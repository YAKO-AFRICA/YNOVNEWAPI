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
    /**
     * Marqueur spécial utilisé UNIQUEMENT par la route de démonstration.
     *
     * Un `webhook_url` réel entraîne un véritable appel HTTP sortant. Or la démo
     * s'auto-appelle (Laravel -> Laravel) : sur un serveur mono-thread comme
     * `php artisan serve`, la requête sortante attend un processus déjà occupé
     * à traiter la requête entrante -> blocage jusqu'au timeout. Ce marqueur
     * court-circuite l'appel réseau pour ne simuler QUE la livraison, sans
     * jamais toucher au flux réel (voir deliverToHost()).
     */
    public const INTERNAL_ECHO_MARKER = 'internal-echo';

    /** Longueur du token public. */
    public const TOKEN_LENGTH = 64;

    /** Contrainte de route — garantit un token URL-safe (pas de « | » à encoder/décoder). */
    public const TOKEN_PATTERN = '[A-Za-z0-9]{64}';

    /** Taille max acceptée pour la signature base64 (~2 Mo). */
    private const MAX_SIGNATURE_BYTES = 2 * 1024 * 1024;

    /** Durée de validité par défaut d'un lien (1 h). */
    public const DEFAULT_EXPIRES_IN = 3600;

    // =====================================================================
    // TOKEN
    // =====================================================================

    /**
     * Normalise et valide un token reçu de l'extérieur.
     *
     * Le token est volontairement alphanumérique pur : il traverse des URL, des QR codes,
     * des SMS et des aperçus de liens WhatsApp sans jamais être ré-encodé. On se contente
     * d'un rawurldecode défensif (au cas où une couche intermédiaire aurait encodé la chaîne)
     * puis d'une validation stricte du format.
     */
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

    /** Génère un token public unique. */
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
     * @param string|null $documentUrl         URL HTTP(S) du document (optionnel : signature sans document)
     * @param string|null $documentDescription Description affichée au signataire
     * @param string      $webhookUrl          Webhook de l'app hôte qui recevra la signature
     * @param string      $apiKey              Secret partagé — reste côté serveur
     * @param string|null $successRedirectUrl  Redirection après signature réussie
     * @param string|null $cancelRedirectUrl   Redirection en cas d'annulation
     * @param bool        $enableAutoPolling   Polling automatique (scénario agence grand écran)
     * @param int         $expiresIn           Durée de validité en secondes
     */
    public function generateSignatureLink(
        ?string $documentUrl,
        ?string $documentDescription,
        string $webhookUrl,
        string $apiKey,
        ?string $successRedirectUrl = null,
        ?string $cancelRedirectUrl = null,
        bool $enableAutoPolling = false,
        int $expiresIn = self::DEFAULT_EXPIRES_IN
    ): array {
        return DB::transaction(function () use (
            $documentUrl,
            $documentDescription,
            $webhookUrl,
            $apiKey,
            $successRedirectUrl,
            $cancelRedirectUrl,
            $enableAutoPolling,
            $expiresIn
        ) {
            $token     = $this->generateUniqueToken();
            $expiresAt = now()->addSeconds($expiresIn);

            $signatureRequest = SignatureRequest::create([
                'uuid_signature_request' => (string) Str::uuid(),
                'token'                  => $token,
                'document_url'           => $documentUrl,   // URL uniquement, jamais le contenu
                'document_description'   => $documentDescription,
                'webhook_url'            => $webhookUrl,
                'api_key'                => $apiKey,
                'expires_at'             => $expiresAt,
                'delivery_status'        => SignatureRequest::DELIVERY_PENDING,
                'metadata'               => [
                    'created_by'           => 'system',
                    'success_redirect_url' => $successRedirectUrl,
                    'cancel_redirect_url'  => $cancelRedirectUrl,
                    'enable_auto_polling'  => $enableAutoPolling,
                ],
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
            ];
        });
    }

    /** URL publique du widget pour un token donné. */
    public function widgetUrl(string $token): string
    {
        return url('/signature/widget/' . $token);
    }

    // =====================================================================
    // AFFICHAGE DU WIDGET
    // =====================================================================

    /**
     * Données nécessaires à l'initialisation du widget.
     *
     * N'expose NI webhook_url NI api_key : ces valeurs restent côté serveur.
     */
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
        ];
    }

    // =====================================================================
    // RÉCEPTION ET RELAIS DE LA SIGNATURE
    // =====================================================================

    /**
     * Traiter la signature envoyée par le widget.
     *
     * Le token seul authentifie l'appel. Aucun header X-Api-Key n'est attendu du navigateur.
     *
     * Point important : le token est marqué comme utilisé MÊME si le webhook de l'app hôte
     * échoue. Sinon un hôte momentanément indisponible bloquerait indéfiniment le poste
     * desktop en polling alors que le client a bel et bien signé. L'état de livraison est
     * exposé séparément via delivery_status.
     */
    public function processSignature(string $signatureBase64, string $token): array
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

        // Verrouillage pessimiste : deux soumissions simultanées ne peuvent pas
        // relayer deux fois la même signature.
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

            // Marquage immédiat : la signature n'est PAS stockée.
            $signatureRequest->markAsUsed();

            return ['success' => true, 'request' => $signatureRequest];
        });

        if (!$claim['success']) {
            return $claim;
        }

        /** @var SignatureRequest $signatureRequest */
        $signatureRequest = $claim['request'];

        $delivery = $this->deliverToHost($signatureRequest, $signatureBase64);

        $signatureRequest->forceFill([
            'delivery_status' => $delivery['delivered']
                ? SignatureRequest::DELIVERY_DELIVERED
                : SignatureRequest::DELIVERY_FAILED,
            'delivered_at'    => $delivery['delivered'] ? now() : null,
        ])->save();

        if (!$delivery['delivered']) {
            // La signature est perdue volontairement (aucune persistance).
            // L'app hôte devra relancer une demande de signature.
            return [
                'success' => false,
                'message' => "La signature n'a pas pu être transmise à l'application hôte.",
                'code'    => 'WEBHOOK_DELIVERY_FAILED',
                'status'  => 502,
                'data'    => [
                    'signature_request_uuid' => $signatureRequest->uuid_signature_request,
                    'webhook_status'         => $delivery['status'],
                    'token_consumed'         => true,
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
            ],
        ];
    }

    /**
     * Relaie la signature au webhook de l'app hôte, avec l'api_key lue en base.
     */
    private function deliverToHost(SignatureRequest $signatureRequest, string $signatureBase64): array
    {
        // Court-circuit réservé à la page de démonstration : voir INTERNAL_ECHO_MARKER.
        // Ne concerne jamais un webhook_url réel fourni par une app hôte.
        if ($signatureRequest->webhook_url === self::INTERNAL_ECHO_MARKER) {
            return [
                'delivered' => true,
                'status'    => 200,
                'body'      => [
                    'success'     => true,
                    'received_at' => now()->toIso8601String(),
                    'simulated'   => true,
                ],
            ];
        }

        try {
            $response = Http::withHeaders([
                    'X-Api-Key'    => $signatureRequest->api_key,
                    'Content-Type' => 'application/json',
                ])
                ->timeout(15)
                ->retry(2, 500, throw: false)
                ->post($signatureRequest->webhook_url, [
                    'success'                => true,
                    'status'                 => 200,
                    'signature'              => $signatureBase64,
                    'token'                  => $signatureRequest->token,
                    'document_url'           => $signatureRequest->document_url,
                    'signature_request_uuid' => $signatureRequest->uuid_signature_request,
                    'signed_at'              => optional($signatureRequest->signed_at)->toIso8601String()
                                                ?? now()->toIso8601String(),
                    'timestamp'              => now()->toIso8601String(),
                ]);

            if (!$response->successful()) {
                Log::warning('[Signature] Webhook hôte en échec', [
                    'uuid'   => $signatureRequest->uuid_signature_request,
                    'status' => $response->status(),
                ]);

                return ['delivered' => false, 'status' => $response->status(), 'body' => null];
            }

            return [
                'delivered' => true,
                'status'    => $response->status(),
                'body'      => $response->json(),
            ];
        } catch (\Throwable $e) {
            // On ne logge jamais le contenu de la signature.
            Log::error('[Signature] Exception lors de la livraison au webhook hôte', [
                'uuid'    => $signatureRequest->uuid_signature_request,
                'message' => $e->getMessage(),
            ]);

            return ['delivered' => false, 'status' => null, 'body' => null];
        }
    }

    /** Valide le format de la signature sans la conserver. */
    private function isValidSignaturePayload(string $signatureBase64): bool
    {
        if (strlen($signatureBase64) > self::MAX_SIGNATURE_BYTES) {
            return false;
        }

        return (bool) preg_match('#^data:image/png;base64,[A-Za-z0-9+/]+={0,2}$#', $signatureBase64);
    }

    // =====================================================================
    // STATUT (polling desktop)
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
    // ENVOI DU LIEN (Email / SMS / WhatsApp)
    // =====================================================================

    /** Récupère une demande de signature encore valide, ou null. */
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

    /** Appel générique à l'API Infobip. */
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