<?php

namespace App\Services\Api\Ynov;

use App\Models\Api\Ynov\SignatureRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Service de Signature Électronique — YAKOA AFRICASSUR
 *
 * ARCHITECTURE
 * ------------
 * 1. L'app hôte appelle POST /api/v1/signature/generate-link
 *    -> Laravel crée un token public opaque (64 car. alphanumériques, usage unique, expirant)
 *       et stocke les métadonnées (webhook_url + api_key de l'app hôte) dans signature_requests.
 * 2. Le client ouvre /signature/widget/{token} (directement, par QR code, SMS, email ou WhatsApp).
 * 3. Le widget poste la signature sur POST /api/v1/signature/webhook (MÊME ORIGINE, donc
 *    ni CORS, ni preflight, ni 404).
 * 4. Laravel relaie la signature au webhook de l'app hôte (avec l'api_key lue en base),
 *    puis marque le token comme utilisé dans la MÊME transaction.
 * 5. Le poste desktop détecte la fin via GET /api/v1/signature/token/{token}/status.
 *
 * RÈGLES DE PERSISTANCE (strictes)
 * --------------------------------
 * - Le document n'est JAMAIS stocké (seulement son URL).
 * - La signature n'est JAMAIS stockée, même « pour traçabilité ». Elle transite en mémoire.
 * - Seules les métadonnées du token vivent en base.
 *
 * SÉCURITÉ
 * --------
 * - L'api_key de l'app hôte ne quitte jamais le serveur : elle n'est ni rendue dans le HTML,
 *   ni acceptée depuis le navigateur. C'est le token (imprévisible, usage unique, expirant)
 *   qui authentifie la requête du widget.
 * - Aucun paramètre de configuration (webhook_url, api_key…) n'est surchargeable par query string.
 */


class SignatureService
{

    protected string $api_webhook_key;
    public function __construct()
    {
        $this->api_webhook_key = config('services.signature.api_webhook_key');
    }
    public const INTERNAL_ECHO_MARKER = 'internal-echo';

    public const TOKEN_LENGTH  = 64;
    public const TOKEN_PATTERN = '[A-Za-z0-9]{64}';

    private const MAX_SIGNATURE_BYTES = 2 * 1024 * 1024;

    public const DEFAULT_EXPIRES_IN = 3600;

    /** Purpose OTP par défaut pour le flux signature. */
    public const DEFAULT_OTP_PURPOSE = 'signature';

    // =====================================================================
    // TOKEN
    // =====================================================================

    public static function normalizeToken(?string $token): ?string
    {
        if ($token === null) {
            return null;
        }

        $normalized = rawurldecode(trim($token));

        if (!preg_match('/^[A-Za-z0-9]{' . self::TOKEN_LENGTH . '}$/', $normalized)) {
            return null;
        }

        return $normalized;
    }

    private function generateUniqueToken(): string
    {
        do {
            $token = Str::random(self::TOKEN_LENGTH);
        } while (SignatureRequest::where('token', $token)->exists());

        return $token;
    }

    // =====================================================================
    // GÉNÉRATION DU LIEN
    // =====================================================================

