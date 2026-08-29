<?php

namespace App\Http\Controllers\Api\Ynov;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Ynov\TwoFactorEnableRequest;
use App\Http\Resources\Api\Ynov\LoginResource;
use App\Mail\Api\Ynov\TwoFactorDisabledMail;
use App\Mail\Api\Ynov\TwoFactorEnabledMail;
use App\Mail\Api\Ynov\TwoFactorRecoveryCodesMail;
use App\Models\Api\Ynov\parameter\GroupNotif;
use App\Models\Api\Ynov\parameter\TwoFactorRecoveryCode;
use App\Models\Api\Ynov\parameter\User;
use App\Services\Api\Ynov\Auth\DeviceService;
use App\Services\Api\Ynov\Auth\OtpService;
use App\Services\Api\Ynov\Auth\TwoFactorService;
use App\Services\Api\Ynov\NotificationService;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class TwoFactorController extends Controller
{
    public function __construct(
        private TwoFactorService $twoFactorService,
        private OtpService $otpService,
        private DeviceService $deviceService,
        
        private NotificationService $notificationService,
    ) {}

    /**
     * Activer 2FA - Générer QR Code ou OTP
     */
    public function enable(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if ($user->two_factor_enabled) {
            return response()->json([
                'success' => false,
                'code' => '2FA_ALREADY_ENABLED',
                'message' => '2FA déjà activé.'
            ], 422);
        }

        $method = $request->input('method', 'authenticator');

        // Méthode OTP (SMS/Email)
        if ($method === 'otp') {
            // Envoyer un OTP de vérification
            $result = $this->twoFactorService->sendOtp(
                $user,
                $request->input('channel', 'email'),
                '2fa_activation',
                $request->ip(),
                $request->userAgent(),
                5,
                $request->all()
            );

            if (!$result['success']) {
                return response()->json($result, 422);
            }

            // Stocker temporairement la méthode choisie
            session(['2fa_method' => 'otp']);

            return response()->json([
                'success' => true,
                'data' => [
                    'method' => 'otp',
                    'message' => $result['message'],
                    'expires_in' => $result['data']['expires_in'] ?? 5,
                ]
            ]);
        }

        // Méthode Authenticator (Google Authenticator)
        $secret = $this->twoFactorService->generateSecret();
        $user->update(['two_factor_secret' => $secret]);

        $qrUrl = $this->twoFactorService->getQRCodeUrl('YNOV', $user->email, $secret);

        $renderer = new ImageRenderer(new RendererStyle(400), new SvgImageBackEnd());
        $writer = new Writer($renderer);
        $qrSvg = $writer->writeString($qrUrl);

        // Stocker temporairement la méthode choisie
        session(['2fa_method' => 'authenticator']);

        return response()->json([
            'success' => true,
            'data' => [
                'method' => 'authenticator',
                'secret' => $secret,
                'qr_code_svg' => $qrSvg,
            ],
        ]);
    }

    /**
     * Confirmer l'activation 2FA
     */
    // public function confirm(TwoFactorEnableRequest $request): JsonResponse
    // {
    //     $user = $request->user();
    //     $method = $request->input('method', 'authenticator');

    //     // Vérifier le code
    //     $codeValid = false;

    //     if ($method === 'authenticator') {
    //         // Vérification TOTP
    //         if (!$user->two_factor_secret) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Secret 2FA non trouvé. Veuillez recommencer l\'activation.'
    //             ], 422);
    //         }
    //         $codeValid = $this->twoFactorService->verify($user->two_factor_secret, $request->code);
    //     } else {
    //         // Vérification OTP
    //         $codeValid = $this->otpService->verify($user, $request->code, '2fa_activation');
    //     }

    //     if (!$codeValid) {
    //         // Incrémenter les tentatives
    //         $user->incrementTwoFactorAttempts();
            
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Code invalide.',
    //             'attempts' => $user->two_factor_attempts,
    //             'is_locked' => $user->isTwoFactorLocked(),
    //             'locked_until' => $user->isTwoFactorLocked() ? $user->two_factor_locked_until : null,
    //         ], 422);
    //     }

    //     // Générer les codes de récupération
    //     $codes = $this->twoFactorService->generateRecoveryCodes($user);

    //     // Activer la 2FA
    //     $user->enableTwoFactor(
    //         $method,
    //         $user->two_factor_secret,
    //         $codes
    //     );

    //     // Réinitialiser les tentatives
    //     $user->resetTwoFactorAttempts();

    //     // Envoyer email de confirmation
    //     if ($user->email) {
    //         Mail::to($user->email)->queue(new TwoFactorEnabledMail(
    //             $user->fresh('details'),
    //             $codes
    //         ));
    //     }

    //     return response()->json([
    //         'success' => true,
    //         'message' => '2FA activé avec succès.',
    //         'data' => [
    //             'method' => $method,
    //             'recovery_codes' => $codes,
    //             'recovery_codes_count' => count($codes),
    //         ],
    //     ], 201);
    // }

    // /**
    //  * Désactiver la 2FA
    //  */
    // public function disable(Request $request): JsonResponse
    // {
    //     $request->validate(['password' => 'required|string']);
    //     $user = $request->user();

    //     if (!Hash::check($request->password, $user->password)) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Mot de passe incorrect.'
    //         ], 403);
    //     }

    //     $user->disableTwoFactor();

    //     // Supprimer les codes de récupération
    //     TwoFactorRecoveryCode::where('user_uuid', $user->uuid_user)->delete();

    //     if ($user->email) {
    //         Mail::to($user->email)->queue(new TwoFactorDisabledMail(
    //             $user->fresh('details'),
    //             $request->ip()
    //         ));
    //     }

    //     return response()->json([
    //         'success' => true,
    //         'message' => '2FA désactivé.'
    //     ]);
    // }

    public function confirm(TwoFactorEnableRequest $request): JsonResponse
    {
        $user = $request->user();
        $method = $request->input('method', 'authenticator');

        $codeValid = false;

        if ($method === 'authenticator') {
            if (!$user->two_factor_secret) {
                return response()->json([
                    'success' => false,
                    'message' => 'Secret 2FA non trouvé. Veuillez recommencer l\'activation.'
                ], 422);
            }
            $codeValid = $this->twoFactorService->verify($user->two_factor_secret, $request->code);
        } else {
            $codeValid = $this->otpService->verify($user, $request->code, '2fa_activation');
        }

        if (!$codeValid) {
            $user->incrementTwoFactorAttempts();
            
            return response()->json([
                'success' => false,
                'message' => 'Code invalide.',
                'attempts' => $user->two_factor_attempts,
                'is_locked' => $user->isTwoFactorLocked(),
                'locked_until' => $user->isTwoFactorLocked() ? $user->two_factor_locked_until : null,
            ], 422);
        }

        $codes = $this->twoFactorService->generateRecoveryCodes($user);

        $user->enableTwoFactor(
            $method,
            $user->two_factor_secret,
            $codes
        );

        $user->resetTwoFactorAttempts();

        // Créer une notification pour l'activation 2FA
        $this->notificationService->create([
            'user_uuid' => $user->uuid_user,
            'group_notif_uuid' => $this->getSecurityGroupUuid(),
            'title' => '🔐 2FA activée',
            'body' => "La double authentification a été activée sur votre compte avec la méthode {$method}.",
            'type' => 'security',
            'metadata' => [
                'method' => $method,
                'activated_at' => now()->toISOString(),
            ],
            'channel' => 'database',
            'created_by' => $user->uuid_user,
        ]);

        if ($user->email) {
            Mail::to($user->email)->queue(new TwoFactorEnabledMail(
                $user->fresh('details'),
                $codes
            ));
        }

        return response()->json([
            'success' => true,
            'message' => '2FA activé avec succès.',
            'data' => [
                'method' => $method,
                'recovery_codes' => $codes,
                'recovery_codes_count' => count($codes),
            ],
        ], 201);
    }

    public function disable(Request $request): JsonResponse
    {
        $request->validate(['password' => 'required|string']);
        $user = $request->user();

        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Mot de passe incorrect.'
            ], 403);
        }

        $user->disableTwoFactor();
        TwoFactorRecoveryCode::where('user_uuid', $user->uuid_user)->delete();

        // Créer une notification pour la désactivation 2FA
        $this->notificationService->create([
            'user_uuid' => $user->uuid_user,
            'group_notif_uuid' => $this->getSecurityGroupUuid(),
            'title' => '🔓 2FA désactivée',
            'body' => 'La double authentification a été désactivée sur votre compte.',
            'type' => 'security',
            'metadata' => [
                'disabled_at' => now()->toISOString(),
                'ip_address' => $request->ip(),
            ],
            'channel' => 'database',
            'created_by' => $user->uuid_user,
        ]);

        if ($user->email) {
            Mail::to($user->email)->queue(new TwoFactorDisabledMail(
                $user->fresh('details'),
                $request->ip()
            ));
        }

        return response()->json([
            'success' => true,
            'message' => '2FA désactivé.'
        ]);
    }

    /**
     * Vérifier 2FA lors de la connexion
     */
    public function verifyLogin(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string|size:6',
            'method' => 'sometimes|string|in:authenticator,otp'
        ]);

        $user = $request->user();

        if (!$user || !$request->user()->currentAccessToken()->can('2fa-verify')) {
            return response()->json([
                'success' => false,
                'message' => 'Token invalide.'
            ], 401);
        }

        // Vérifier si l'utilisateur est verrouillé
        if ($user->isTwoFactorLocked()) {
            $remaining = $user->getTwoFactorLockRemaining();
            return response()->json([
                'success' => false,
                'message' => "Trop de tentatives. Réessayez dans {$remaining} secondes.",
                'is_locked' => true,
                'remaining_seconds' => $remaining,
            ], 423);
        }

        // Vérifier le code selon la méthode
        $method = $request->input('method', $user->two_factor_method ?? 'authenticator');
        $codeValid = false;

        if ($method === 'authenticator') {
            $codeValid = $this->twoFactorService->verify($user->two_factor_secret, $request->code);
        } else {
            $codeValid = $this->otpService->verify($user, $request->code, 'login');
        }

        if (!$codeValid) {
            $user->incrementTwoFactorAttempts();
            
            // Créer une notification pour une tentative 2FA échouée
            if ($user->two_factor_attempts >= 3) {
                $this->notificationService->create([
                    'user_uuid' => $user->uuid_user,
                    'group_notif_uuid' => $this->getSecurityGroupUuid(),
                    'title' => '⚠️ Tentatives 2FA échouées',
                    'body' => "{$user->two_factor_attempts} tentatives de vérification 2FA ont échoué sur votre compte.",
                    'type' => 'security',
                    'metadata' => [
                        'attempts' => $user->two_factor_attempts,
                        'ip_address' => $request->ip(),
                    ],
                    'channel' => 'database',
                    'created_by' => null,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Code 2FA invalide.',
                'attempts' => $user->two_factor_attempts,
                'is_locked' => $user->isTwoFactorLocked(),
            ], 422);
        }

        // Réinitialiser les tentatives
        $user->resetTwoFactorAttempts();

        // Supprimer le token temporaire
        $user->currentAccessToken()->delete();

        // Marquer l'appareil comme de confiance si demandé
        if ($request->boolean('trust_device')) {
            $fingerprint = $this->deviceService->fingerprint($request);
            $this->deviceService->trust($user, $fingerprint);
        }

        // Créer un nouveau token complet
        $token = $user->createToken('API Token', ['*'], now()->addHours(24));


        return (new LoginResource((object)[
            'user' => $user,
            'token' => $token->plainTextToken,
            'expires_at' => now()->addHours(24),
            'requires_2fa' => false,
            'must_change_password' => false,
            'trusted_device' => true,
        ]))->response()->setStatusCode(200);
    }

    /**
     * Gérer les codes de récupération
     */
    public function recoveryCodes(Request $request): JsonResponse
    {
        $request->validate([
            'action' => 'required|in:regenerate,send',
        ]);

        $user = $request->user();

        if (!$user->two_factor_enabled) {
            return response()->json([
                'success' => false,
                'code' => '2FA_NOT_ENABLED',
                'message' => 'La 2FA n\'est pas activée pour ce compte.'
            ], 422);
        }

        // if ($request->action === 'regenerate') {
        //     $newCodes = $this->twoFactorService->regenerateRecoveryCodes($user);

        //     // Envoyer les nouveaux codes par email
        //     if ($user->email) {
        //         Mail::to($user->email)->queue(new TwoFactorRecoveryCodesMail(
        //             $user->fresh('details'),
        //             $newCodes
        //         ));
        //     }

        //     return response()->json([
        //         'success' => true,
        //         'message' => 'Nouveaux codes de récupération générés.',
        //         'data' => [
        //             'recovery_codes' => $newCodes,
        //             'count' => count($newCodes),
        //         ]
        //     ]);
        // }

        if ($request->action === 'regenerate') {
            $newCodes = $this->twoFactorService->regenerateRecoveryCodes($user);

            if ($user->email) {
                Mail::to($user->email)->queue(new TwoFactorRecoveryCodesMail(
                    $user->fresh('details'),
                    $newCodes
                ));
            }

            // Créer une notification pour la régénération
            $this->notificationService->create([
                'user_uuid' => $user->uuid_user,
                'group_notif_uuid' => $this->getSecurityGroupUuid(),
                'title' => '🔄 Codes de récupération 2FA régénérés',
                'body' => 'Vos codes de récupération 2FA ont été régénérés avec succès.',
                'type' => 'security',
                'metadata' => [
                    'codes_count' => count($newCodes),
                    'regenerated_at' => now()->toISOString(),
                ],
                'channel' => 'database',
                'created_by' => $user->uuid_user,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Nouveaux codes de récupération générés.',
                'data' => [
                    'recovery_codes' => $newCodes,
                    'count' => count($newCodes),
                ]
            ]);
        }

        if ($request->action === 'send') {
            $this->twoFactorService->sendRecoveryCodes($user);

            return response()->json([
                'success' => true,
                'message' => 'Codes de récupération envoyés par email.'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Action non supportée.'
        ], 422);
    }

    /**
     * Vérifier un code de récupération
     */
    public function verifyRecovery(Request $request): JsonResponse
    {
        $request->validate([
            'login' => 'required|string|max:100|exists:users,login',
            'code' => 'required|string|size:10',
        ]);

        $user = User::where('login', $request->login)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non trouvé.'
            ], 404);
        }

        // Vérifier si le code est valide
        $isValid = $this->twoFactorService->verifyRecoveryCode($user, $request->code);

        if (!$isValid) {
            return response()->json([
                'success' => false,
                'message' => 'Code de récupération invalide ou déjà utilisé.'
            ], 422);
        }

        // Générer un token de réinitialisation de la 2FA
        $resetToken = Str::random(64);
        $hashedToken = Hash::make($resetToken);

        // Stocker le token (table personnalisée ou dans la session)
        DB::table('two_factor_reset_tokens')->updateOrInsert(
            ['user_uuid' => $user->uuid_user],
            [
                'token' => $hashedToken,
                'created_at' => now(),
                'expires_at' => now()->addMinutes(30),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Code de récupération valide.',
            'data' => [
                'user_uuid' => $user->uuid_user,
                'reset_token' => $resetToken,
                'expires_in' => 30,
            ]
        ]);
    }

    /**
     * Statut de la 2FA
     */
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();

        $recoveryCodesCount = $this->twoFactorService->countAvailableRecoveryCodes($user);

        return response()->json([
            'success' => true,
            'data' => [
                'enabled' => $user->isTwoFactorEnabled(),
                'method' => $user->getTwoFactorMethod(),
                'recovery_codes_count' => $recoveryCodesCount,
                'two_factor_method' => $user->two_factor_method ?? 'none',
                'is_locked' => $user->isTwoFactorLocked(),
                'locked_until' => $user->two_factor_locked_until,
            ]
        ]);
    }

    /**
     * Méthodes 2FA disponibles
     */
    public function methods(Request $request): JsonResponse
    {
        $user = $request->user();

        $availableMethods = ['authenticator'];
        
        // Vérifier si l'utilisateur a un email ou un téléphone pour l'OTP
        if ($user->email || $user->details?->mobile_1) {
            $availableMethods[] = 'otp';
        }

        return response()->json([
            'success' => true,
            'data' => [
                'available_methods' => $availableMethods,
                'configured_method' => $user->two_factor_method ?? 'none',
                'is_enabled' => $user->isTwoFactorEnabled(),
            ]
        ]);
    }

    private function getSecurityGroupUuid(): ?string
    {
        $group = GroupNotif::where('code', 'securite')->first();
        return $group?->uuid_group_notif;
    }
}
