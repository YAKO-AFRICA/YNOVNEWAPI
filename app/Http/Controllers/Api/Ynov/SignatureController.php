<?php

namespace App\Http\Controllers\Api\Ynov;

use App\Http\Controllers\Controller;
use App\Models\Api\Ynov\parameter\User;
use App\Services\Api\Ynov\Auth\OtpService;
use App\Services\Api\Ynov\SignatureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
// use Laravel\Sanctum\PersonalAccessToken;
/**
 * Contrôleur du Widget de Signature Électronique — YAKOA AFRICASSUR
 *
 * Endpoints :
 *   POST /api/v1/signature/generate-link          (app hôte, à protéger par auth)
 *   POST /api/v1/signature/webhook                (widget -> Laravel, authentifié par le token)
 *   GET  /api/v1/signature/token/{token}/status   (polling desktop)
 *   POST /api/v1/signature/send/{email|sms|whatsapp}
 *   GET  /signature/widget/{token}                (page du widget)
 *
 * Endpoints SUPPRIMÉS par rapport à la version précédente :
 *   - POST /mark-token-used        -> inutile, Laravel marque lui-même le token
 *   - GET  /mark-token-used/{token} -> GET non authentifié, invalidable par un simple prefetch
 */
// class SignatureController extends Controller
// {
//     public function __construct(
//         private SignatureService $signatureService
//     ) {}
 
//     // =====================================================================
//     // GÉNÉRATION DU LIEN
//     // =====================================================================
 
//     /**
//      * @bodyParam document_url string nullable URL HTTP(S) du document à signer
//      * @bodyParam document_description string nullable Description (max 500)
//      * @bodyParam webhook_url string required Webhook de l'app hôte
//      * @bodyParam api_key string required Secret partagé (reste côté serveur)
//      * @bodyParam success_redirect_url string nullable
//      * @bodyParam cancel_redirect_url string nullable
//      * @bodyParam enable_auto_polling boolean nullable
//      * @bodyParam expires_in int nullable Secondes (60 à 86400, défaut 3600)
//      */
//     public function generateLink(Request $request): JsonResponse
//     {
//         try {
//             $validated = $request->validate([
//                 'document_url'         => ['nullable', 'url:http,https'],
//                 'document_description' => ['nullable', 'string', 'max:500'],
//                 'webhook_url'          => ['required', 'url:https'],
//                 'api_key'              => ['required', 'string', 'min:16', 'max:255'],
//                 'success_redirect_url' => ['nullable', 'url:http,https'],
//                 'cancel_redirect_url'  => ['nullable', 'url:http,https'],
//                 'enable_auto_polling'  => ['nullable', 'boolean'],
//                 'expires_in'           => ['nullable', 'integer', 'min:60', 'max:86400'],
//             ]);
 
//             $result = $this->signatureService->generateSignatureLink(
//                 $validated['document_url'] ?? null,
//                 $validated['document_description'] ?? null,
//                 $validated['webhook_url'],
//                 $validated['api_key'],
//                 $validated['success_redirect_url'] ?? null,
//                 $validated['cancel_redirect_url'] ?? null,
//                 $validated['enable_auto_polling'] ?? false,
//                 $validated['expires_in'] ?? SignatureService::DEFAULT_EXPIRES_IN
//             );
 
//             return response()->json([
//                 'success' => true,
//                 'message' => 'Lien de signature généré avec succès.',
//                 'code'    => 'SIGNATURE_LINK_GENERATED',
//                 'data'    => $result,
//             ], 201);
//         } catch (ValidationException $e) {
//             return $this->validationError($e);
//         } catch (\Throwable $e) {
//             return $this->serverError($e, 'SIGNATURE_LINK_ERROR', 'Erreur lors de la génération du lien.');
//         }
//     }
 
//     // =====================================================================
//     // PAGE DU WIDGET
//     // =====================================================================
 
//     /**
//      * Sert la page du widget.
//      *
//      * Aucun paramètre n'est lu depuis la query string : la configuration provient
//      * exclusivement des métadonnées du token. Ni webhook_url ni api_key ne sont exposés.
//      */
//     public function serveWidget(string $token)
//     {
//         try {
//             $widgetData = $this->signatureService->getWidgetData($token);
 
//             if (!$widgetData) {
//                 return response()->view('signature.invalid', [], 404);
//             }
 