    /**
     * Générer un lien de signature à usage unique.
     *
     * @param array $options Options OTP optionnelles :
     *   - signer_login        (string|null)
     *   - signer_user_uuid    (string|null)
     *   - signer_email        (string|null)
     *   - signer_phone        (string|null)
     *   - otp_purpose         (string|null)   défaut : 'signature'
     *   - otp_qr_url_template (string|null)
     */
    public function generateSignatureLink(
        ?string $documentUrl,
        ?string $documentDescription,
        string $webhookUrl,
        ?string $successRedirectUrl = null,
        ?string $cancelRedirectUrl = null,
        bool $enableAutoPolling = false,
        int $expiresIn = self::DEFAULT_EXPIRES_IN,
        array $options = []
    ): array {
        return DB::transaction(function () use (
            $documentUrl,
            $documentDescription,
            $webhookUrl,
            $successRedirectUrl,
            $cancelRedirectUrl,
            $enableAutoPolling,
            $expiresIn,
            $options
        ) {
            $token     = $this->generateUniqueToken();
            $expiresAt = now()->addSeconds($expiresIn);

            $metadata = [
                'created_by'           => 'system',
                'success_redirect_url' => $successRedirectUrl,
                'cancel_redirect_url'  => $cancelRedirectUrl,
                'enable_auto_polling'  => $enableAutoPolling,
                // Options OTP (facultatives)
                'signer_login'         => $options['signer_login']        ?? null,
                'signer_user_uuid'     => $options['signer_user_uuid']    ?? null,
                'signer_email'         => $options['signer_email']        ?? null,
                'signer_phone'         => $options['signer_phone']        ?? null,
                'otp_purpose'          => $options['otp_purpose']         ?? self::DEFAULT_OTP_PURPOSE,
                'otp_qr_url_template'  => $options['otp_qr_url_template'] ?? null,
            ];

            $signatureRequest = SignatureRequest::create([
                'uuid_signature_request' => (string) Str::uuid(),
                'token'                  => $token,
                'document_url'           => $documentUrl,
                'document_description'   => $documentDescription,
                'webhook_url'            => $webhookUrl,
                'api_key'                => $this->api_webhook_key,
                'expires_at'             => $expiresAt,
                'delivery_status'        => SignatureRequest::DELIVERY_PENDING,
                'metadata'               => $metadata,
            ]);

            return [
                'token'                    => $token,
                'widget_url'               => $this->widgetUrl($token),
                'status_url'               => url('/api/v1/signature/token/' . $token . '/status'),
                'expires_at'               => $expiresAt->toIso8601String(),
                'expires_in'               => $expiresIn,
                'document_url'             => $documentUrl,
                'document_description'     => $documentDescription,
                'signature_request_uuid'   => $signatureRequest->uuid_signature_request,
                'success_redirect_url'     => $successRedirectUrl,
                'cancel_redirect_url'      => $cancelRedirectUrl,
                'enable_auto_polling'      => $enableAutoPolling,
                'otp_purpose'              => $metadata['otp_purpose'],
                'has_otp_qr_url_template'  => !empty($metadata['otp_qr_url_template']),
            ];
        });
    }

    public function widgetUrl(string $token): string
    {
        return url('/signature/widget/' . $token);
    }

    // =====================================================================
    // AFFICHAGE DU WIDGET
    // =====================================================================

    public function getWidgetData(string $token): ?array
    {
        $token = self::normalizeToken($token);

        if (!$token) {
            return null;
        }

        $signatureRequest = SignatureRequest::where('token', $token)->first();

        if (!$signatureRequest || !$signatureRequest->isValid()) {
            return null;
        }

        $metadata = $signatureRequest->metadata ?? [];

        return [
            'token'                => $signatureRequest->token,
            'document_url'         => $signatureRequest->document_url,
            'document_description' => $signatureRequest->document_description,
            'success_redirect_url' => $metadata['success_redirect_url'] ?? null,
            'cancel_redirect_url'  => $metadata['cancel_redirect_url'] ?? null,
            'enable_auto_polling'  => (bool) ($metadata['enable_auto_polling'] ?? false),
            // Nouveaux champs OTP (facultatif)
            'signer_login'         => $metadata['signer_login']        ?? null,
            'signer_user_uuid'     => $metadata['signer_user_uuid']    ?? null,
            'signer_email'         => $metadata['signer_email']        ?? null,
            'signer_phone'         => $metadata['signer_phone']        ?? null,
            'otp_purpose'          => $metadata['otp_purpose']         ?? self::DEFAULT_OTP_PURPOSE,
            'otp_qr_url_template'  => $metadata['otp_qr_url_template'] ?? null,
        ];
    }

    // =====================================================================
    // RÉCEPTION ET RELAIS DE LA SIGNATURE
    // =====================================================================

