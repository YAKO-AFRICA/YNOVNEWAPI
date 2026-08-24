<?php

namespace App\Http\Controllers\Api\Ynov;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Ynov\TwoFactorEnableRequest;
use App\Http\Resources\Api\Ynov\LoginResource;
use App\Mail\Api\Ynov\OtpMail;
use App\Mail\Api\Ynov\TwoFactorDisabledMail;
use App\Mail\Api\Ynov\TwoFactorEnabledMail;
use App\Services\Api\Ynov\Auth\DeviceService;
use App\Services\Api\Ynov\Auth\OtpService;
use App\Services\Api\Ynov\Auth\TwoFactorService;
use App\Services\SMSService;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class TwoFactorController extends Controller
{
    public function __construct(
        private TwoFactorService $twoFactorService,
        private OtpService $otpService,
        private DeviceService $deviceService,
        private SMSService $SMSService
    ) {}

    public function enable(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user->two_factor_enabled) {
            return response()->json(['success' => false, 'message' => '2FA déjà activé.'], 422);
        }

        $secret = $this->twoFactorService->generateSecret();
        $user->update(['two_factor_secret' => $secret]);
        $qrUrl = $this->twoFactorService->getQRCodeUrl('YNOV', $user->email, $secret);

        $renderer = new ImageRenderer(new RendererStyle(400), new SvgImageBackEnd());
        $writer = new Writer($renderer);
        $qrSvg = $writer->writeString($qrUrl);

        return response()->json([
            'success' => true,
            'data' => ['secret' => $secret, 'qr_code_svg' => $qrSvg],
        ]);
    }

    public function confirm(TwoFactorEnableRequest $request): JsonResponse
    {
        $user = $request->user();

        if (!$this->twoFactorService->verify($user->two_factor_secret, $request->code)) {
            return response()->json(['success' => false, 'message' => 'Code invalide.'], 422);
        }

        $codes = $this->twoFactorService->generateRecoveryCodes($user);

        $user->update([
            'two_factor_enabled' => true,
            'two_factor_recovery_codes' => encrypt(implode(',', $codes)),
        ]);


        if ($user->email) {
            
            Mail::to($user->email)->queue(new TwoFactorEnabledMail($user->fresh('details'), $codes));
        }
        return response()->json([
            'success' => true,
            'message' => '2FA activé avec succès.',
            'data' => ['recovery_codes' => $codes],
        ]);
    }

    public function disable(Request $request): JsonResponse
    {
        $request->validate(['password' => 'required|string']);
        $user = $request->user();

        if (!Hash::check($request->password, $user->password)) {
            return response()->json(['success' => false, 'message' => 'Mot de passe incorrect.'], 403);
        }

        $user->update([
            'two_factor_secret' => null,
            'two_factor_enabled' => false,
            'two_factor_recovery_codes' => null,
        ]);
        $user->twoFactorRecoveryCodes()->delete();

        if ($user->email) {
            Mail::to($user->email)->queue(new TwoFactorDisabledMail($user->fresh('details'), $request->ip()));
        }
        // Mail::to($user->email)->queue(new TwoFactorDisabledMail($user->fresh('details'), $request->ip()));

        return response()->json(['success' => true, 'message' => '2FA désactivé.']);
    }

    public function verifyLogin(Request $request): JsonResponse
    {
        $request->validate(['code' => 'required|string|size:6']);
        $user = $request->user();

        if (!$user || !$request->user()->currentAccessToken()->can('2fa-verify')) {
            return response()->json(['success' => false, 'message' => 'Token invalide.'], 401);
        }

        if (!$this->twoFactorService->verify($user->two_factor_secret, $request->code)) {
            return response()->json(['success' => false, 'message' => 'Code 2FA invalide.'], 422);
        }

        $user->currentAccessToken()->delete();

        if ($request->boolean('trust_device')) {
            // Le fingerprint est recalculé côté serveur à partir de la requête
            // courante (IP + User-Agent), jamais fourni par le front, pour
            // garantir qu'il correspond bien à celui enregistré lors du login.
            $fingerprint = $this->deviceService->fingerprint($request);
            $this->deviceService->trust($user, $fingerprint);
        }

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
    
    public function sendOtp(Request $request): JsonResponse
    {
        $request->validate([
            'channel' => 'required|in:email,sms',
            'purpose' => 'required|in:login,2fa,reset'
        ]);

        $user = $request->user();
        $code = $this->otpService->generate(
            $user,
            $request->channel,
            $request->purpose,
            $request->ip(),
            $request->userAgent()
        );

        if ($request->channel === 'email') {
            // ✅ Envoi par Email
            Mail::to($user->email)->queue(new OtpMail(
                $user->details,
                $request->purpose,
                $code,
                2 // Expiration en minutes
            ));

            Log::info("OTP envoyé par email à: {$user->email} pour: {$request->purpose}");
        } elseif ($request->channel === 'sms') {
            // Envoi par SMS
            $phone = preg_replace('/\D/', '', $user->details->mobile_1 ?? '');
            $phone = substr($phone, -10);

            if (strlen($phone) === 10) {
                $phoneNumber = '+225' . $phone;
                $dataMessage = 'Votre code OTP YNOV est : ' . $code . ' (valable 2 min)';
                $this->SMSService->sendSmsByInfobipAPI($phoneNumber, $dataMessage);

                Log::info("OTP envoyé par SMS à: {$phoneNumber} pour: {$request->purpose}");
            } else {
                Log::warning("Numéro de téléphone invalide pour OTP SMS: {$user->details->mobile_1}");
                return response()->json([
                    'success' => false,
                    'message' => 'Numéro de téléphone invalide pour l\'envoi SMS.'
                ], 422);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Code OTP envoyé avec succès.',
            'data' => [
                'channel' => $request->channel,
                'purpose' => $request->purpose,
                'expires_in' => 2, // minutes
            ]
        ]);
    }

    public function verifyOtp(Request $request): JsonResponse
    {
        $request->validate(['code' => 'required|string|size:6', 'purpose' => 'required|string']);
        $user = $request->user();

        if (!$this->otpService->verify($user, $request->code, $request->purpose)) {
            return response()->json(['success' => false, 'message' => 'Code OTP invalide ou expiré.'], 422);
        }

        return response()->json(['success' => true, 'message' => 'Code OTP vérifié.']);
    }
}