//             return view('signature.widget', [
//                 'token'                => $widgetData['token'],
//                 'document_url'         => $widgetData['document_url'],
//                 'document_description' => $widgetData['document_description'] ?: 'Document à signer',
//                 'success_redirect_url' => $widgetData['success_redirect_url'],
//                 'cancel_redirect_url'  => $widgetData['cancel_redirect_url'],
//                 'enable_auto_polling'  => $widgetData['enable_auto_polling'],
//             ]);
//         } catch (\Throwable $e) {
//             Log::error('[Signature] Erreur de chargement du widget', ['message' => $e->getMessage()]);
 
//             return response()->view('signature.invalid', [], 500);
//         }
//     }
 
//     // =====================================================================
//     // RÉCEPTION DE LA SIGNATURE
//     // =====================================================================
 
//     /**
//      * Reçoit la signature depuis le widget, la relaie à l'app hôte et consomme le token.
//      *
//      * Authentification : le token lui-même (imprévisible, usage unique, expirant).
//      * Aucun header X-Api-Key n'est attendu — un secret rendu dans le navigateur
//      * n'authentifie plus rien.
//      *
//      * @bodyParam token string required
//      * @bodyParam signature string required data:image/png;base64,...
//      */
//     public function receiveSignature(Request $request): JsonResponse
//     {
//         try {
//             $validated = $request->validate([
//                 'token'     => ['required', 'string'],
//                 'signature' => ['required', 'string'],
//             ]);
 
//             $result = $this->signatureService->processSignature(
//                 $validated['signature'],
//                 $validated['token']
//             );
 
//             if (!$result['success']) {
//                 return response()->json([
//                     'success' => false,
//                     'message' => $result['message'],
//                     'code'    => $result['code'],
//                     'data'    => $result['data'] ?? null,
//                 ], $result['status'] ?? 400);
//             }
 
//             return response()->json([
//                 'success' => true,
//                 'message' => $result['message'],
//                 'code'    => $result['code'],
//                 'data'    => $result['data'],
//             ]);
//         } catch (ValidationException $e) {
//             return $this->validationError($e);
//         } catch (\Throwable $e) {
//             return $this->serverError($e, 'SIGNATURE_PROCESS_ERROR', 'Erreur lors du traitement de la signature.');
//         }
//     }
 
//     // =====================================================================
//     // STATUT (polling)
//     // =====================================================================
 
//     public function checkTokenStatus(string $token): JsonResponse
//     {
//         try {
//             $status = $this->signatureService->checkTokenStatus($token);
 
//             if (!$status) {
//                 return response()->json([
//                     'success' => false,
//                     'message' => 'Token invalide.',
//                     'code'    => 'INVALID_TOKEN',
//                 ], 404);
//             }
 
//             return response()->json([
//                 'success' => true,
//                 'message' => 'Statut du token.',
//                 'code'    => 'TOKEN_STATUS',
//                 'data'    => $status,
//             ])->header('Cache-Control', 'no-store');
//         } catch (\Throwable $e) {
//             return $this->serverError($e, 'TOKEN_CHECK_ERROR', 'Erreur lors de la vérification du token.');
//         }
//     }
 
//     // =====================================================================
//     // ENVOI DU LIEN
//     // =====================================================================
 
//     public function sendByEmail(Request $request): JsonResponse
//     {
//         return $this->dispatchSend($request, [
//             'token'   => ['required', 'string'],
//             'email'   => ['required', 'email'],
//             'subject' => ['nullable', 'string', 'max:200'],
//             'message' => ['nullable', 'string', 'max:1000'],
//         ], fn (array $v) => $this->signatureService->sendLinkByEmail(
//             $v['token'], $v['email'], $v['subject'] ?? null, $v['message'] ?? null
//         ), 'EMAIL_SEND_ERROR');
//     }
 
//     public function sendBySms(Request $request): JsonResponse
//     {
//         return $this->dispatchSend($request, [
//             'token'   => ['required', 'string'],
//             'phone'   => ['required', 'string', 'max:20'],
//             'message' => ['nullable', 'string', 'max:160'],
//         ], fn (array $v) => $this->signatureService->sendLinkBySms(
//             $v['token'], $v['phone'], $v['message'] ?? null
//         ), 'SMS_SEND_ERROR');
//     }
 
//     public function sendByWhatsapp(Request $request): JsonResponse
//     {
//         return $this->dispatchSend($request, [
//             'token'   => ['required', 'string'],
//             'phone'   => ['required', 'string', 'max:20'],
//             'message' => ['nullable', 'string', 'max:1000'],
//         ], fn (array $v) => $this->signatureService->sendLinkByWhatsapp(
//             $v['token'], $v['phone'], $v['message'] ?? null
//         ), 'WHATSAPP_SEND_ERROR');
//     }
 
