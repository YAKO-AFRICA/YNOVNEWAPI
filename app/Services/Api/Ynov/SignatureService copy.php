<?php

namespace App\Services\Api\Ynov;

use App\Models\Api\Ynov\SignatureRequest;
use App\Models\Api\Ynov\parameter\ActivityLog;
use App\Support\SafeUrl;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Service de Signature Électronique — YAKOA AFRICASSUR
 *
 * ARCHITECTURE
 * ------------
 * 1. L'app hôte appelle POST /api/v1/signature/generate-link (auth Sanctum).
 * 2. Le client ouvre /signature/widget/{token}.
 * 3. En mode OTP, le widget appelle POST /api/v1/signature/otp/verify AVEC le token :
 *    le serveur vérifie le code pour le signataire défini à la création du lien
 *    et enregistre une PREUVE OTP liée au token (cache, expirant avec le token).
 * 4. Le widget poste la signature sur POST /api/v1/signature/webhook (même origine).
 *    Pour method=otp_qr, la preuve serveur est OBLIGATOIRE ; le bloc `otp` envoyé par
 *    le navigateur est ignoré (IP, user-agent, canal, date viennent du serveur).
 * 5. Laravel verrouille le token, relaie au webhook de l'hôte (clé lue en base,
 *    déchiffrée, IP validée + épinglée contre le SSRF). Si la livraison échoue
 *    de façon récupérable, le token est RELÂCHÉ (max MAX_DELIVERY_ATTEMPTS essais)
 *    pour que le client puisse re-signer.
 *
 * RÈGLES DE PERSISTANCE
 * ---------------------
 * - Ni le document ni la signature ne sont stockés. Seules les métadonnées vivent en base.
 * - La preuve OTP ne contient aucune donnée personnelle (pas de nom, adresse...).
 */
class SignatureService
{
    protected string $api_webhook_key;

    public function __construct()
    {
        $this->api_webhook_key = config('services.signature.api_webhook_key') ?? '';
    }

    public const INTERNAL_ECHO_MARKER = 'internal-echo';

    public const TOKEN_LENGTH  = 64;
    public const TOKEN_PATTERN = '[A-Za-z0-9]{64}';

    private const MAX_SIGNATURE_BYTES = 2 * 1024 * 1024;
    private const MIN_SIGNATURE_PX    = 20;
    private const MAX_SIGNATURE_PX    = 4000;

    public const DEFAULT_EXPIRES_IN = 3600;

    public const DEFAULT_OTP_PURPOSE = 'signature';