// namespace App\Http\Controllers\Api\Ynov;

// use App\Http\Controllers\Controller;
// use App\Http\Requests\Api\Ynov\TwoFactorEnableRequest;
// use App\Http\Resources\Api\Ynov\LoginResource;
// use App\Mail\Api\Ynov\OtpMail;
// use App\Mail\Api\Ynov\TwoFactorDisabledMail;
// use App\Mail\Api\Ynov\TwoFactorEnabledMail;
// use App\Services\Api\Ynov\Auth\DeviceService;
// use App\Services\Api\Ynov\Auth\OtpService;
// use App\Services\Api\Ynov\Auth\ThrottleService;
// use App\Services\Api\Ynov\Auth\TwoFactorService;
// use App\Services\SMSService;
// use BaconQrCode\Renderer\Image\SvgImageBackEnd;
// use BaconQrCode\Renderer\ImageRenderer;
// use BaconQrCode\Renderer\RendererStyle\RendererStyle;
// use BaconQrCode\Writer;
// use Illuminate\Http\JsonResponse;
// use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Hash;
// use Illuminate\Support\Facades\Log;
// use Illuminate\Support\Facades\Mail;

// class TwoFactorController extends Controller
// {
//     private const MAX_TOTP_ATTEMPTS = 5;
//     private const TOTP_DECAY_SECONDS = 300; // 5 minutes
//     private const MAX_OTP_ATTEMPTS = 5;
//     private const OTP_DECAY_SECONDS = 300; // 5 minutes

//     public function __construct(
//         private TwoFactorService $twoFactorService,
//         private OtpService $otpService,
//         private DeviceService $deviceService,
//         private SMSService $SMSService,
//         private ThrottleService $throttleService,
//     ) {}

//     public function enable(Request $request): JsonResponse
//     {
//         $user = $request->user();
//         if ($user->two_factor_enabled) {
//             return response()->json(['success' => false, 'message' => '2FA déjà activé.'], 422);
//         }

