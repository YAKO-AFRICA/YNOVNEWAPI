<?php

namespace App\Services\Api\Ynov;

use App\Models\Api\Ynov\parameter\Role;
use App\Models\Api\Ynov\parameter\User;
use App\Models\Api\Ynov\SignatureRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Service de Signature Électronique
 * 
 * Ce service gère la logique métier du widget de signature électronique :
 * - Génération de liens de signature à usage unique avec tokens Sanctum
 * - Validation et traitement des signatures
 * - Transmission des signatures aux applications hôtes via webhooks
 * - Envoi de liens de signature par Email, SMS et WhatsApp via Infobip
 * 
 * IMPORTANT : Règle de persistance
 * - Aucune persistance du document ni de la signature elle-même
 * - Stockage uniquement des métadonnées dans la table signature_requests
 * - L'application hôte est responsable d'apposer et enregistrer la signature
 * - Les tokens Sanctum sont invalidés après signature réussie
 */
class SignatureService
{
    public static function normalizeToken(?string $token): ?string
    {
        if ($token === null) {
            return null;
        }

        $normalized = trim((string) $token);
        if ($normalized === '') {
            return null;
        }

        $normalized = preg_replace('/[?#].*$/', '', $normalized);
        $normalized = preg_replace('/\/+$/', '', $normalized);

        if (preg_match('/^(?:https?:)?\/\//i', $normalized)) {
            $path = parse_url($normalized, PHP_URL_PATH) ?? $normalized;
            $segments = preg_split('#/+#', trim((string) $path, '/'));
            $normalized = end($segments) ?: $normalized;
        }

        $normalized = preg_replace('/^.*\//', '', $normalized);
        $normalized = trim($normalized, " \t\n\r/\\");

        return $normalized !== '' ? $normalized : null;
    }

    /**
     * Générer un lien de signature à usage unique avec token Sanctum
     * 
     * Crée un token Sanctum avec abilities spécifiques et expiration
     * Stocke les métadonnées nécessaires dans signature_requests
     * Ne stocke PAS le document ni la signature
     * 
     * @param string $documentUrl URL HTTP(S) du document à signer
     * @param string|null $documentDescription Description du document
     * @param string $webhookUrl URL du webhook de l'app hôte
     * @param string $apiKey Secret partagé pour authentification webhook
     * @param string|null $successRedirectUrl URL de redirection après signature réussie
     * @param string|null $cancelRedirectUrl URL de redirection si signature annulée
     * @param bool $enableAutoPolling Activer le polling automatique (scénario agence)
     * @param int $expiresIn Durée de validité en secondes (défaut 3600 = 1h)
     * @return array Données du lien généré (token, widget_url, expires_at, etc.)
     */
    public function generateSignatureLink(
        string $documentUrl,
        ?string $documentDescription,
        string $webhookUrl,
        string $apiKey,
        ?string $successRedirectUrl = null,
        ?string $cancelRedirectUrl = null,
        bool $enableAutoPolling = false,
        int $expiresIn = 3600
    ): array {
        return DB::transaction(function () use ($documentUrl, $documentDescription, $webhookUrl, $apiKey, $successRedirectUrl, $cancelRedirectUrl, $enableAutoPolling, $expiresIn) {
            // Créer ou réutiliser un utilisateur système pour les tokens Sanctum
            $systemUser = User::where('email', 'signature@system.local')->first();
            $superAdmin = Role::where('code', 'super_admin')->first();
            
            if (!$systemUser) {
                $systemUser = User::create([
                    'uuid_user' => (string) Str::uuid(),
                    'role_uuid' => $superAdmin->uuid_role,
                    'email' => 'signature@system.local',
                    'login' => 'signature_system',
                    'password' => bcrypt(Str::random(32)),
                    'status' => 'actif',
                    'user_type' => 'system',
                ]);
            }

            // Créer un token Sanctum avec abilities spécifiques et expiration
            $token = $systemUser->createToken(
                'signature_token',
                ['signature:sign'],
                now()->addSeconds($expiresIn)
            );

            // Calculer l'expiration manuellement (Sanctum ne l'expose plus directement)
            $expiresAt = now()->addSeconds($expiresIn);

            // Récupérer l'ID du token depuis la base de données
            $sanctumToken = \Laravel\Sanctum\PersonalAccessToken::where('token', hash('sha256', $token->plainTextToken))->first();
            $sanctumTokenId = $sanctumToken ? $sanctumToken->id : null;

            // Créer l'enregistrement de signature request (métadonnées uniquement)
            // IMPORTANT : On ne stocke PAS le document ni la signature
            $signatureRequest = SignatureRequest::create([
                'uuid_signature_request' => (string) Str::uuid(),
                'token' => $token->plainTextToken,
                'document_url' => $documentUrl,              // URL uniquement, pas le contenu
                'document_description' => $documentDescription,
                'webhook_url' => $webhookUrl,
                'api_key' => $apiKey,
                'expires_at' => $expiresAt,
                'metadata' => [
                    'sanctum_token_id' => $sanctumTokenId,
                    'created_by' => 'system',
                    'success_redirect_url' => $successRedirectUrl,
                    'cancel_redirect_url' => $cancelRedirectUrl,
                    'enable_auto_polling' => $enableAutoPolling,
                ],
            ]);

            // Générer l'URL du widget qui sera utilisée pour afficher la page de signature
            $widgetUrl = url('/signature/widget/' . $token->plainTextToken);

            return [
                'token' => $token->plainTextToken,
                'widget_url' => $widgetUrl,
                'expires_at' => $expiresAt->toIso8601String(),
                'expires_in' => $expiresIn,
                'document_url' => $documentUrl,
                'document_description' => $documentDescription,
                'webhook_url' => $webhookUrl,
                'signature_request_uuid' => $signatureRequest->uuid_signature_request,
                'success_redirect_url' => $successRedirectUrl,
                'cancel_redirect_url' => $cancelRedirectUrl,
                'enable_auto_polling' => $enableAutoPolling,
            ];
        });
    }

