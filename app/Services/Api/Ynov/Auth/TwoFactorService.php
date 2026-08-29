<?php
// namespace App\Services\Api\Ynov\Auth;

// use App\Mail\Api\Ynov\TwoFactorRecoveryCodesMail;
// use App\Models\Api\Ynov\parameter\TwoFactorRecoveryCode;
// use App\Models\Api\Ynov\parameter\User;
// use App\Services\Api\Ynov\Auth\OtpService;
// use App\Services\SMSService;
// use Illuminate\Support\Facades\Hash;
// use Illuminate\Support\Facades\Mail;
// use Illuminate\Support\Str;
// use PragmaRX\Google2FA\Google2FA;

// class TwoFactorService
// {
//     private Google2FA $google2fa;
//     private const RECOVERY_CODE_COUNT = 8;
//     private const RECOVERY_CODE_LENGTH = 10;

//     public function __construct(
//         private SMSService $SMSService,
//         private OtpService $otpService
//     ) {
//         $this->google2fa = new Google2FA();
//     }

//     /**
//      * Générer un secret pour Google Authenticator
//      */
//     public function generateSecret(): string
//     {
//         return $this->google2fa->generateSecretKey();
//     }

//     /**
//      * Obtenir l'URL du QR Code
//      */
//     public function getQRCodeUrl(string $company, string $email, string $secret): string
//     {
//         return $this->google2fa->getQRCodeUrl($company, $email, $secret);
//     }

//     /**
//      * Vérifier un code TOTP
//      */
//     public function verify(string $secret, string $code): bool
//     {
//         return $this->google2fa->verifyKey($secret, $code);
//     }

//     /**
//      * Générer des codes de récupération
//      */
//     public function generateRecoveryCodes(User $user): array
//     {
//         $codes = [];
//         $hashedCodes = [];

//         for ($i = 0; $i < self::RECOVERY_CODE_COUNT; $i++) {
//             $plain = Str::random(self::RECOVERY_CODE_LENGTH);
//             $codes[] = $plain;
            
//             $hashedCodes[] = [
//                 'uuid' => (string) Str::uuid(),
//                 'user_uuid' => $user->uuid_user,
//                 'code' => Hash::make($plain),
//                 'expires_at' => now()->addMonths(6),
//                 'created_at' => now(),
//                 'updated_at' => now(),
//             ];
//         }

//         // Supprimer les anciens codes
//         TwoFactorRecoveryCode::where('user_uuid', $user->uuid_user)->delete();
        
//         // Insérer les nouveaux codes
//         TwoFactorRecoveryCode::insert($hashedCodes);

//         return $codes;
//     }

//     /**
//      * Vérifier un code de récupération
//      */
//     public function verifyRecoveryCode(User $user, string $code): bool
//     {
//         $record = TwoFactorRecoveryCode::where('user_uuid', $user->uuid_user)
//             ->where('is_used', false)
//             ->where(function ($q) {
//                 $q->whereNull('expires_at')
//                   ->orWhere('expires_at', '>', now());
//             })
//             ->first();

//         if (!$record) {
//             return false;
//         }

//         if (Hash::check($code, $record->code)) {
//             $record->update([
//                 'is_used' => true,
//                 'used_at' => now(),
//             ]);
//             return true;
//         }

//         return false;
//     }

//     /**
//      * Compter les codes de récupération disponibles
//      */
//     public function countAvailableRecoveryCodes(User $user): int
//     {
//         return TwoFactorRecoveryCode::where('user_uuid', $user->uuid_user)
//             ->where('is_used', false)
//             ->where(function ($q) {
//                 $q->whereNull('expires_at')
//                   ->orWhere('expires_at', '>', now());
//             })
//             ->count();
//     }

//     /**
//      * Envoyer les codes de récupération par email
//      */
//     public function sendRecoveryCodes(User $user): void
//     {
//         $codes = TwoFactorRecoveryCode::where('user_uuid', $user->uuid_user)
//             ->where('is_used', false)
//             ->where(function ($q) {
//                 $q->whereNull('expires_at')
//                   ->orWhere('expires_at', '>', now());
//             })
//             ->get();

//         // Décrypter les codes (on ne peut pas les décrypter, on les régénère)
//         // En pratique, on envoie les codes en clair au moment de l'activation
//         // Pour un renvoi sécurisé, on régénère de nouveaux codes
//         $newCodes = $this->generateRecoveryCodes($user);
        