//         $secret = $this->twoFactorService->generateSecret();
//         $user->update(['two_factor_secret' => $secret]);
//         $qrUrl = $this->twoFactorService->getQRCodeUrl('YNOV', $user->email, $secret);

//         $renderer = new ImageRenderer(new RendererStyle(400), new SvgImageBackEnd());
//         $writer = new Writer($renderer);
//         $qrSvg = $writer->writeString($qrUrl);

//         return response()->json([
//             'success' => true,
//             'data' => ['secret' => $secret, 'qr_code_svg' => $qrSvg],
//         ]);
//     }

//     public function confirm(TwoFactorEnableRequest $request): JsonResponse
//     {
//         $user = $request->user();

//         if (!$this->twoFactorService->verify($user->two_factor_secret, $request->code)) {
//             return response()->json(['success' => false, 'message' => 'Code invalide.'], 422);
//         }

//         $codes = $this->twoFactorService->generateRecoveryCodes($user);

//         $user->update([
//             'two_factor_enabled' => true,
//             'two_factor_recovery_codes' => encrypt(implode(',', $codes)),
//         ]);

//         Mail::to($user->email)->queue(new TwoFactorEnabledMail($user->fresh('details'), $codes));

//         return response()->json([
//             'success' => true,
//             'message' => '2FA activé avec succès.',
//             'data' => ['recovery_codes' => $codes],
//         ]);
//     }

//     public function disable(Request $request): JsonResponse
//     {
//         $request->validate(['password' => 'required|string']);
//         $user = $request->user();

//         if (!Hash::check($request->password, $user->password)) {
//             return response()->json(['success' => false, 'message' => 'Mot de passe incorrect.'], 403);
//         }

//         $user->update([
//             'two_factor_secret' => null,
//             'two_factor_enabled' => false,
//             'two_factor_recovery_codes' => null,
//         ]);
//         $user->twoFactorRecoveryCodes()->delete();

//         Mail::to($user->email)->queue(new TwoFactorDisabledMail($user->fresh('details'), $request->ip()));

//         return response()->json(['success' => true, 'message' => '2FA désactivé.']);
//     }

//     /**
//      * Vérification du code TOTP pour la connexion
//      * ================================================================
//      * CORRECTION #5 : Ajout du rate limiting sur TOTP
//      * ================================================================
//      */
//     public function verifyLogin(Request $request): JsonResponse
//     {
//         $request->validate(['code' => 'required|string|size:6']);
//         $user = $request->user();

//         if (!$user || !$request->user()->currentAccessToken()->can('2fa-verify')) {
//             return response()->json(['success' => false, 'message' => 'Token invalide.'], 401);
//         }

//         // ================================================================
//         // CORRECTION #5 : Rate limiting sur TOTP
//         // ================================================================
//         $throttleKey = $this->throttleService->key($user, 'totp_verify');

//         try {
//             $this->throttleService->checkAndIncrement(
//                 $throttleKey,
//                 self::MAX_TOTP_ATTEMPTS,
//                 self::TOTP_DECAY_SECONDS
//             );
//         } catch (\RuntimeException $e) {
//             return response()->json([
//                 'success' => false,
//                 'message' => $e->getMessage(),
//                 'code' => 'TOO_MANY_ATTEMPTS',
//                 'available_in' => $this->throttleService->availableIn($throttleKey),
//             ], 429);
//         }

//         // Vérification du code TOTP
//         if (!$this->twoFactorService->verify($user->two_factor_secret, $request->code)) {
//             return response()->json(['success' => false, 'message' => 'Code 2FA invalide.'], 422);
//         }

//         // Réinitialiser le compteur en cas de succès
//         $this->throttleService->clear($throttleKey);

//         $user->currentAccessToken()->delete();

//         if ($request->boolean('trust_device')) {
//             $fingerprint = $this->deviceService->fingerprint($request);
//             $this->deviceService->trust($user, $fingerprint);
//         }

//         $token = $user->createToken('API Token', ['*'], now()->addHours(24));

