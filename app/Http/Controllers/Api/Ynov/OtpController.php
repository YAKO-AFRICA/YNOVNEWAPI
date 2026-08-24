<?php

namespace App\Http\Controllers\Api\Ynov;

use App\Http\Controllers\Controller;
use App\Models\Api\Ynov\parameter\User;
use App\Services\Api\Ynov\Auth\OtpService;
use App\Services\SMSService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class OtpController extends Controller
{
    public function __construct(
        private OtpService $otpService,
        private SMSService $SMSService
    ) {}
    
    // public function sendOtp(Request $request): JsonResponse
    // {
    //     $request->validate([
    //         'channel' => 'required|in:sms,email,whatsapp',
    //         'purpose' => 'required'

    //     ]);

    //     $user = $request->user();

    //     $result = $this->otpService->sendOtp(
    //         user: $user,
    //         channel: $request->channel,
    //         purpose: $request->purpose,
    //         ip: $request->ip(),
    //         ua: $request->userAgent(),
    //         expiryMinutes: 5,
    //         data: $request->all()
    //     );

    //     if ($request->channel === 'email') {
    //         // ✅ Envoi par Email
    //         Mail::to($user->email)->queue(new OtpMail(
    //             $user->details,
    //             $request->purpose,
    //             $code,
    //             2 // Expiration en minutes
    //         ));

    //         // Log::info("OTP envoyé par email à: {$user->email} pour: {$request->purpose}");
    //     } elseif ($request->channel === 'sms') {
    //         // Envoi par SMS
    //         $phone = preg_replace('/\D/', '', $user->details->mobile_1 ?? '');
    //         $phone = substr($phone, -10);

    //         if (strlen($phone) === 10) {
    //             $phoneNumber = '+225' . $phone;
    //             $dataMessage = 'Votre code OTP YNOV est : ' . $code . ' (valable  min)';
    //             $this->SMSService->sendSmsByInfobipAPI($phoneNumber, $dataMessage);

    //             Log::info("OTP envoyé par SMS à: {$phoneNumber} pour: {$request->purpose}");
    //         } else {
    //             Log::warning("Numéro de téléphone invalide pour OTP SMS: {$user->details->mobile_1}");
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Numéro de téléphone invalide pour l\'envoi SMS.'
    //             ], 422);
    //         }
    //     }

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Code OTP envoyé avec succès.',
    //         'data' => [
    //             'channel' => $request->channel,
    //             'purpose' => $request->purpose,
    //             'expires_in' => 2, // minutes
    //         ]
    //     ]);
    // }

    public function verifyOtp(Request $request): JsonResponse
    {
        // $request->validate(['code' => 'required|string|size:6', 'purpose' => 'required|string']);
        $request->validate([
            'login' => [
                'required',
                'string',
                'max:100',
            ],
            'code' => [
                'required',
                'string',
                'size:6',
                'regex:/^[0-9]{6}$/',
            ],
            'purpose' => [
                'required',
                'string',
            ],
        ]);
        /*
        * Pour un OTP de réinitialisation,
        * l'utilisateur n'est pas encore authentifié.
        */
        $user = User::where('login', $request->login)->first();

        $result = $this->otpService->verify($user, $request->code, $request->purpose);

        if (!$user) {
            return response()->json([
                'success' => false,
                'code' => 'OTP_INVALID',
                'message' => 'Code OTP invalide ou expiré.',
            ], 422);
        }

        if (!$result) {
            return response()->json([
                'success' => false, 
                'code' => 'OTP_INVALID',
                'message' => 'Code OTP invalide ou expiré.'
                ], 422);
        }

        if ($request->purpose === 'reset') {
            // Génération du token
            $resetToken = Str::random(64);

            // Hash du token avant stockage
            $hashedToken = Hash::make($resetToken);

            DB::table('password_reset_tokens')->updateOrInsert(
                [
                    'login' => $user->login,
                ],
                [
                    'token' => $hashedToken,
                    'created_at' => now(),
                    'expires_at' => now()->addMinutes(60),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]
            );
            return response()->json([
                'success' => true,
                'code' => 'OTP_VERIFIED',
                'message' => 'Code OTP vérifié.',
                'data' => [
                    'user_uuid' => $user->uuid_user,
                    'reset_token' => $resetToken
                ]
            ]);
        }

        return response()->json([
                'success' => true,
                'code' => 'OTP_VERIFIED',
                'message' => 'Code OTP vérifié.'
            ]);
    }
}
