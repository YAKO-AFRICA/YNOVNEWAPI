<?php
namespace App\Services\Api\Ynov\Auth;

use App\Mail\Api\Ynov\TwoFactorRecoveryCodesMail;
use App\Models\Api\Ynov\parameter\TwoFactorRecoveryCode;
use App\Models\Api\Ynov\parameter\User;
use App\Services\Api\Ynov\Auth\OtpService;
use App\Services\SMSService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

// class TwoFactorService
// {
//     private Google2FA $google2fa;

//     public function __construct()
//     {
//         $this->google2fa = new Google2FA();
//     }

//     public function generateSecret(): string
//     {
//         return $this->google2fa->generateSecretKey();
//     }

//     public function getQRCodeUrl(string $company, string $email, string $secret): string
//     {
//         return $this->google2fa->getQRCodeUrl($company, $email, $secret);
//     }

//     public function verify(string $secret, string $code): bool
//     {
//         return $this->google2fa->verifyKey($secret, $code);
//     }

//     public function generateRecoveryCodes(User $user): array
//     {
//         $codes = [];
//         $hashed = [];
//         for ($i = 0; $i < 8; $i++) {
//             $plain = Str::random(10);
//             $codes[] = $plain;
//             $hashed[] = [
//                 'user_uuid' => $user->uuid_user,
//                 'code' => Hash::make($plain),
//                 'expires_at' => now()->addMonths(6),
//                 'created_at' => now(),
//             ];
//         }
//         TwoFactorRecoveryCode::insert($hashed);
//         return $codes;
//     }

//     public function verifyRecoveryCode(User $user, string $code): bool
//     {
//         $records = TwoFactorRecoveryCode::where('user_uuid', $user->uuid_user)
//             ->where('is_used', false)
//             ->where(function ($q) {
//                 $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
//             })
//             ->get();

//         foreach ($records as $record) {
//             if (Hash::check($code, $record->code)) {
//                 $record->update(['is_used' => true, 'used_at' => now()]);
//                 return true;
//             }
//         }
//         return false;
//     }
// }

class TwoFactorService
{
    private Google2FA $google2fa;
    private const RECOVERY_CODE_COUNT = 8;
    private const RECOVERY_CODE_LENGTH = 10;

    public function __construct(
        private SMSService $SMSService,
        private OtpService $otpService
    ) {
        $this->google2fa = new Google2FA();
    }

    /**
     * Générer un secret pour Google Authenticator
     */
    public function generateSecret(): string
    {
        return $this->google2fa->generateSecretKey();
    }

    /**
     * Obtenir l'URL du QR Code
     */
    public function getQRCodeUrl(string $company, string $email, string $secret): string
    {
        return $this->google2fa->getQRCodeUrl($company, $email, $secret);
    }

    /**
     * Vérifier un code TOTP
     */
    public function verify(string $secret, string $code): bool
    {
        return $this->google2fa->verifyKey($secret, $code);
    }

    /**
     * Générer des codes de récupération
     */
    public function generateRecoveryCodes(User $user): array
    {
        $codes = [];
        $hashedCodes = [];

        for ($i = 0; $i < self::RECOVERY_CODE_COUNT; $i++) {
            $plain = Str::random(self::RECOVERY_CODE_LENGTH);
            $codes[] = $plain;
            
            $hashedCodes[] = [
                'uuid' => (string) Str::uuid(),
                'user_uuid' => $user->uuid_user,
                'code' => Hash::make($plain),
                'expires_at' => now()->addMonths(6),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Supprimer les anciens codes
        TwoFactorRecoveryCode::where('user_uuid', $user->uuid_user)->delete();
        
        // Insérer les nouveaux codes
        TwoFactorRecoveryCode::insert($hashedCodes);

        return $codes;
    }

    /**
     * Vérifier un code de récupération
     */
    public function verifyRecoveryCode(User $user, string $code): bool
    {
        $record = TwoFactorRecoveryCode::where('user_uuid', $user->uuid_user)
            ->where('is_used', false)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            })
            ->first();

        if (!$record) {
            return false;
        }

        if (Hash::check($code, $record->code)) {
            $record->update([
                'is_used' => true,
                'used_at' => now(),
            ]);
            return true;
        }

        return false;
    }