//         return (new LoginResource((object)[
//             'user' => $user,
//             'token' => $token->plainTextToken,
//             'expires_at' => now()->addHours(24),
//             'requires_2fa' => false,
//             'must_change_password' => false,
//             'trusted_device' => true,
//         ]))->response()->setStatusCode(200);
//     }

//     public function sendOtp(Request $request): JsonResponse
//     {
//         $request->validate([
//             'channel' => 'required|in:email,sms',
//             'purpose' => 'required|in:login,2fa,reset'
//         ]);

//         $user = $request->user();

//         // ================================================================
//         // CORRECTION #6 : Rate limiting sur l'envoi d'OTP
//         // ================================================================
//         $sendThrottleKey = $this->throttleService->key($user, 'otp_send');

//         try {
//             $this->throttleService->checkAndIncrement(
//                 $sendThrottleKey,
//                 3, // Max 3 envois
//                 600 // 10 minutes
//             );
//         } catch (\RuntimeException $e) {
//             return response()->json([
//                 'success' => false,
//                 'message' => $e->getMessage(),
//                 'code' => 'TOO_MANY_ATTEMPTS',
//                 'available_in' => $this->throttleService->availableIn($sendThrottleKey),
//             ], 429);
//         }

//         $code = $this->otpService->generate(
//             $user,
//             $request->channel,
//             $request->purpose,
//             $request->ip(),
//             $request->userAgent()
//         );

//         if ($request->channel === 'email') {
//             Mail::to($user->email)->queue(new OtpMail(
//                 $user->details,
//                 $request->purpose,
//                 $code,
//                 2
//             ));

//             Log::info("OTP envoyé par email à: {$user->email} pour: {$request->purpose}");

//         } elseif ($request->channel === 'sms') {
//             $phone = preg_replace('/\D/', '', $user->details->mobile_1 ?? '');
//             $phone = substr($phone, -10);

//             if (strlen($phone) === 10) {
//                 $phoneNumber = '+225' . $phone;
//                 $dataMessage = 'Votre code OTP YNOV est : ' . $code . ' (valable 2 min)';
//                 $this->SMSService->sendSmsByInfobipAPI($phoneNumber, $dataMessage);

//                 Log::info("OTP envoyé par SMS à: {$phoneNumber} pour: {$request->purpose}");
//             } else {
//                 Log::warning("Numéro de téléphone invalide pour OTP SMS: {$user->details->mobile_1}");
//                 return response()->json([
//                     'success' => false,
//                     'message' => 'Numéro de téléphone invalide pour l\'envoi SMS.'
//                 ], 422);
//             }
//         }

//         return response()->json([
//             'success' => true,
//             'message' => 'Code OTP envoyé avec succès.',
//             'data' => [
//                 'channel' => $request->channel,
//                 'purpose' => $request->purpose,
//                 'expires_in' => 2,
//             ]
//         ]);
//     }

//     /**
//      * Vérification du code OTP
//      * ================================================================
//      * CORRECTION #6 : Rate limiting sur la vérification OTP
//      * ================================================================
//      */
//     public function verifyOtp(Request $request): JsonResponse
//     {
//         $request->validate(['code' => 'required|string|size:6', 'purpose' => 'required|string']);
//         $user = $request->user();

//         // ================================================================
//         // CORRECTION #6 : Rate limiting sur la vérification OTP
//         // ================================================================
//         $throttleKey = $this->throttleService->key($user, 'otp_verify');

//         try {
//             $this->throttleService->checkAndIncrement(
//                 $throttleKey,
//                 self::MAX_OTP_ATTEMPTS,
//                 self::OTP_DECAY_SECONDS
//             );
//         } catch (\RuntimeException $e) {
//             return response()->json([
//                 'success' => false,
//                 'message' => $e->getMessage(),
//                 'code' => 'TOO_MANY_ATTEMPTS',
//                 'available_in' => $this->throttleService->availableIn($throttleKey),
//             ], 429);
//         }

//         if (!$this->otpService->verify($user, $request->code, $request->purpose)) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'Code OTP invalide ou expiré.'
//             ], 422);
//         }

//         // Réinitialiser le compteur en cas de succès
//         $this->throttleService->clear($throttleKey);

//         return response()->json(['success' => true, 'message' => 'Code OTP vérifié.']);
//     }
// }