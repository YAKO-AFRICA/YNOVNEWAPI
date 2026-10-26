<?php

namespace App\Http\Controllers\Api\Ynov;

use App\Http\Controllers\Controller;
use App\Models\Api\Ynov\parameter\User;
use App\Services\Api\Ynov\Auth\OtpService;
use App\Services\Api\Ynov\SignatureService;
use App\Support\SafeUrl;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;

/**
 * Contrôleur du Widget de Signature Électronique — YAKOA AFRICASSUR
 *
 * Endpoints :
 *   POST /api/v1/signature/generate-link          (app hôte, auth:sanctum)
 *   POST /api/v1/signature/send/{email|sms|whatsapp} (app hôte, auth:sanctum)
 *   POST /api/v1/signature/otp/verify             (widget, lié au token)
 *   POST /api/v1/signature/webhook                (widget -> Laravel, authentifié par le token)
 *   GET  /api/v1/signature/token/{token}/status   (polling)
 *   GET  /signature/widget/{token}                (page du widget)
 *   GET  /signature/proxy-document/{token}        (document du token, via proxy sécurisé)
 */
class SignatureController extends Controller
{
    private const MAX_DOCUMENT_BYTES = 25 * 1024 * 1024;
    private const OTP_MAX_ATTEMPTS   = 8;
    private const OTP_DECAY_SECONDS  = 600;

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
                'success_redirect_url' => ['nullable', 'url:http,https'],
                'cancel_redirect_url'  => ['nullable', 'url:http,https'],
                'enable_auto_polling'  => ['nullable', 'boolean'],
                'expires_in'           => ['nullable', 'integer', 'min:60', 'max:86400'],

