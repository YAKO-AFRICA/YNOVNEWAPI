<?php
namespace App\Services\Api\Ynov\Auth;

use App\Models\Api\Ynov\parameter\OtpCode;
use App\Models\Api\Ynov\parameter\User;
use Illuminate\Support\Facades\Hash;

class OtpService
{
    public function generate(User $user, string $channel, string $purpose, ?string $ip = null, ?string $ua = null): string
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        OtpCode::create([
            'user_uuid' => $user->uuid_user,
            'code' => Hash::make($code),
            'code_plain' => $code,
            'channel' => $channel,
            'purpose' => $purpose,
            'length' => 6,
            'expires_at' => now()->addMinutes(10),
            'ip_address' => $ip,
            'user_agent' => $ua,
        ]);

        return $code;
    }

    public function verify(User $user, string $code, string $purpose): bool
    {
        $record = OtpCode::where('user_uuid', $user->uuid_user)
            ->where('purpose', $purpose)
            ->where('is_valid', true)
            ->where('is_used', false)
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (!$record) return false;

        if (Hash::check($code, $record->code)) {
            $record->update(['is_used' => true, 'used_at' => now()]);
            return true;
        }

        $record->incrementAttempts();
        return false;
    }
}

// namespace App\Services\Api\Ynov\Auth;

// use App\Models\Api\Ynov\parameter\OtpCode;
// use App\Models\Api\Ynov\parameter\User;
// use Illuminate\Support\Facades\Hash;

// class OtpService
// {
//     private const MAX_ATTEMPTS = 3;
//     private const DEFAULT_EXPIRY_MINUTES = 10;
//     private const CODE_LENGTH = 6;

//     /**
//      * Générer un nouveau code OTP
//      */
//     public function generate(
//         User $user,
//         string $channel,
//         string $purpose,
//         ?string $ip = null,
//         ?string $ua = null,
//         int $expiryMinutes = self::DEFAULT_EXPIRY_MINUTES
//     ): string {
//         // Générer un code aléatoire de 6 chiffres
//         $code = str_pad((string) random_int(0, 999999), self::CODE_LENGTH, '0', STR_PAD_LEFT);

//         // Hasher le code pour le stockage
//         $hashedCode = Hash::make($code);

//         // Révoquer tous les anciens codes OTP pour ce purpose
//         $this->revokeAll($user, $purpose);

//         // Créer le nouveau code
//         OtpCode::create([
//             'user_uuid' => $user->uuid_user,
//             'code' => $hashedCode,
//             'code_plain' => $code, // Stocké en clair pour l'envoi (SMS/Email)
//             'channel' => $channel,
//             'purpose' => $purpose,
//             'length' => self::CODE_LENGTH,
//             'expires_at' => now()->addMinutes($expiryMinutes),
//             'ip_address' => $ip,
//             'user_agent' => $ua,
//             'is_valid' => true,
//             'is_used' => false,
//             'attempts' => 0,
//             'resend_count' => 0,
//             'last_resend_at' => null,
//         ]);

//         return $code;
//     }

//     /**
//      * Vérifier un code OTP
//      * ================================================================
//      * CORRECTION #6 : Amélioration de la gestion des tentatives
//      * ================================================================
//      */
//     public function verify(User $user, string $code, string $purpose): bool
//     {
//         // Récupérer le dernier code OTP valide pour ce purpose
//         $record = OtpCode::where('user_uuid', $user->uuid_user)
//             ->where('purpose', $purpose)
//             ->where('is_valid', true)
//             ->where('is_used', false)
//             ->where('expires_at', '>', now())
//             ->latest()
//             ->first();

//         if (!$record) {
//             return false;
//         }

//         // ================================================================
//         // CORRECTION #6 : Vérification du nombre de tentatives
//         // ================================================================
//         if ($record->attempts >= self::MAX_ATTEMPTS) {
//             $record->update(['is_valid' => false]);
//             return false;
//         }

//         // Vérifier le code avec Hash::check
//         if (Hash::check($code, $record->code)) {
//             $record->update([
//                 'is_used' => true,
//                 'used_at' => now(),
//             ]);
//             return true;
//         }