    /** Nombre maximal de tentatives de livraison à l'hôte par token. */
    public const MAX_DELIVERY_ATTEMPTS = 3;

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
                'delivery_attempts'    => 0,
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
                'api_key'                => $this->api_webhook_key, // chiffré par le cast du modèle
                'expires_at'             => $expiresAt,
                'delivery_status'        => SignatureRequest::DELIVERY_PENDING,
                'metadata'               => $metadata,
            ]);

            ActivityLog::log([
                'user_uuid' => null,
                'action' => 'signature_link_created',
                'action_type' => 'security',
                'module' => 'signatures',
                'description' => 'Création d’une demande de signature électronique.',
                'resource_type' => 'signature_request',
                'resource_id' => $signatureRequest->uuid_signature_request,
                'new_values' => [
                    'document_url' => $documentUrl,
                    'document_description' => $documentDescription,
                    'expires_at' => $expiresAt->toIso8601String(),
                    'signer_user_uuid' => $metadata['signer_user_uuid'],
                    'delivery_status' => $signatureRequest->delivery_status,
                ],
                'level' => 'info',
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
        $signatureRequest = $this->findActiveRequest($token);

        if (!$signatureRequest) {
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
            'signer_login'         => $metadata['signer_login']        ?? null,
            'signer_user_uuid'     => $metadata['signer_user_uuid']    ?? null,
            'signer_email'         => $metadata['signer_email']        ?? null,
            'signer_phone'         => $metadata['signer_phone']        ?? null,
            'otp_purpose'          => $metadata['otp_purpose']         ?? self::DEFAULT_OTP_PURPOSE,
            'otp_qr_url_template'  => $metadata['otp_qr_url_template'] ?? null,
        ];
    }

    /** Demande valide (existante, non utilisée, non expirée) ou null. */
    public function findActiveRequest(?string $token): ?SignatureRequest
    {
        $token = self::normalizeToken($token);

        if (!$token) {
            return null;
        }

        $signatureRequest = SignatureRequest::where('token', $token)->first();

        return ($signatureRequest && $signatureRequest->isValid()) ? $signatureRequest : null;
    }

    // =====================================================================
    // PREUVE OTP LIÉE AU TOKEN
    // =====================================================================

    private static function otpProofKey(string $token): string
    {
        return 'signature:otp_proof:' . hash('sha256', $token);
    }

    /**
     * Enregistre la preuve qu'un OTP valide a été saisi pour CE token.
     * Utilise le cache : le driver doit être partagé entre processus (redis, database, file...).
     */
    public function storeOtpProof(SignatureRequest $signatureRequest, array $proof): void
    {
        Cache::put(
            self::otpProofKey($signatureRequest->token),
            $proof,
            $signatureRequest->expires_at
        );
    }

    // =====================================================================
    // RÉCEPTION ET RELAIS DE LA SIGNATURE
    // =====================================================================

    /**
     * @param array $context Contexte optionnel :
     *   - method : 'handwritten' | 'otp_qr'  (défaut : 'handwritten')
     *   - geo    : [lat, lng]  (déclaratif, fourni par le navigateur)
     *   Le bloc `otp` éventuellement fourni par le client est IGNORÉ.
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

        // OTP : la preuve serveur est obligatoire
        $proof = null;
        if ($method === 'otp_qr') {
            $proof = Cache::get(self::otpProofKey($token));

            if (!is_array($proof)) {
                return $this->failure(
                    'Vérification du code OTP requise avant de signer.',
                    'OTP_REQUIRED',
                    403
                );
            }
        }

        // Verrouillage atomique du token
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

        $attempt = (int) ($signatureRequest->metadata['delivery_attempts'] ?? 0) + 1;

        $delivery = $this->deliverToHost(
            $signatureRequest,
            $signatureBase64,
            $method,
            [
                'otp'     => $proof ?? [],
                'geo'     => $context['geo'] ?? [],
                'attempt' => $attempt,
            ]
        );

        $metadata = $signatureRequest->metadata ?? [];

        if ($delivery['delivered']) {
            $signatureRequest->forceFill([
                'delivery_status' => SignatureRequest::DELIVERY_DELIVERED,
                'delivered_at'    => now(),
            ])->save();

            Cache::forget(self::otpProofKey($token));
            $tokenConsumed = true;
            $retryAllowed  = false;
        } else {
            $metadata['delivery_attempts']  = $attempt;
            $metadata['last_webhook_status'] = $delivery['status'];

            $retryAllowed = $delivery['retryable'] && $attempt < self::MAX_DELIVERY_ATTEMPTS;

            if ($retryAllowed) {
                // On relâche le token : le client peut re-signer (la preuve OTP est conservée)
                $signatureRequest->forceFill([
                    'metadata'        => $metadata,
                    'is_used'         => false,
                    'signed_at'       => null,
                    'delivery_status' => SignatureRequest::DELIVERY_PENDING,
                ])->save();
            } else {
                $signatureRequest->forceFill([
                    'metadata'        => $metadata,
                    'delivery_status' => SignatureRequest::DELIVERY_FAILED,
                ])->save();

                Cache::forget(self::otpProofKey($token));
            }

            $tokenConsumed = !$retryAllowed;
        }

        ActivityLog::log([
            'user_uuid' => null,
            'action' => $delivery['delivered'] ? 'signature_processed' : 'signature_delivery_failed',
            'action_type' => 'security',
            'module' => 'signatures',
            'description' => $delivery['delivered']
                ? 'Signature transmise à l’application hôte.'
                : 'Échec de transmission de la signature à l’application hôte.',
            'resource_type' => 'signature_request',
            'resource_id' => $signatureRequest->uuid_signature_request,
            'new_values' => [
                'delivery_status' => $signatureRequest->delivery_status,
                'webhook_status' => $delivery['status'],
                'method' => $method,
                'attempt' => $attempt,
                'retry_allowed' => $retryAllowed,
                'signer_user_uuid' => $metadata['signer_user_uuid'] ?? null,
            ],
            'level' => $delivery['delivered'] ? 'info' : ($retryAllowed ? 'warning' : 'error'),
        ]);

        if (!$delivery['delivered']) {
            return [
                'success' => false,
                'message' => $retryAllowed
                    ? "La signature n'a pas pu être transmise. Veuillez réessayer."
                    : "La signature n'a pas pu être transmise à l'application hôte.",
                'code'    => 'WEBHOOK_DELIVERY_FAILED',
                'status'  => 502,
                'data'    => [
                    'signature_request_uuid' => $signatureRequest->uuid_signature_request,
                    'webhook_status'         => $delivery['status'],
                    'token_consumed'         => $tokenConsumed,
                    'retry_allowed'          => $retryAllowed,
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
     * Relaie la signature au webhook de l'app hôte.
     *
     * - IP du webhook validée et épinglée (anti-SSRF / DNS rebinding), redirections refusées
     * - Idempotency-Key = uuid de la demande : l'hôte doit conserver la PREMIÈRE signature acceptée
     * - retry uniquement sur erreur réseau ou 5xx
     *
     * @return array{delivered:bool,status:?int,body:mixed,retryable:bool}
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
                'retryable' => false,
                'body'      => [
                    'success'     => true,
                    'received_at' => now()->toIso8601String(),
                    'simulated'   => true,
                    'method'      => $method,
                ],
            ];
        }

        try {
            $target = SafeUrl::resolve(
                $signatureRequest->webhook_url,
                true,
                (array) config('services.signature.allowed_webhook_hosts', [])
            );
        } catch (InvalidArgumentException $e) {
            Log::error('[Signature] Webhook hôte refusé (SSRF)', [
                'uuid'   => $signatureRequest->uuid_signature_request,
                'reason' => $e->getMessage(),
            ]);

            return ['delivered' => false, 'status' => null, 'body' => null, 'retryable' => false];
        }

        $payload = [
            'success'                => true,
            'status'                 => 200,
            'signature'              => $signatureBase64,
            'method'                 => $method,
            'token'                  => $signatureRequest->token,
            'document_url'           => $signatureRequest->document_url,
            'signature_request_uuid' => $signatureRequest->uuid_signature_request,
            'idempotency_key'        => $signatureRequest->uuid_signature_request,
            'delivery_attempt'       => $context['attempt'] ?? 1,
            'signed_at'              => optional($signatureRequest->signed_at)->toIso8601String()
                                        ?? now()->toIso8601String(),
            'timestamp'              => now()->toIso8601String(),
        ];

        if ($method === 'otp_qr') {
            $otp = $context['otp'] ?? [];
            $geo = $context['geo'] ?? [];

            $payload['signer_user_uuid'] = $otp['user_uuid'] ?? null;

            // Données issues de la vérification serveur (non falsifiables)
            $payload['otp'] = [
                'verified'   => true,
                'channel'    => $otp['channel']    ?? null,
                'contact'    => $otp['contact']    ?? null,
                'purpose'    => $otp['purpose']    ?? null,
                'ip_address' => $otp['ip_address'] ?? null,
                'user_agent' => $otp['user_agent'] ?? null,
                'used_at'    => $otp['used_at']    ?? null,
            ];

            // Déclaratif (fourni par le navigateur) : non vérifiable côté serveur
            $payload['geo'] = [
                'lat'      => $geo['lat'] ?? null,
                'lng'      => $geo['lng'] ?? null,
                'declared' => true,
            ];
        }

        try {
            $response = Http::withHeaders([
                    'X-Api-Key'       => $signatureRequest->api_key, // déchiffré par le cast
                    'Content-Type'    => 'application/json',
                    'Idempotency-Key' => $signatureRequest->uuid_signature_request,
                ])
                ->withOptions([
                    'allow_redirects' => false,
                    'curl'            => $target['curl'],
                ])
                ->timeout(15)
                ->retry(
                    2,
                    500,
                    fn ($e) => $e instanceof ConnectionException
                        || ($e instanceof RequestException && $e->response->status() >= 500),
                    throw: false
                )
                ->post($target['url'], $payload);

            if (!$response->successful()) {
                $status = $response->status();

                Log::warning('[Signature] Webhook hôte en échec', [
                    'uuid'   => $signatureRequest->uuid_signature_request,
                    'status' => $status,
                    'method' => $method,
                ]);

                return [
                    'delivered' => false,
                    'status'    => $status,
                    'body'      => null,
                    'retryable' => $status >= 500 || in_array($status, [408, 429], true),
                ];
            }

            return [
                'delivered' => true,
                'status'    => $response->status(),
                'body'      => $response->json(),
                'retryable' => false,
            ];
        } catch (\Throwable $e) {
            Log::error('[Signature] Exception lors de la livraison au webhook hôte', [
                'uuid'    => $signatureRequest->uuid_signature_request,
                'message' => $e->getMessage(),
                'method'  => $method,
            ]);

            return ['delivered' => false, 'status' => null, 'body' => null, 'retryable' => true];
        }
    }

    /**
     * Valide réellement le PNG : préfixe data-URI, base64 strict, signature d'en-tête PNG,
     * décodage de l'image et bornes de dimensions (pas de regex sur 2 Mo de texte).
     */
    private function isValidSignaturePayload(string $signatureBase64): bool
    {
        if (strlen($signatureBase64) > self::MAX_SIGNATURE_BYTES) {
            return false;
        }

        $prefix = 'data:image/png;base64,';

        if (!str_starts_with($signatureBase64, $prefix)) {
            return false;
        }

        $binary = base64_decode(substr($signatureBase64, strlen($prefix)), true);

        if ($binary === false || strlen($binary) < 67) {
            return false;
        }

        if (substr($binary, 0, 8) !== "\x89PNG\r\n\x1a\n") {
            return false;
        }

        $info = @getimagesizefromstring($binary);

        if (!$info || $info[2] !== IMAGETYPE_PNG) {
            return false;
        }

        [$width, $height] = $info;

        return $width >= self::MIN_SIGNATURE_PX
            && $height >= self::MIN_SIGNATURE_PX
            && $width <= self::MAX_SIGNATURE_PX
            && $height <= self::MAX_SIGNATURE_PX;
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

        $isValid = $signatureRequest->isValid();

        if ($signatureRequest->delivery_status === SignatureRequest::DELIVERY_DELIVERED) {
            $status = 'signed';
        } elseif ($signatureRequest->delivery_status === SignatureRequest::DELIVERY_FAILED) {
            $status = 'delivery_failed';
        } elseif ($signatureRequest->isExpired()) {
            $status = 'expired';
        } elseif ($signatureRequest->is_used) {
            $status = 'processing';
        } else {
            $status = 'pending';
        }

        return [
            'valid'                  => $isValid,
            'status'                 => $status,
            'expires_at'             => optional($signatureRequest->expires_at)->toIso8601String(),
            'expired'                => $signatureRequest->isExpired(),
            'is_used'                => (bool) $signatureRequest->is_used,
            'is_valid'               => $isValid,
            'signed_at'              => optional($signatureRequest->signed_at)->toIso8601String(),
            'delivery_status'        => $signatureRequest->delivery_status,
            'document_description'   => $signatureRequest->document_description,
            'signature_request_uuid' => $signatureRequest->uuid_signature_request,
        ];
    }

    // =====================================================================
    // ENVOI DU LIEN
    // =====================================================================

    public function sendLinkByEmail(
        string $token,
        string $email,
        ?string $subject = null,
        ?string $message = null
    ): array {
        $signatureRequest = $this->findActiveRequest($token);

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
        $signatureRequest = $this->findActiveRequest($token);

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
        $signatureRequest = $this->findActiveRequest($token);

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