                'signer_login'         => ['nullable', 'string', 'max:100'],
                'signer_user_uuid'     => ['nullable', 'uuid'],
                'signer_email'         => ['nullable', 'email'],
                'signer_phone'         => ['nullable', 'string', 'max:20'],
                'otp_purpose'          => ['nullable', 'string', 'max:120'],
                'otp_qr_url_template'  => ['nullable', 'url:http,https', 'max:2000'],
            ]);

            // Anti-SSRF : le webhook doit pointer vers un hôte public (et autorisé si une liste existe)
            try {
                SafeUrl::resolve(
                    $validated['webhook_url'],
                    true,
                    (array) config('services.signature.allowed_webhook_hosts', [])
                );
            } catch (InvalidArgumentException $e) {
                throw ValidationException::withMessages(['webhook_url' => [$e->getMessage()]]);
            }

            $result = $this->signatureService->generateSignatureLink(
                $validated['document_url'] ?? null,
                $validated['document_description'] ?? null,
                $validated['webhook_url'],
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

    public function serveWidget(Request $request, string $token)
    {
        try {
            $widgetData = $this->signatureService->getWidgetData($token);

            if (!$widgetData) {
                return response()->view('signature.invalid', [], 404)
                    ->header('Cache-Control', 'no-store');
            }

            // Document hébergé ailleurs : le navigateur ne reçoit que l'URL du proxy lié au token
            $documentUrl = $widgetData['document_url'];
            if ($documentUrl) {
                $documentHost = strtolower((string) parse_url($documentUrl, PHP_URL_HOST));

                if ($documentHost !== strtolower($request->getHost())) {
                    $documentUrl = url('/signature/proxy-document/' . $widgetData['token']);
                }
            }

            return response()->view('signature.widget', [
                'token'                => $widgetData['token'],
                'document_url'         => $documentUrl,
                'document_description' => $widgetData['document_description'] ?: 'Document à signer',
                'success_redirect_url' => $widgetData['success_redirect_url'],
                'cancel_redirect_url'  => $widgetData['cancel_redirect_url'],
                'enable_auto_polling'  => $widgetData['enable_auto_polling'],
                'signer_login'         => $widgetData['signer_login'],
                'signer_user_uuid'     => $widgetData['signer_user_uuid'],
                'signer_email'         => $widgetData['signer_email'],
                'signer_phone'         => $widgetData['signer_phone'],
                'otp_purpose'          => $widgetData['otp_purpose'],
                'otp_qr_url_template'  => $widgetData['otp_qr_url_template'],
            ])->header('Cache-Control', 'no-store');
        } catch (\Throwable $e) {
            Log::error('[Signature] Erreur de chargement du widget', ['message' => $e->getMessage()]);

            return response()->view('signature.invalid', [], 500);
        }
    }

    // =====================================================================
    // PROXY DE DOCUMENT (lié au token)
    // =====================================================================

    /**
     * Sert le document associé au token. L'URL n'est JAMAIS fournie par le navigateur :
     * elle est lue en base. Anti-SSRF : hôte autorisé, IP publique validée puis épinglée,
     * pas de redirection, type MIME et taille contrôlés pendant le téléchargement.
     */
    public function proxyDocument(string $token)
    {
        $signatureRequest = $this->signatureService->findActiveRequest($token);

        if (!$signatureRequest || !$signatureRequest->document_url) {
            abort(404);
        }

        $allowedHosts = (array) config('services.signature.allowed_document_hosts', []);

        if (!$allowedHosts && app()->environment('production')) {
            Log::error('[Signature] services.signature.allowed_document_hosts est vide en production : proxy désactivé.');
            abort(403, 'Proxy de document non configuré.');
        }

        try {
            $target = SafeUrl::resolve($signatureRequest->document_url, false, $allowedHosts);
        } catch (InvalidArgumentException $e) {
            abort(403, 'Document non autorisé.');
        }

        $allowedTypes = ['application/pdf', 'image/png', 'image/jpeg', 'image/jpg'];
        $max          = self::MAX_DOCUMENT_BYTES;
        $reject       = null; // code HTTP à renvoyer si un contrôle en cours de route échoue

        try {
            $response = Http::timeout(15)
                ->withOptions([
                    'allow_redirects' => false,
                    'curl'            => $target['curl'],
                    'on_headers'      => function (ResponseInterface $r) use ($max, $allowedTypes, &$reject) {
                        $type = strtolower(trim(explode(';', $r->getHeaderLine('Content-Type'))[0]));

                        if (!in_array($type, $allowedTypes, true)) {
                            $reject = 415;
                            throw new \RuntimeException('type_not_allowed');
                        }

                        if ((int) $r->getHeaderLine('Content-Length') > $max) {
                            $reject = 413;
                            throw new \RuntimeException('too_large');
                        }
                    },
                    // Coupe le téléchargement dès que la limite est dépassée (même sans Content-Length)
                    'progress'        => function ($dlTotal, $dlNow) use ($max, &$reject) {
                        if ($dlNow > $max) {
                            $reject = 413;
                            throw new \RuntimeException('too_large');
                        }
                    },
                ])
                ->get($target['url']);
        } catch (\Throwable $e) {
            if ($reject === null) {
                Log::warning('[Signature] Proxy document en échec', ['message' => $e->getMessage()]);
            }
        }

        // Les abort() sont volontairement HORS du try/catch pour que 413/415 ne soient pas avalés en 502
        if ($reject !== null) {
            abort($reject, $reject === 413 ? 'Document trop volumineux.' : 'Type de document non pris en charge.');
        }

        if (!isset($response) || !$response->successful()) {
            abort(502, 'Document inaccessible.');
        }

        $body = $response->body();

        if (strlen($body) > $max) {
            abort(413, 'Document trop volumineux.');
        }

        $contentType = strtolower(trim(explode(';', $response->header('Content-Type'))[0]));

        if (!in_array($contentType, $allowedTypes, true)) {
            abort(415, 'Type de document non pris en charge.');
        }

        // Même origine : aucun en-tête CORS nécessaire
        return response($body)
            ->header('Content-Type', $contentType)
            ->header('Content-Disposition', 'inline')
            ->header('X-Content-Type-Options', 'nosniff')
            ->header('Content-Security-Policy', "default-src 'none'; sandbox")
            ->header('Referrer-Policy', 'no-referrer')
            ->header('Cache-Control', 'private, max-age=300');
    }

    // =====================================================================
    // RÉCEPTION DE LA SIGNATURE
    // =====================================================================

    public function receiveSignature(Request $request): JsonResponse
    {
        try {
            // Le bloc `otp` envoyé par le navigateur n'est volontairement plus accepté :
            // les preuves OTP viennent uniquement du serveur (voir verifySignatureOtp).
            $validated = $request->validate([
                'token'     => ['required', 'string'],
                'signature' => ['required', 'string'],
                'method'    => ['nullable', 'string', 'in:otp_qr,handwritten'],
                'geo'       => ['nullable', 'array'],
                'geo.lat'   => ['nullable', 'numeric', 'between:-90,90'],
                'geo.lng'   => ['nullable', 'numeric', 'between:-180,180'],
            ]);

            $result = $this->signatureService->processSignature(
                $validated['signature'],
                $validated['token'],
                [
                    'method' => $validated['method'] ?? 'handwritten',
                    'geo'    => $validated['geo'] ?? [],
                ]
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
    // VÉRIFICATION OTP (liée au token)
    // =====================================================================

    /**
     * Vérifie un code OTP POUR CE TOKEN.
     *
     * - le signataire (user) et le purpose viennent des métadonnées du lien, jamais du navigateur
     * - le contact doit correspondre au signataire défini à la création du lien
     * - rate-limit par token
     * - en cas de succès : une preuve serveur (IP, user-agent, canal, date) est enregistrée
     *   pour le token ; processSignature l'exige pour method=otp_qr
     *
     * @bodyParam token   string required Token du lien de signature
     * @bodyParam code    string required Code à 6 chiffres
     * @bodyParam channel string required sms|email|whatsapp
     * @bodyParam contact string required Email ou téléphone utilisé
     */
    public function verifySignatureOtp(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'token'   => ['required', 'string'],
                'code'    => ['required', 'string', 'size:6', 'regex:/^[0-9]{6}$/'],
                'channel' => ['required', 'string', 'in:sms,email,whatsapp'],
                'contact' => ['required', 'string', 'max:255'],
            ]);

            $signatureRequest = $this->signatureService->findActiveRequest($validated['token']);

            if (!$signatureRequest) {
                return response()->json([
                    'success' => false,
                    'code'    => 'INVALID_TOKEN',
                    'message' => 'Lien invalide ou expiré.',
                ], 404);
            }

            $limiterKey = 'signature-otp:' . sha1($signatureRequest->token);

            if (RateLimiter::tooManyAttempts($limiterKey, self::OTP_MAX_ATTEMPTS)) {
                return response()->json([
                    'success' => false,
                    'code'    => 'OTP_TOO_MANY_ATTEMPTS',
                    'message' => 'Trop de tentatives. Réessayez plus tard.',
                ], 429);
            }

            $metadata = $signatureRequest->metadata ?? [];

            // Signataire défini côté serveur uniquement
            $user = null;
            if (!empty($metadata['signer_user_uuid'])) {
                $user = User::where('uuid_user', $metadata['signer_user_uuid'])->first();
            } elseif (!empty($metadata['signer_login'])) {
                $user = User::where('login', $metadata['signer_login'])->first();
            } else {
                return response()->json([
                    'success' => false,
                    'code'    => 'SIGNER_NOT_DEFINED',
                    'message' => 'Aucun signataire défini pour ce lien : la vérification OTP est indisponible.',
                ], 422);
            }

            $purpose = $metadata['otp_purpose'] ?? SignatureService::DEFAULT_OTP_PURPOSE;

            if (
                !$user
                || !$this->contactMatchesSigner($validated['channel'], $validated['contact'], $metadata)
                || !$this->otpService->verify($user, $validated['code'], $purpose)
            ) {
                RateLimiter::hit($limiterKey, self::OTP_DECAY_SECONDS);

                return response()->json([
                    'success' => false,
                    'code'    => 'OTP_INVALID',
                    'message' => 'Code OTP invalide ou expiré.',
                ], 422);
            }

            RateLimiter::clear($limiterKey);

            $otp = $this->otpService->getOtpByUser($user, $purpose, $validated['channel']);

            $proof = [
                'user_uuid'  => $user->uuid_user,
                'channel'    => $otp?->channel ?? $validated['channel'],
                'contact'    => $validated['contact'],
                'purpose'    => $this->otpService->normalizePurpose($purpose),
                'ip_address' => $request->ip(),
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
                'used_at'    => optional($otp?->used_at)->toIso8601String() ?? now()->toIso8601String(),
            ];

            // Preuve serveur liée au token (aucune donnée personnelle)
            $this->signatureService->storeOtpProof($signatureRequest, $proof);

            $user->load(['details']);

            return response()->json([
                'success' => true,
                'code'    => 'OTP_VERIFIED',
                'message' => 'Code OTP vérifié.',
                'data'    => array_merge($proof, [
                    'login'            => $user->login,
                    'email'            => $user->email,
                    'nom'              => $user->details?->nom,
                    'prenoms'          => $user->details?->prenoms,
                    'mobile_1'         => $user->details?->mobile_1,
                    'adresse_complete' => $user->details?->adresse_complete,
                ]),
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'OTP_VERIFY_ERROR', 'Erreur lors de la vérification OTP.');
        }
    }

    /** Le contact saisi doit correspondre au signataire défini à la création du lien (si renseigné). */
    private function contactMatchesSigner(string $channel, string $contact, array $metadata): bool
    {
        if ($channel === 'email') {
            $expected = $metadata['signer_email'] ?? null;

            return !$expected || strcasecmp(trim($contact), trim($expected)) === 0;
        }

        $expected = $metadata['signer_phone'] ?? null;

        if (!$expected) {
            return true;
        }

        $given    = substr(preg_replace('/\D+/', '', $contact), -8);
        $expected = substr(preg_replace('/\D+/', '', $expected), -8);

        return $given !== '' && $given === $expected;
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
    // ENVOI DU LIEN (routes protégées par auth:sanctum)
    // =====================================================================

    public function sendByEmail(Request $request): JsonResponse
    {
        return $this->dispatchSend($request, [
            'token'   => ['required', 'string', 'regex:/^[A-Za-z0-9]{64}$/'],
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
            'token'   => ['required', 'string', 'regex:/^[A-Za-z0-9]{64}$/'],
            'phone'   => ['required', 'string', 'max:20'],
            'message' => ['nullable', 'string', 'max:160'],
        ], fn (array $v) => $this->signatureService->sendLinkBySms(
            $v['token'], $v['phone'], $v['message'] ?? null
        ), 'SMS_SEND_ERROR');
    }

    public function sendByWhatsapp(Request $request): JsonResponse
    {
        return $this->dispatchSend($request, [
            'token'   => ['required', 'string', 'regex:/^[A-Za-z0-9]{64}$/'],
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