//         // ================================================================
//         // CORRECTION #6 : Incrémenter le compteur de tentatives
//         // ================================================================
//         $record->increment('attempts');

//         // Désactiver après le nombre maximum de tentatives
//         if ($record->attempts >= self::MAX_ATTEMPTS) {
//             $record->update(['is_valid' => false]);
//         }

//         return false;
//     }

//     /**
//      * Vérifier si un utilisateur a un OTP valide pour un purpose donné
//      */
//     public function hasValidOtp(User $user, string $purpose): bool
//     {
//         return OtpCode::where('user_uuid', $user->uuid_user)
//             ->where('purpose', $purpose)
//             ->where('is_valid', true)
//             ->where('is_used', false)
//             ->where('expires_at', '>', now())
//             ->exists();
//     }

//     /**
//      * Révoquer tous les OTP d'un utilisateur pour un purpose donné
//      */
//     public function revokeAll(User $user, string $purpose): void
//     {
//         OtpCode::where('user_uuid', $user->uuid_user)
//             ->where('purpose', $purpose)
//             ->where('is_valid', true)
//             ->update(['is_valid' => false]);
//     }

//     /**
//      * Révoquer tous les OTP d'un utilisateur (tous les purposes)
//      */
//     public function revokeAllForUser(User $user): void
//     {
//         OtpCode::where('user_uuid', $user->uuid_user)
//             ->where('is_valid', true)
//             ->update(['is_valid' => false]);
//     }

//     /**
//      * Nettoyer les OTP expirés (à appeler via un job planifié)
//      */
//     public function cleanExpiredOtps(): int
//     {
//         return OtpCode::where('expires_at', '<=', now())
//             ->orWhere('is_valid', false)
//             ->delete();
//     }

//     /**
//      * Obtenir le nombre de tentatives restantes pour un code OTP
//      */
//     public function getRemainingAttempts(User $user, string $purpose): int
//     {
//         $record = OtpCode::where('user_uuid', $user->uuid_user)
//             ->where('purpose', $purpose)
//             ->where('is_valid', true)
//             ->where('is_used', false)
//             ->where('expires_at', '>', now())
//             ->latest()
//             ->first();

//         if (!$record) {
//             return 0;
//         }

//         return max(0, self::MAX_ATTEMPTS - $record->attempts);
//     }

//     /**
//      * Vérifier si un code OTP peut être renvoyé
//      */
//     public function canResend(User $user, string $purpose, int $maxResends = 3, int $cooldownSeconds = 60): bool
//     {
//         $record = OtpCode::where('user_uuid', $user->uuid_user)
//             ->where('purpose', $purpose)
//             ->where('is_valid', true)
//             ->latest()
//             ->first();

//         if (!$record) {
//             return true;
//         }

//         if ($record->resend_count >= $maxResends) {
//             return false;
//         }

//         if ($record->last_resend_at && $record->last_resend_at->diffInSeconds(now()) < $cooldownSeconds) {
//             return false;
//         }

//         return true;
//     }

//     /**
//      * Marquer un code OTP comme renvoyé
//      */
//     public function markResent(User $user, string $purpose): void
//     {
//         $record = OtpCode::where('user_uuid', $user->uuid_user)
//             ->where('purpose', $purpose)
//             ->where('is_valid', true)
//             ->latest()
//             ->first();

//         if ($record) {
//             $record->increment('resend_count');
//             $record->update(['last_resend_at' => now()]);
//         }
//     }

//     /**
//      * Récupérer le code OTP en clair pour un utilisateur (pour l'envoi)
//      * Note : À n'utiliser que pendant le processus d'envoi
//      */
//     public function getPlainCode(User $user, string $purpose): ?string
//     {
//         $record = OtpCode::where('user_uuid', $user->uuid_user)
//             ->where('purpose', $purpose)
//             ->where('is_valid', true)
//             ->where('is_used', false)
//             ->where('expires_at', '>', now())
//             ->latest()
//             ->first();

//         return $record?->code_plain;
//     }
// }