    /**
     * @param array $context Contexte optionnel :
     *   - method : 'handwritten' | 'otp_qr'  (défaut : 'handwritten')
     *   - otp    : [channel, contact, purpose, ip_address, user_agent, used_at]
     *   - geo    : [lat, lng]
     */
    public function processSignature(string $signatureBase64, string $token, array $context = []): array
    {
        $token = self::normalizeToken($token);

        if (!$token) {
            return $this->failure('Token invalide.', 'INVALID_TOKEN', 404);
        }

        if (!$this->isValidSignaturePayload($signatureBase64)) {
            return $this->failure(
                'Signature invalide : une image PNG encodée en base64 est attendue.',
                'INVALID_SIGNATURE',
                422
            );
        }

        $method = $context['method'] ?? 'handwritten';
        if (!in_array($method, ['handwritten', 'otp_qr'], true)) {
            return $this->failure('Méthode de signature invalide.', 'INVALID_METHOD', 422);
        }

        $claim = DB::transaction(function () use ($token) {
            $signatureRequest = SignatureRequest::where('token', $token)->lockForUpdate()->first();

            if (!$signatureRequest) {
                return $this->failure('Token invalide.', 'INVALID_TOKEN', 404);
            }

            if ($signatureRequest->is_used) {
                return $this->failure('Token déjà utilisé.', 'TOKEN_ALREADY_USED', 409);
            }

            if ($signatureRequest->isExpired()) {
                return $this->failure('Token expiré.', 'EXPIRED_TOKEN', 410);
            }

            $signatureRequest->markAsUsed();

            return ['success' => true, 'request' => $signatureRequest];
        });

        if (!$claim['success']) {
            return $claim;
        }

        /** @var SignatureRequest $signatureRequest */
        $signatureRequest = $claim['request'];

        $delivery = $this->deliverToHost($signatureRequest, $signatureBase64, $method, $context);

        $signatureRequest->forceFill([
            'delivery_status' => $delivery['delivered']
                ? SignatureRequest::DELIVERY_DELIVERED
                : SignatureRequest::DELIVERY_FAILED,
            'delivered_at'    => $delivery['delivered'] ? now() : null,
        ])->save();

        if (!$delivery['delivered']) {
            return [
                'success' => false,
                'message' => "La signature n'a pas pu être transmise à l'application hôte.",
                'code'    => 'WEBHOOK_DELIVERY_FAILED',
                'status'  => 502,
                'data'    => [
                    'signature_request_uuid' => $signatureRequest->uuid_signature_request,
                    'webhook_status'         => $delivery['status'],
                    'token_consumed'         => true,
                    'method'                 => $method,
                ],
            ];
        }

        return [
            'success' => true,
            'message' => 'Signature transmise avec succès.',
            'code'    => 'SIGNATURE_PROCESSED',
            'data'    => [
                'signature_request_uuid' => $signatureRequest->uuid_signature_request,
                'webhook_response'       => $delivery['body'],
                'token_consumed'         => true,
                'method'                 => $method,
            ],
        ];
    }