//     private function dispatchSend(Request $request, array $rules, callable $handler, string $errorCode): JsonResponse
//     {
//         try {
//             $result = $handler($request->validate($rules));
 
//             if (!$result['success']) {
//                 return response()->json([
//                     'success' => false,
//                     'message' => $result['message'],
//                     'code'    => $result['code'],
//                 ], $result['status'] ?? 400);
//             }
 
//             return response()->json([
//                 'success' => true,
//                 'message' => $result['message'],
//                 'code'    => $result['code'],
//                 'data'    => $result['data'],
//             ]);
//         } catch (ValidationException $e) {
//             return $this->validationError($e);
//         } catch (\Throwable $e) {
//             return $this->serverError($e, $errorCode, "Erreur lors de l'envoi.");
//         }
//     }
 
//     // =====================================================================
//     // RÉPONSES D'ERREUR
//     // =====================================================================
 
//     private function validationError(ValidationException $e): JsonResponse
//     {
//         return response()->json([
//             'success' => false,
//             'message' => 'Erreur de validation.',
//             'code'    => 'VALIDATION_ERROR',
//             'errors'  => $e->errors(),
//         ], 422);
//     }
 
//     /** Le détail technique n'est renvoyé qu'en mode debug. */
//     private function serverError(\Throwable $e, string $code, string $message): JsonResponse
//     {
//         Log::error('[Signature] ' . $code, [
//             'message' => $e->getMessage(),
//             'file'    => $e->getFile() . ':' . $e->getLine(),
//         ]);
 
//         return response()->json(array_filter([
//             'success' => false,
//             'message' => $message,
//             'code'    => $code,
//             'error'   => config('app.debug') ? $e->getMessage() : null,
//         ], fn ($v) => $v !== null), 500);
//     }
// }

class SignatureController extends Controller
{
    public function __construct(
        private SignatureService $signatureService,
        private OtpService $otpService
    ) {}

    // =====================================================================
    // GÉNÉRATION DU LIEN
    // =====================================================================