    /**
     * Compter les codes de récupération disponibles
     */
    public function countAvailableRecoveryCodes(User $user): int
    {
        return TwoFactorRecoveryCode::where('user_uuid', $user->uuid_user)
            ->where('is_used', false)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            })
            ->count();
    }

    /**
     * Envoyer les codes de récupération par email
     */
    public function sendRecoveryCodes(User $user): void
    {
        $codes = TwoFactorRecoveryCode::where('user_uuid', $user->uuid_user)
            ->where('is_used', false)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            })
            ->get();

        // Décrypter les codes (on ne peut pas les décrypter, on les régénère)
        // En pratique, on envoie les codes en clair au moment de l'activation
        // Pour un renvoi sécurisé, on régénère de nouveaux codes
        $newCodes = $this->generateRecoveryCodes($user);
        
        Mail::to($user->email)->queue(new TwoFactorRecoveryCodesMail(
            $user->details,
            $newCodes
        ));
    }

    /**
     * Régénérer les codes de récupération
     */
    public function regenerateRecoveryCodes(User $user): array
    {
        return $this->generateRecoveryCodes($user);
    }

    /**
     * Envoyer un OTP par le canal spécifié
     */
    public function sendOtp(
        User $user,
        string $channel,
        string $purpose,
        ?string $ip = null,
        ?string $ua = null,
        int $expiryMinutes = 5,
        array $data = []
    ): array
    {
        // Générer le code OTP
        // $code = $this->generateOtpCode($user, $channel, $purpose);

        // $result = $this->otpService->sendOtp($user, $channel, $purpose);
         $result = $this->otpService->sendOtp(
            user: $user,
            channel: $channel,
            purpose: $purpose,
            ip: $ip,
            ua: $ua,
            expiryMinutes: $expiryMinutes,
            data: $data
        );

        return $result;

        // // EMAIL
        // if ($channel === 'email') {
        //     $email = $data['login'] ?? $user->email ?? $user->details?->email_pro;
            
        //     if (empty($email)) {
        //         return [
        //             'success' => false,
        //             'code' => 'EMAIL_INVALID',
        //             'message' => 'Aucune adresse email disponible.'
        //         ];
        //     }

        //     Mail::to($email)->queue(new OtpMail(
        //         $user->details,
        //         $purpose,
        //         $code,
        //         $expiryMinutes
        //     ));

        //     return [
        //         'success' => true,
        //         'code' => 'OTP_SENT',
        //         'message' => 'Code OTP envoyé par email.',
        //         'data' => [
        //             'channel' => 'email',
        //             'purpose' => $purpose,
        //             'expires_in' => $expiryMinutes,
        //         ]
        //     ];
        // }

        // // SMS
        // if ($channel === 'sms') {
        //     $phone = preg_replace('/\D/', '', $data['tel'] ?? $user->details?->mobile_1 ?? '');
        //     $phone = substr($phone, -10);

        //     if (strlen($phone) !== 10) {
        //         return [
        //             'success' => false,
        //             'code' => 'TELEPHONE_INVALID',
        //             'message' => 'Numéro de téléphone invalide.'
        //         ];
        //     }

        //     $phoneNumber = '+225' . $phone;
        //     $message = "Votre code OTP YNOV est : {$code} (valable {$expiryMinutes} min)";

        //     // $this->SMSService->sendSmsByInfobipAPI($phoneNumber, $message);
        //     if (str_starts_with($phone, '05')) {
        //         $this->SMSService->sendSmsBySayeliAPI($phoneNumber, $message);
        //     } else {
        //         $this->SMSService->sendSmsByInfobipAPI($phoneNumber, $message);
        //     }

        //     return [
        //         'success' => true,
        //         'code' => 'OTP_SENT',
        //         'message' => 'Code OTP envoyé par SMS.',
        //         'data' => [
        //             'channel' => 'sms',
        //             'purpose' => $purpose,
        //             'expires_in' => $expiryMinutes,
        //         ]
        //     ];
        // }

        // // WHATSAPP (à configurer)
        // if ($channel === 'whatsapp') {
        //     return [
        //         'success' => false,
        //         'code' => 'WHATSAPP_NOT_CONFIGURED',
        //         'message' => 'Le canal WhatsApp n\'est pas encore configuré.',
        //     ];
        // }

        // return [
        //     'success' => false,
        //     'code' => 'CHANNEL_INVALID',
        //     'message' => 'Canal d\'envoi invalide.',
        // ];
    }

    /**
     * Générer un code OTP
     */
    // private function generateOtpCode(User $user, string $channel, string $purpose): string
    // {
    //     $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

    //     OtpCode::create([
    //         'user_uuid' => $user->uuid_user,
    //         'code' => Hash::make($code),
    //         'channel' => $channel,
    //         'purpose' => $purpose,
    //         'length' => 6,
    //         'expires_at' => now()->addMinutes(5),
    //     ]);

    //     return $code;
    // }
}