    /**
     * Relaie la signature au webhook de l'app hôte, avec l'api_key lue en base.
     */
    private function deliverToHost(
        SignatureRequest $signatureRequest,
        string $signatureBase64,
        string $method = 'handwritten',
        array $context = []
    ): array {
        if ($signatureRequest->webhook_url === self::INTERNAL_ECHO_MARKER) {
            return [
                'delivered' => true,
                'status'    => 200,
                'body'      => [
                    'success'     => true,
                    'received_at' => now()->toIso8601String(),
                    'simulated'   => true,
                    'method'      => $method,
                ],
            ];
        }

        $payload = [
            'success'                => true,
            'status'                 => 200,
            'signature'              => $signatureBase64,
            'method'                 => $method,
            'token'                  => $signatureRequest->token,
            'document_url'           => $signatureRequest->document_url,
            'signature_request_uuid' => $signatureRequest->uuid_signature_request,
            'signed_at'              => optional($signatureRequest->signed_at)->toIso8601String()
                                        ?? now()->toIso8601String(),
            'timestamp'              => now()->toIso8601String(),
        ];

        if ($method === 'otp_qr') {
            $otp = $context['otp'] ?? [];
            $geo = $context['geo'] ?? [];

            $payload['otp'] = [
                'channel'    => $otp['channel']    ?? null,
                'contact'    => $otp['contact']    ?? null,
                'purpose'    => $otp['purpose']    ?? null,
                'ip_address' => $otp['ip_address'] ?? null,
                'user_agent' => $otp['user_agent'] ?? null,
                'used_at'    => $otp['used_at']    ?? null,
            ];

            $payload['geo'] = [
                'lat' => $geo['lat'] ?? null,
                'lng' => $geo['lng'] ?? null,
            ];
        }

        try {
            $response = Http::withHeaders([
                    'X-Api-Key'    => $signatureRequest->api_key,
                    'Content-Type' => 'application/json',
                ])
                ->timeout(15)
                ->retry(2, 500, throw: false)
                ->post($signatureRequest->webhook_url, $payload);

            if (!$response->successful()) {
                Log::warning('[Signature] Webhook hôte en échec', [
                    'uuid'   => $signatureRequest->uuid_signature_request,
                    'status' => $response->status(),
                    'method' => $method,
                ]);

                return ['delivered' => false, 'status' => $response->status(), 'body' => null];
            }

            return [
                'delivered' => true,
                'status'    => $response->status(),
                'body'      => $response->json(),
            ];
        } catch (\Throwable $e) {
            Log::error('[Signature] Exception lors de la livraison au webhook hôte', [
                'uuid'    => $signatureRequest->uuid_signature_request,
                'message' => $e->getMessage(),
                'method'  => $method,
            ]);

            return ['delivered' => false, 'status' => null, 'body' => null];
        }
    }

    private function isValidSignaturePayload(string $signatureBase64): bool
    {
        if (strlen($signatureBase64) > self::MAX_SIGNATURE_BYTES) {
            return false;
        }

        return (bool) preg_match('#^data:image/png;base64,[A-Za-z0-9+/]+={0,2}$#', $signatureBase64);
    }

    // =====================================================================
    // STATUT
    // =====================================================================

    public function checkTokenStatus(string $token): ?array
    {
        $token = self::normalizeToken($token);

        if (!$token) {
            return null;
        }

        $signatureRequest = SignatureRequest::where('token', $token)->first();

        if (!$signatureRequest) {
            return null;
        }

        return [
            'valid'                  => true,
            'expires_at'             => optional($signatureRequest->expires_at)->toIso8601String(),
            'expired'                => $signatureRequest->isExpired(),
            'is_used'                => (bool) $signatureRequest->is_used,
            'is_valid'               => $signatureRequest->isValid(),
            'signed_at'              => optional($signatureRequest->signed_at)->toIso8601String(),
            'delivery_status'        => $signatureRequest->delivery_status,
            'document_description'   => $signatureRequest->document_description,
            'signature_request_uuid' => $signatureRequest->uuid_signature_request,
        ];
    }

    // =====================================================================
    // ENVOI DU LIEN
    // =====================================================================

    private function activeRequest(?string $token): ?SignatureRequest
    {
        $token = self::normalizeToken($token);

        if (!$token) {
            return null;
        }

        $signatureRequest = SignatureRequest::where('token', $token)->first();

        return ($signatureRequest && $signatureRequest->isValid()) ? $signatureRequest : null;
    }