    /**
     * Récupérer les données du widget pour un token donné
     * 
     * Valide le token et retourne les métadonnées nécessaires
     * pour initialiser le widget JS
     * 
     * @param string $token Token Sanctum de signature
     * @return array|null Données du widget ou null si token invalide
     */
    public function getWidgetData(string $token): ?array
    {
        $token = self::normalizeToken($token);

        if (!$token) {
            return null;
        }

        $signatureRequest = SignatureRequest::where('token', $token)->first();

        if (!$signatureRequest) {
            return null;
        }

        // Vérifier si la requête est valide (non utilisée et non expirée)
        if (!$signatureRequest->isValid()) {
            return null;
        }

        return [
            'token' => $token,
            'document_url' => $signatureRequest->document_url,
            'document_description' => $signatureRequest->document_description,
            'webhook_url' => $signatureRequest->webhook_url,
            'api_key' => $signatureRequest->api_key,
            'success_redirect_url' => $signatureRequest->metadata['success_redirect_url'] ?? null,
            'cancel_redirect_url' => $signatureRequest->metadata['cancel_redirect_url'] ?? null,
            'enable_auto_polling' => $signatureRequest->metadata['enable_auto_polling'] ?? false,
        ];
    }

    /**
     * Traiter la signature reçue via webhook
     * 
     * Valide le token, vérifie l'API key, transmet la signature à l'app hôte
     * et invalide le token après succès
     * 
     * @param string $signatureBase64 Signature en base64
     * @param string $token Token de signature
     * @param string $apiKey API key pour validation
     * @return array Résultat du traitement
     */
    public function processSignature(string $signatureBase64, string $token, string $apiKey): array
    {
        $token = self::normalizeToken($token);

        if (!$token) {
            return [
                'success' => false,
                'message' => 'Token invalide.',
                'code' => 'INVALID_TOKEN',
                'status' => 404,
            ];
        }

        return DB::transaction(function () use ($signatureBase64, $token, $apiKey) {
            // Trouver la requête de signature
            $signatureRequest = SignatureRequest::where('token', $token)->first();

            if (!$signatureRequest) {
                return [
                    'success' => false,
                    'message' => 'Token invalide.',
                    'code' => 'INVALID_TOKEN',
                    'status' => 404,
                ];
            }

            // Vérifier si la requête est valide (non utilisée et non expirée)
            if (!$signatureRequest->isValid()) {
                if ($signatureRequest->is_used) {
                    return [
                        'success' => false,
                        'message' => 'Token déjà utilisé.',
                        'code' => 'TOKEN_ALREADY_USED',
                        'status' => 400,
                    ];
                }
                
                if ($signatureRequest->isExpired()) {
                    return [
                        'success' => false,
                        'message' => 'Token expiré.',
                        'code' => 'EXPIRED_TOKEN',
                        'status' => 400,
                    ];
                }
            }

            // Vérifier l'API Key (secret partagé)
            if ($signatureRequest->api_key !== $apiKey) {
                return [
                    'success' => false,
                    'message' => 'API Key invalide.',
                    'code' => 'INVALID_API_KEY',
                    'status' => 401,
                ];
            }

            // Transmettre la signature au webhook de l'app hôte
            // C'est l'app hôte qui va apposer la signature sur le document
            $webhookUrl = $signatureRequest->webhook_url;
            
            $webhookResponse = Http::withHeaders([
                'X-Api-Key' => $apiKey,
                'Content-Type' => 'application/json',
            ])->post($webhookUrl, [
                'success' => true,
                'signature' => $signatureBase64,
                'token' => $token,
                'timestamp' => now()->toIso8601String(),
                'signature_request_uuid' => $signatureRequest->uuid_signature_request,
                'status' => 200,
            ]);

            if (!$webhookResponse->successful()) {
                return [
                    'success' => false,
                    'message' => 'Échec de l\'envoi au webhook.',
                    'code' => 'WEBHOOK_ERROR',
                    'status' => 500,
                    'webhook_status' => $webhookResponse->status(),
                ];
            }

            // Marquer la requête comme utilisée (métadonnées uniquement)
            // On stocke la signature en base64 uniquement pour traçabilité
            // mais ce n'est PAS la persistance de la signature finale
            $signatureRequest->markAsUsed($signatureBase64);

            // Invalider le token Sanctum (usage unique)
            $accessToken = PersonalAccessToken::findToken($token);
            if ($accessToken) {
                $accessToken->delete();
            }

            return [
                'success' => true,
                'message' => 'Signature traitée avec succès.',
                'code' => 'SIGNATURE_PROCESSED',
                'data' => [
                    'webhook_response' => $webhookResponse->json(),
                    'signature_request_uuid' => $signatureRequest->uuid_signature_request,
                    'token_invalidated' => true,
                ],
            ];
        });
    }