    public function generateLink(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'document_url'         => ['nullable', 'url:http,https'],
                'document_description' => ['nullable', 'string', 'max:500'],
                'webhook_url'          => ['required', 'url:https'],
                'api_key'              => ['required', 'string', 'min:16', 'max:255'],
                'success_redirect_url' => ['nullable', 'url:http,https'],
                'cancel_redirect_url'  => ['nullable', 'url:http,https'],
                'enable_auto_polling'  => ['nullable', 'boolean'],
                'expires_in'           => ['nullable', 'integer', 'min:60', 'max:86400'],

                // Champs OTP (optionnels)
                'signer_login'         => ['nullable', 'string', 'max:100'],
                'signer_user_uuid'     => ['nullable', 'uuid'],
                'signer_email'         => ['nullable', 'email'],
                'signer_phone'         => ['nullable', 'string', 'max:20'],
                'otp_purpose'          => ['nullable', 'string', 'max:120'],
                'otp_qr_url_template'  => ['nullable', 'url:http,https', 'max:2000'],
            ]);

            $result = $this->signatureService->generateSignatureLink(
                $validated['document_url'] ?? null,
                $validated['document_description'] ?? null,
                $validated['webhook_url'],
                $validated['api_key'],
                $validated['success_redirect_url'] ?? null,
                $validated['cancel_redirect_url'] ?? null,
                $validated['enable_auto_polling'] ?? false,
                $validated['expires_in'] ?? SignatureService::DEFAULT_EXPIRES_IN,
                [
                    'signer_login'         => $validated['signer_login']        ?? null,
                    'signer_user_uuid'     => $validated['signer_user_uuid']    ?? null,
                    'signer_email'         => $validated['signer_email']        ?? null,
                    'signer_phone'         => $validated['signer_phone']        ?? null,
                    'otp_purpose'          => $validated['otp_purpose']         ?? null,
                    'otp_qr_url_template'  => $validated['otp_qr_url_template'] ?? null,
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Lien de signature généré avec succès.',
                'code'    => 'SIGNATURE_LINK_GENERATED',
                'data'    => $result,
            ], 201);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'SIGNATURE_LINK_ERROR', 'Erreur lors de la génération du lien.');
        }
    }

    // =====================================================================
    // PAGE DU WIDGET
    // =====================================================================

    public function serveWidget(string $token)
    {
        try {
            $widgetData = $this->signatureService->getWidgetData($token);

            if (!$widgetData) {
                return response()->view('signature.invalid', [], 404);
            }

            return view('signature.widget', [
                'token'                => $widgetData['token'],
                'document_url'         => $widgetData['document_url'],
                'document_description' => $widgetData['document_description'] ?: 'Document à signer',
                'success_redirect_url' => $widgetData['success_redirect_url'],
                'cancel_redirect_url'  => $widgetData['cancel_redirect_url'],
                'enable_auto_polling'  => $widgetData['enable_auto_polling'],
                // Champs OTP
                'signer_login'         => $widgetData['signer_login'],
                'signer_user_uuid'     => $widgetData['signer_user_uuid'],
                'signer_email'         => $widgetData['signer_email'],
                'signer_phone'         => $widgetData['signer_phone'],
                'otp_purpose'          => $widgetData['otp_purpose'],
                'otp_qr_url_template'  => $widgetData['otp_qr_url_template'],
            ]);
        } catch (\Throwable $e) {
            Log::error('[Signature] Erreur de chargement du widget', ['message' => $e->getMessage()]);

            return response()->view('signature.invalid', [], 500);
        }
    }

    // =====================================================================
    // RÉCEPTION DE LA SIGNATURE
    // =====================================================================

    public function receiveSignature(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'token'     => ['required', 'string'],
                'signature' => ['required', 'string'],
                'method'    => ['nullable', 'string', 'in:otp_qr,handwritten'],
                'otp'       => ['nullable', 'array'],
                'otp.channel'    => ['nullable', 'string', 'in:sms,email,whatsapp'],
                'otp.contact'    => ['nullable', 'string', 'max:255'],
                'otp.purpose'    => ['nullable', 'string', 'max:120'],
                'otp.ip_address' => ['nullable', 'string', 'max:45'],
                'otp.user_agent' => ['nullable', 'string', 'max:500'],
                'otp.used_at'    => ['nullable', 'string', 'max:40'],
                'geo'            => ['nullable', 'array'],
                'geo.lat'        => ['nullable', 'numeric', 'between:-90,90'],
                'geo.lng'        => ['nullable', 'numeric', 'between:-180,180'],
            ]);

            $context = [
                'method' => $validated['method'] ?? 'handwritten',
                'otp'    => $validated['otp'] ?? [],
                'geo'    => $validated['geo'] ?? [],
            ];

            $result = $this->signatureService->processSignature(
                $validated['signature'],
                $validated['token'],
                $context
            );

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                    'code'    => $result['code'],
                    'data'    => $result['data'] ?? null,
                ], $result['status'] ?? 400);
            }

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'code'    => $result['code'],
                'data'    => $result['data'],
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'SIGNATURE_PROCESS_ERROR', 'Erreur lors du traitement de la signature.');
        }
    }

    // =====================================================================
    // VÉRIFICATION OTP (passerelle signature)
    // =====================================================================

    /**
     * Vérifie un code OTP dans le contexte signature.
     *
     * Réutilise OtpService::verify() à 100 %, mais renvoie des données
     * autoritaires (IP, User-Agent, used_at) récupérées côté serveur.
     *
     * @bodyParam code string required Code à 6 chiffres
     * @bodyParam purpose string required
     * @bodyParam login string nullable
     * @bodyParam user_uuid string nullable
     * @bodyParam channel string required sms|email|whatsapp
     * @bodyParam contact string required Email ou téléphone utilisé
     */
    public function verifySignatureOtp(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'code'      => ['required', 'string', 'size:6', 'regex:/^[0-9]{6}$/'],
                'purpose'   => ['required', 'string', 'max:120'],
                'login'     => ['nullable', 'string', 'max:100'],
                'user_uuid' => ['nullable', 'uuid'],
                'channel'   => ['required', 'string', 'in:sms,email,whatsapp'],
                'contact'   => ['required', 'string', 'max:255'],
            ]);

            // Résolution utilisateur — même logique que OtpController::resolveUser
            $user = null;
            if (!empty($validated['user_uuid'])) {
                $user = User::where('uuid_user', $validated['user_uuid'])->first();
            } elseif (!empty($validated['login'])) {
                $user = User::where('login', $validated['login'])->first();
            }

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'code'    => 'OTP_INVALID',
                    'message' => 'Code OTP invalide ou expiré.',
                ], 422);
            }

            // Réutilisation TOTALE du service existant
            if (!$this->otpService->verify($user, $validated['code'], $validated['purpose'])) {
                return response()->json([
                    'success' => false,
                    'code'    => 'OTP_INVALID',
                    'message' => 'Code OTP invalide ou expiré.',
                ], 422);
            }

            // Récupération de l'enregistrement qui vient d'être consommé
            $otp = $this->otpService->getOtpByUser(
                $user,
                $validated['purpose'],
                $validated['channel']
            );

            // Charger uniquement les détails utilisateur
            $user->load(['details']);

            // Construire les données simplifiées
            $enrichedData = [
                // Identité de base
                'user_uuid' => $user->uuid_user,
                'login'     => $user->login,
                'email'     => $user->email,
                
                // Informations personnelles essentielles
                'nom'       => $user->details?->nom,
                'prenoms'   => $user->details?->prenoms,
                'mobile_1'  => $user->details?->mobile_1,
                
                // Adresse
                'adresse_complete' => $user->details?->adresse_complete,
                
                // Données techniques autoritaires
                'channel'    => $otp?->channel ?? $validated['channel'],
                'contact'    => $validated['contact'],
                'purpose'    => $this->otpService->normalizePurpose($validated['purpose']),
                'ip_address' => $request->ip(),          // autoritaire
                'user_agent' => $request->userAgent(),   // autoritaire
                'used_at'    => optional($otp?->used_at)->toIso8601String()
                                ?? now()->toIso8601String(),
            ];

            return response()->json([
                'success' => true,
                'code'    => 'OTP_VERIFIED',
                'message' => 'Code OTP vérifié.',
                'data'    => $enrichedData,
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'OTP_VERIFY_ERROR', 'Erreur lors de la vérification OTP.');
        }
    }

    // =====================================================================
    // STATUT (polling)
    // =====================================================================

    public function checkTokenStatus(string $token): JsonResponse
    {
        try {
            $status = $this->signatureService->checkTokenStatus($token);

            if (!$status) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token invalide.',
                    'code'    => 'INVALID_TOKEN',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Statut du token.',
                'code'    => 'TOKEN_STATUS',
                'data'    => $status,
            ])->header('Cache-Control', 'no-store');
        } catch (\Throwable $e) {
            return $this->serverError($e, 'TOKEN_CHECK_ERROR', 'Erreur lors de la vérification du token.');
        }
    }

    // =====================================================================
    // ENVOI DU LIEN
    // =====================================================================

    public function sendByEmail(Request $request): JsonResponse
    {
        return $this->dispatchSend($request, [
            'token'   => ['required', 'string'],
            'email'   => ['required', 'email'],
            'subject' => ['nullable', 'string', 'max:200'],
            'message' => ['nullable', 'string', 'max:1000'],
        ], fn (array $v) => $this->signatureService->sendLinkByEmail(
            $v['token'], $v['email'], $v['subject'] ?? null, $v['message'] ?? null
        ), 'EMAIL_SEND_ERROR');
    }

    public function sendBySms(Request $request): JsonResponse
    {
        return $this->dispatchSend($request, [
            'token'   => ['required', 'string'],
            'phone'   => ['required', 'string', 'max:20'],
            'message' => ['nullable', 'string', 'max:160'],
        ], fn (array $v) => $this->signatureService->sendLinkBySms(
            $v['token'], $v['phone'], $v['message'] ?? null
        ), 'SMS_SEND_ERROR');
    }

    public function sendByWhatsapp(Request $request): JsonResponse
    {
        return $this->dispatchSend($request, [
            'token'   => ['required', 'string'],
            'phone'   => ['required', 'string', 'max:20'],
            'message' => ['nullable', 'string', 'max:1000'],
        ], fn (array $v) => $this->signatureService->sendLinkByWhatsapp(
            $v['token'], $v['phone'], $v['message'] ?? null
        ), 'WHATSAPP_SEND_ERROR');
    }

    private function dispatchSend(Request $request, array $rules, callable $handler, string $errorCode): JsonResponse
    {
        try {
            $result = $handler($request->validate($rules));

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                    'code'    => $result['code'],
                ], $result['status'] ?? 400);
            }

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'code'    => $result['code'],
                'data'    => $result['data'],
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        } catch (\Throwable $e) {
            return $this->serverError($e, $errorCode, "Erreur lors de l'envoi.");
        }
    }

    // =====================================================================
    // RÉPONSES D'ERREUR
    // =====================================================================

    private function validationError(ValidationException $e): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Erreur de validation.',
            'code'    => 'VALIDATION_ERROR',
            'errors'  => $e->errors(),
        ], 422);
    }

    private function serverError(\Throwable $e, string $code, string $message): JsonResponse
    {
        Log::error('[Signature] ' . $code, [
            'message' => $e->getMessage(),
            'file'    => $e->getFile() . ':' . $e->getLine(),
        ]);

        return response()->json(array_filter([
            'success' => false,
            'message' => $message,
            'code'    => $code,
            'error'   => config('app.debug') ? $e->getMessage() : null,
        ], fn ($v) => $v !== null), 500);
    }
}