    public function sendLinkByEmail(
        string $token,
        string $email,
        ?string $subject = null,
        ?string $message = null
    ): array {
        $signatureRequest = $this->activeRequest($token);

        if (!$signatureRequest) {
            return $this->failure('Token invalide ou expiré.', 'INVALID_TOKEN', 404);
        }

        $widgetUrl = $this->widgetUrl($signatureRequest->token);
        $subject   = $subject ?: 'Document à signer';
        $body      = $message
            ?: "Bonjour,\n\nVeuillez signer votre document en cliquant sur le lien ci-dessous :\n{$widgetUrl}\n\n"
             . "Ce lien est personnel, à usage unique et expire le "
             . $signatureRequest->expires_at->format('d/m/Y à H:i') . ".\n\nYAKOA AFRICASSUR";

        try {
            Mail::raw($body, function ($mail) use ($email, $subject) {
                $mail->to($email)->subject($subject);
            });

            return [
                'success' => true,
                'message' => 'Email envoyé avec succès.',
                'code'    => 'EMAIL_SENT',
                'data'    => ['email' => $email],
            ];
        } catch (\Throwable $e) {
            Log::error('[Signature] Échec envoi email', ['message' => $e->getMessage()]);

            return $this->failure("Erreur lors de l'envoi de l'email.", 'EMAIL_SEND_ERROR', 500);
        }
    }

    public function sendLinkBySms(string $token, string $phone, ?string $message = null): array
    {
        $signatureRequest = $this->activeRequest($token);

        if (!$signatureRequest) {
            return $this->failure('Token invalide ou expiré.', 'INVALID_TOKEN', 404);
        }

        $widgetUrl = $this->widgetUrl($signatureRequest->token);
        $text      = $message ?: "Signez votre document ici : {$widgetUrl}";

        return $this->sendViaInfobip(
            '/sms/2/text/advanced',
            [
                'messages' => [[
                    'from'         => config('services.infobip.sms_from', 'YAKOA'),
                    'destinations' => [['to' => $phone]],
                    'text'         => $text,
                ]],
            ],
            ['phone' => $phone],
            'SMS_SENT',
            'INFOBIP_SMS_ERROR'
        );
    }

    public function sendLinkByWhatsapp(string $token, string $phone, ?string $message = null): array
    {
        $signatureRequest = $this->activeRequest($token);

        if (!$signatureRequest) {
            return $this->failure('Token invalide ou expiré.', 'INVALID_TOKEN', 404);
        }

        $widgetUrl = $this->widgetUrl($signatureRequest->token);
        $text      = $message ?: "Veuillez signer votre document en cliquant sur ce lien : {$widgetUrl}";

        return $this->sendViaInfobip(
            '/whatsapp/1/message/text',
            [
                'from'    => config('services.infobip.whatsapp_from'),
                'to'      => $phone,
                'content' => ['text' => $text],
            ],
            ['phone' => $phone],
            'WHATSAPP_SENT',
            'INFOBIP_WHATSAPP_ERROR'
        );
    }

    private function sendViaInfobip(
        string $path,
        array $payload,
        array $extraData,
        string $successCode,
        string $errorCode
    ): array {
        $apiKey  = config('services.infobip.api_key');
        $baseUrl = rtrim((string) config('services.infobip.base_url'), '/');

        if (!$apiKey || !$baseUrl) {
            return $this->failure('Configuration Infobip manquante.', 'INFOBIP_CONFIG_ERROR', 500);
        }

        try {
            $response = Http::withHeaders([
                    'Authorization' => 'App ' . $apiKey,
                    'Content-Type'  => 'application/json',
                ])
                ->timeout(15)
                ->post($baseUrl . $path, $payload);

            if (!$response->successful()) {
                Log::warning('[Signature] Infobip en échec', [
                    'path'   => $path,
                    'status' => $response->status(),
                ]);

                return $this->failure('Erreur du fournisseur de messagerie.', $errorCode, 502);
            }

            return [
                'success' => true,
                'message' => 'Message envoyé avec succès.',
                'code'    => $successCode,
                'data'    => $extraData,
            ];
        } catch (\Throwable $e) {
            Log::error('[Signature] Exception Infobip', ['message' => $e->getMessage()]);

            return $this->failure('Erreur du fournisseur de messagerie.', $errorCode, 502);
        }
    }

    // =====================================================================
    // HELPERS
    // =====================================================================

    private function failure(string $message, string $code, int $status): array
    {
        return [
            'success' => false,
            'message' => $message,
            'code'    => $code,
            'status'  => $status,
        ];
    }
}