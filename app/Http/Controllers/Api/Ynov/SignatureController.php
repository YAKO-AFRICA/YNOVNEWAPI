<?php

namespace App\Http\Controllers\Api\Ynov;

use App\Http\Controllers\Controller;
use App\Services\Api\Ynov\SignatureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Contrôleur pour le Widget de Signature Électronique
 * 
 * Ce contrôleur gère :
 * - La génération de liens de signature à usage unique avec tokens Sanctum
 * - Le service de la page du widget avec les paramètres liés au token
 * - La réception des signatures via webhook authentifié
 * - L'envoi de liens de signature par Email, SMS et WhatsApp via Infobip
 * - La vérification du statut des tokens
 * 
 * IMPORTANT : Aucune persistance du document ni de la signature
 * - Le widget frontend ne stocke rien (tout en mémoire)
 * - Le backend ne stocke que les métadonnées des tokens dans signature_requests
 * - L'application hôte est responsable d'apposer et enregistrer la signature
 */
class SignatureController extends Controller
{
    public function __construct(
        private SignatureService $signatureService
    ) {}

    /**
     * Générer un lien de signature à usage unique
     * 
     * Crée un token Sanctum avec expiration et stocke les métadonnées
     * Le lien généré peut être utilisé pour afficher le widget ou envoyé par Email/SMS/WhatsApp
     * 
     * @param Request $request
     * @return JsonResponse
     * 
     * @bodyParam document_url string nullable URL HTTP(S) du document à signer
     * @bodyParam document_description string nullable Description du document (max 500 caractères)
     * @bodyParam webhook_url string required URL du webhook de l'app hôte pour recevoir la signature
     * @bodyParam api_key string required Secret partagé pour authentification webhook
     * @bodyParam success_redirect_url string nullable URL de redirection après signature réussie
     * @bodyParam cancel_redirect_url string nullable URL de redirection si signature annulée
     * @bodyParam enable_auto_polling boolean nullable Activer le polling automatique (scénario agence grand écran)
     * @bodyParam expires_in int nullable Durée de validité en secondes (max 86400 = 24h, défaut 3600 = 1h)
     * 
     * @response 201 {"success":true,"message":"Lien de signature généré avec succès.","code":"SIGNATURE_LINK_GENERATED","data":{"token":"...","widget_url":"...","expires_at":"...","expires_in":3600}}
     */
    public function generateLink(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'document_url' => ['nullable', 'url'],
                'document_description' => ['nullable', 'string', 'max:500'],
                'webhook_url' => ['required', 'url'],
                'api_key' => ['required', 'string'],
                'success_redirect_url' => ['nullable', 'url'],
                'cancel_redirect_url' => ['nullable', 'url'],
                'enable_auto_polling' => ['nullable', 'boolean'],
                'expires_in' => ['nullable', 'integer', 'min:1', 'max:86400'], // Max 24h
            ]);

            $result = $this->signatureService->generateSignatureLink(
                $validated['document_url'],
                $validated['document_description'] ?? null,
                $validated['webhook_url'],
                $validated['api_key'],
                $validated['success_redirect_url'] ?? null,
                $validated['cancel_redirect_url'] ?? null,
                $validated['enable_auto_polling'] ?? false,
                $validated['expires_in'] ?? 3600 // 1h par défaut
            );

            return response()->json([
                'success' => true,
                'message' => 'Lien de signature généré avec succès.',
                'code' => 'SIGNATURE_LINK_GENERATED',
                'data' => $result,
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation.',
                'errors' => $e->errors(),
                'code' => 'VALIDATION_ERROR',
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du lien de signature.',
                'code' => 'SIGNATURE_LINK_ERROR',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Servir la page du widget avec le token
     * 
     * Cette route web affiche la page du widget avec les paramètres liés au token
     * Le widget JS est initialisé avec les données du token
     * 
     * @param string $token Token Sanctum de signature
     * @return \Illuminate\View\View|\Illuminate\Http\JsonResponse
     */
    public function serveWidget(string $token)
    {
        try {
            $widgetData = $this->signatureService->getWidgetData($token);

            if (!$widgetData) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token invalide ou expiré.',
                    'code' => 'INVALID_TOKEN',
                ], 404);
            }

            // Récupérer les paramètres depuis l'URL ou les métadonnées du token
            $documentUrl = request()->query('document_url', $widgetData['document_url'] ?? '');
            $documentDescription = request()->query('document_description', $widgetData['document_description'] ?? 'Document à signer');
            $webhookUrl = request()->query('webhook_url', $widgetData['webhook_url'] ?? '');
            $apiKey = request()->query('api_key', $widgetData['api_key'] ?? '');
            $successRedirectUrl = request()->query('success_redirect_url', $widgetData['success_redirect_url'] ?? '');
            $cancelRedirectUrl = request()->query('cancel_redirect_url', $widgetData['cancel_redirect_url'] ?? '');
            $enableAutoPolling = request()->query('enable_auto_polling', $widgetData['enable_auto_polling'] ?? false);

            // Retourner la vue avec les données du widget
            return view('signature.widget', [
                'token' => $token,
                'document_url' => $documentUrl,
                'document_description' => $documentDescription,
                'webhook_url' => $webhookUrl,
                'api_key' => $apiKey,
                'success_redirect_url' => $successRedirectUrl,
                'cancel_redirect_url' => $cancelRedirectUrl,
                'enable_auto_polling' => $enableAutoPolling,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement du widget.',
                'code' => 'WIDGET_LOAD_ERROR',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Webhook pour recevoir la signature
     * 
     * Reçoit la signature en base64 depuis le widget JS et la transmet à l'app hôte
     * Authentifié via header X-Api-Key (secret partagé)
     * Invalide le token après signature réussie
     * 
     * @param Request $request
     * @return JsonResponse
     * 
     * @bodyParam signature string required Signature en base64 (data:image/png;base64,...)
     * @bodyParam token string required Token de signature
     * 
     * @header X-Api-Key Secret partagé pour authentification
     */
    public function receiveSignature(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'signature' => ['required', 'string'],
                'token' => ['required', 'string'],
            ]);

            // Vérifier l'authentification via X-Api-Key
            $apiKey = $request->header('X-Api-Key');
            if (!$apiKey) {
                return response()->json([
                    'success' => false,
                    'message' => 'API Key manquante.',
                    'code' => 'MISSING_API_KEY',
                ], 401);
            }

            $result = $this->signatureService->processSignature(
                $validated['signature'],
                $validated['token'],
                $apiKey
            );

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                    'code' => $result['code'],
                ], $result['status'] ?? 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Signature reçue avec succès.',
                'code' => 'SIGNATURE_RECEIVED',
                'data' => $result['data'],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation.',
                'errors' => $e->errors(),
                'code' => 'VALIDATION_ERROR',
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du traitement de la signature.',
                'code' => 'SIGNATURE_PROCESS_ERROR',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Envoyer le lien de signature par Email
     * 
     * @param Request $request
     * @return JsonResponse
     * 
     * @bodyParam token string required Token de signature
     * @bodyParam email string required Email du destinataire
     * @bodyParam subject string nullable Sujet de l'email (max 200 caractères)
     * @bodyParam message string nullable Message personnalisé (max 1000 caractères)
     */
    public function sendByEmail(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'token' => ['required', 'string'],
                'email' => ['required', 'email'],
                'subject' => ['nullable', 'string', 'max:200'],
                'message' => ['nullable', 'string', 'max:1000'],
            ]);

            $result = $this->signatureService->sendLinkByEmail(
                $validated['token'],
                $validated['email'],
                $validated['subject'] ?? null,
                $validated['message'] ?? null
            );

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                    'code' => $result['code'],
                ], $result['status'] ?? 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Lien de signature envoyé par email avec succès.',
                'code' => 'EMAIL_SENT',
                'data' => $result['data'],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation.',
                'errors' => $e->errors(),
                'code' => 'VALIDATION_ERROR',
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi de l\'email.',
                'code' => 'EMAIL_SEND_ERROR',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Envoyer le lien de signature par SMS via Infobip
     * 
     * @param Request $request
     * @return JsonResponse
     * 
     * @bodyParam token string required Token de signature
     * @bodyParam phone string required Numéro de téléphone du destinataire
     * @bodyParam message string nullable Message personnalisé (max 160 caractères)
     */
    public function sendBySms(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'token' => ['required', 'string'],
                'phone' => ['required', 'string'],
                'message' => ['nullable', 'string', 'max:160'],
            ]);

            $result = $this->signatureService->sendLinkBySms(
                $validated['token'],
                $validated['phone'],
                $validated['message'] ?? null
            );

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                    'code' => $result['code'],
                ], $result['status'] ?? 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Lien de signature envoyé par SMS avec succès.',
                'code' => 'SMS_SENT',
                'data' => $result['data'],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation.',
                'errors' => $e->errors(),
                'code' => 'VALIDATION_ERROR',
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi du SMS.',
                'code' => 'SMS_SEND_ERROR',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Envoyer le lien de signature par WhatsApp via Infobip
     * 
     * @param Request $request
     * @return JsonResponse
     * 
     * @bodyParam token string required Token de signature
     * @bodyParam phone string required Numéro de téléphone du destinataire
     * @bodyParam message string nullable Message personnalisé (max 1000 caractères)
     */
    public function sendByWhatsapp(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'token' => ['required', 'string'],
                'phone' => ['required', 'string'],
                'message' => ['nullable', 'string', 'max:1000'],
            ]);

            $result = $this->signatureService->sendLinkByWhatsapp(
                $validated['token'],
                $validated['phone'],
                $validated['message'] ?? null
            );

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                    'code' => $result['code'],
                ], $result['status'] ?? 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Lien de signature envoyé par WhatsApp avec succès.',
                'code' => 'WHATSAPP_SENT',
                'data' => $result['data'],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation.',
                'errors' => $e->errors(),
                'code' => 'VALIDATION_ERROR',
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi WhatsApp.',
                'code' => 'WHATSAPP_SEND_ERROR',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Vérifier le statut d'un token
     * 
     * Permet à l'app client de faire du polling pour savoir si la signature est terminée
     * 
     * @param string $token Token de signature
     * @return JsonResponse
     * 
     * @response 200 {"success":true,"code":"TOKEN_STATUS","data":{"valid":true,"expired":false,"is_used":false,"is_valid":true}}
     * @response 200 {"success":true,"code":"TOKEN_STATUS","data":{"valid":true,"expired":false,"is_used":true,"is_valid":false,"signed_at":"2024-09-18T10:30:00Z"}}
     */
    public function checkTokenStatus(string $token): JsonResponse
    {
        try {
            $status = $this->signatureService->checkTokenStatus($token);

            if (!$status) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token invalide.',
                    'code' => 'INVALID_TOKEN',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Statut du token.',
                'code' => 'TOKEN_STATUS',
                'data' => $status,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la vérification du token.',
                'code' => 'TOKEN_CHECK_ERROR',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}