//         Mail::to($user->email)->queue(new TwoFactorRecoveryCodesMail(
//             $user->details,
//             $newCodes
//         ));
//     }

//     /**
//      * Régénérer les codes de récupération
//      */
//     public function regenerateRecoveryCodes(User $user): array
//     {
//         return $this->generateRecoveryCodes($user);
//     }

//     /**
//      * Envoyer un OTP par le canal spécifié
//      */
//     public function sendOtp(
//         User $user,
//         string $channel,
//         string $purpose,
//         ?string $ip = null,
//         ?string $ua = null,
//         int $expiryMinutes = 5,
//         array $data = []
//     ): array
//     {
//         // Générer le code OTP
//         // $code = $this->generateOtpCode($user, $channel, $purpose);

//         // $result = $this->otpService->sendOtp($user, $channel, $purpose);
//          $result = $this->otpService->sendOtp(
//             user: $user,
//             channel: $channel,
//             purpose: $purpose,
//             ip: $ip,
//             ua: $ua,
//             expiryMinutes: $expiryMinutes,
//             data: $data
//         );

//         return $result;

//     }
// }

namespace App\Services\Api\Ynov\Auth;

use App\Mail\Api\Ynov\TwoFactorRecoveryCodesMail;
use App\Models\Api\Ynov\parameter\GroupNotif;
use App\Models\Api\Ynov\parameter\TwoFactorRecoveryCode;
use App\Models\Api\Ynov\parameter\User;
use App\Services\Api\Ynov\NotificationService;
use App\Services\SMSService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorService
{
    private Google2FA $google2fa;
    private const RECOVERY_CODE_COUNT = 8;
    private const RECOVERY_CODE_LENGTH = 10;

    public function __construct(
        private SMSService $SMSService,
        private OtpService $otpService,
        private NotificationService $notificationService,
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

        TwoFactorRecoveryCode::where('user_uuid', $user->uuid_user)->delete();
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
        $newCodes = $this->generateRecoveryCodes($user);
        
        // Créer une notification
        $this->notificationService->create([
            'user_uuid' => $user->uuid_user,
            'group_notif_uuid' => $this->getSecurityGroupUuid(),
            'title' => '🔑 Codes de récupération 2FA',
            'body' => 'De nouveaux codes de récupération 2FA ont été générés pour votre compte.',
            'type' => 'security',
            'metadata' => [
                'codes_count' => count($newCodes),
                'sent_at' => now()->toISOString(),
            ],
            'channel' => 'database',
            'created_by' => null,
        ]);
        
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
        $codes = $this->generateRecoveryCodes($user);
        
        // Créer une notification
        $this->notificationService->create([
            'user_uuid' => $user->uuid_user,
            'group_notif_uuid' => $this->getSecurityGroupUuid(),
            'title' => '🔄 Codes de récupération régénérés',
            'body' => 'Vos codes de récupération 2FA ont été régénérés avec succès.',
            'type' => 'security',
            'metadata' => [
                'codes_count' => count($codes),
                'regenerated_at' => now()->toISOString(),
            ],
            'channel' => 'database',
            'created_by' => null,
        ]);
        
        return $codes;
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
        $result = $this->otpService->sendOtp(
            user: $user,
            channel: $channel,
            purpose: $purpose,
            ip: $ip,
            ua: $ua,
            expiryMinutes: $expiryMinutes,
            data: $data
        );

        if ($result['success']) {
            // Créer une notification pour l'envoi OTP
            $this->notificationService->create([
                'user_uuid' => $user->uuid_user,
                'group_notif_uuid' => $this->getSecurityGroupUuid(),
                'title' => '📧 Code OTP envoyé',
                'body' => "Un code OTP a été envoyé via {$channel} pour {$purpose}.",
                'type' => 'security',
                'metadata' => [
                    'channel' => $channel,
                    'purpose' => $purpose,
                    'expires_in' => $expiryMinutes,
                    'ip_address' => $ip,
                ],
                'channel' => 'database',
                'created_by' => null,
            ]);
        }

        return $result;
    }

    private function getSecurityGroupUuid(): ?string
    {
        $group = GroupNotif::where('code', 'securite')->first();
        return $group?->uuid_group_notif;
    }
}