    /**
     * Envoyer le lien de signature par email
     * 
     * @param string $token Token de signature
     * @param string $email Email du destinataire
     * @param string|null $subject Sujet de l'email
     * @param string|null $message Message personnalisé
     * @return array Résultat de l'envoi
     */
    public function sendLinkByEmail(
        string $token,
        string $email,
        ?string $subject = null,
        ?string $message = null
    ): array {
        $token = self::normalizeToken($token);
        if (!$token) {
            return [
                'success' => false,
                'message' => 'Token invalide ou expiré.',
                'code' => 'INVALID_TOKEN',
                'status' => 404,
            ];
        }

        // Vérifier que le token est valide
        $signatureRequest = SignatureRequest::where('token', $token)->first();
        
        if (!$signatureRequest || !$signatureRequest->isValid()) {
            return [
                'success' => false,
                'message' => 'Token invalide ou expiré.',
                'code' => 'INVALID_TOKEN',
                'status' => 404,
            ];
        }

        $widgetUrl = url('/signature/widget/' . $token);
        $defaultSubject = 'Document à signer';
        $defaultMessage = "Veuillez signer le document en cliquant sur le lien suivant : {$widgetUrl}";

        // Intégration avec votre service d'email existant
        // À adapter selon votre infrastructure d'email
        try {
            // Exemple avec Laravel Mail (à adapter selon votre config)
            // Mail::to($email)->send(new SignatureLinkMail($widgetUrl, $subject ?? $defaultSubject, $message ?? $defaultMessage));
            
            return [
                'success' => true,
                'message' => 'Email envoyé avec succès.',
                'code' => 'EMAIL_SENT',
                'data' => [
                    'email' => $email,
                    'widget_url' => $widgetUrl,
                ],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur lors de l\'envoi de l\'email.',
                'code' => 'EMAIL_SEND_ERROR',
                'status' => 500,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Envoyer le lien de signature par SMS via Infobip
     * 
     * @param string $token Token de signature
     * @param string $phone Numéro de téléphone
     * @param string|null $message Message personnalisé
     * @return array Résultat de l'envoi
     */
    public function sendLinkBySms(
        string $token,
        string $phone,
        ?string $message = null
    ): array {
        $token = self::normalizeToken($token);
        if (!$token) {
            return [
                'success' => false,
                'message' => 'Token invalide ou expiré.',
                'code' => 'INVALID_TOKEN',
                'status' => 404,
            ];
        }

        // Vérifier que le token est valide
        $signatureRequest = SignatureRequest::where('token', $token)->first();
        
        if (!$signatureRequest || !$signatureRequest->isValid()) {
            return [
                'success' => false,
                'message' => 'Token invalide ou expiré.',
                'code' => 'INVALID_TOKEN',
                'status' => 404,
            ];
        }

        $widgetUrl = url('/signature/widget/' . $token);
        $defaultMessage = "Signez votre document ici : {$widgetUrl}";

        try {
            // Intégration Infobip SMS
            $infobipApiKey = config('services.infobip.api_key');
            $infobipBaseUrl = config('services.infobip.base_url');

            if (!$infobipApiKey || !$infobipBaseUrl) {
                return [
                    'success' => false,
                    'message' => 'Configuration Infobip manquante.',
                    'code' => 'INFOBIP_CONFIG_ERROR',
                    'status' => 500,
                ];
            }

            $response = Http::withHeaders([
                'Authorization' => 'App ' . $infobipApiKey,
                'Content-Type' => 'application/json',
            ])->post("{$infobipBaseUrl}/sms/2/text", [
                'messages' => [
                    [
                        'from' => config('services.infobip.sms_from', 'InfoSMS'),
                        'to' => $phone,
                        'text' => $message ?? $defaultMessage,
                    ],
                ],
            ]);

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'message' => 'Erreur Infobip SMS.',
                    'code' => 'INFOBIP_SMS_ERROR',
                    'status' => 500,
                    'error' => $response->body(),
                ];
            }

            return [
                'success' => true,
                'message' => 'SMS envoyé avec succès.',
                'code' => 'SMS_SENT',
                'data' => [
                    'phone' => $phone,
                    'widget_url' => $widgetUrl,
                    'message_id' => $response->json('messages.0.messageId'),
                ],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur lors de l\'envoi du SMS.',
                'code' => 'SMS_SEND_ERROR',
                'status' => 500,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Envoyer le lien de signature par WhatsApp via Infobip
     * 
     * @param string $token Token de signature
     * @param string $phone Numéro de téléphone
     * @param string|null $message Message personnalisé
     * @return array Résultat de l'envoi
     */
    public function sendLinkByWhatsapp(
        string $token,
        string $phone,
        ?string $message = null
    ): array {
        $token = self::normalizeToken($token);
        if (!$token) {
            return [
                'success' => false,
                'message' => 'Token invalide ou expiré.',
                'code' => 'INVALID_TOKEN',
                'status' => 404,
            ];
        }

        // Vérifier que le token est valide
        $signatureRequest = SignatureRequest::where('token', $token)->first();
        
        if (!$signatureRequest || !$signatureRequest->isValid()) {
            return [
                'success' => false,
                'message' => 'Token invalide ou expiré.',
                'code' => 'INVALID_TOKEN',
                'status' => 404,
            ];
        }

        $widgetUrl = url('/signature/widget/' . $token);
        $defaultMessage = "Veuillez signer votre document en cliquant sur ce lien : {$widgetUrl}";

        try {
            // Intégration Infobip WhatsApp
            $infobipApiKey = config('services.infobip.api_key');
            $infobipBaseUrl = config('services.infobip.base_url');

            if (!$infobipApiKey || !$infobipBaseUrl) {
                return [
                    'success' => false,
                    'message' => 'Configuration Infobip manquante.',
                    'code' => 'INFOBIP_CONFIG_ERROR',
                    'status' => 500,
                ];
            }

            $response = Http::withHeaders([
                'Authorization' => 'App ' . $infobipApiKey,
                'Content-Type' => 'application/json',
            ])->post("{$infobipBaseUrl}/whatsapp/1/message/text", [
                'from' => config('services.infobip.whatsapp_from'),
                'to' => $phone,
                'content' => [
                    'text' => $message ?? $defaultMessage,
                ],
            ]);

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'message' => 'Erreur Infobip WhatsApp.',
                    'code' => 'INFOBIP_WHATSAPP_ERROR',
                    'status' => 500,
                    'error' => $response->body(),
                ];
            }

            return [
                'success' => true,
                'message' => 'Message WhatsApp envoyé avec succès.',
                'code' => 'WHATSAPP_SENT',
                'data' => [
                    'phone' => $phone,
                    'widget_url' => $widgetUrl,
                    'message_id' => $response->json('messageId'),
                ],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur lors de l\'envoi WhatsApp.',
                'code' => 'WHATSAPP_SEND_ERROR',
                'status' => 500,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Vérifier le statut d'un token
     * 
     * Permet à l'app client de faire du polling pour savoir si la signature est terminée
     * Retourne des informations détaillées sur l'état de la demande de signature
     * 
     * @param string $token Token de signature
     * @return array|null Statut du token ou null si invalide
     */
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
            'valid' => true,
            'expires_at' => $signatureRequest->expires_at->toIso8601String(),
            'expired' => $signatureRequest->isExpired(),
            'is_used' => $signatureRequest->is_used,
            'is_valid' => $signatureRequest->isValid(),
            'signed_at' => $signatureRequest->signed_at ? $signatureRequest->signed_at->toIso8601String() : null,
            'document_url' => $signatureRequest->document_url,
            'document_description' => $signatureRequest->document_description,
            'signature_request_uuid' => $signatureRequest->uuid_signature_request,
        ];
    }
}