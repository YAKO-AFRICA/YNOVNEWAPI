<?php
namespace App\Services\Api\Ynov\Auth;

use App\Models\Api\Ynov\parameter\GroupNotif;
use App\Models\Api\Ynov\parameter\PasswordHistory;
use App\Models\Api\Ynov\parameter\User;
use App\Services\Api\Ynov\NotificationService;
use Illuminate\Support\Facades\Hash;

// class PasswordService
// {
//     private const EXPIRATION_DAYS = 90;
//     private const HISTORY_SIZE = 5;

//     public function isExpired(User $user): bool
//     {
//         if (!$user->password_expires_at) return false;
//         return now()->gt($user->password_expires_at);
//     }

//     public function validateHistory(User $user, string $password): bool
//     {
//         $hashes = PasswordHistory::where('user_uuid', $user->uuid_user)
//             ->latest()->limit(self::HISTORY_SIZE)->pluck('password_hash');

//         foreach ($hashes as $hash) {
//             if (Hash::check($password, $hash)) return false;
//         }
//         return true;
//     }

//     public function addToHistory(User $user, ?string $ip = null, ?string $ua = null): void
//     {
//         PasswordHistory::create([
//             'user_uuid' => $user->uuid_user,
//             'password_hash' => $user->password,
//             'changed_at' => now(),
//             'ip_address' => $ip,
//             'user_agent' => $ua,
//         ]);

//         $idsToKeep = PasswordHistory::where('user_uuid', $user->uuid_user)
//             ->latest()->limit(self::HISTORY_SIZE)->pluck('id');

//         PasswordHistory::where('user_uuid', $user->uuid_user)->whereNotIn('id', $idsToKeep)->delete();
//     }
// }

class PasswordService
{
    private const EXPIRATION_DAYS = 90;
    private const HISTORY_SIZE = 5;

    public function __construct(
        private NotificationService $notificationService,
    ) {}

    public function isExpired(User $user): bool
    {
        if (!$user->password_expires_at) return false;
        return now()->gt($user->password_expires_at);
    }

    public function validateHistory(User $user, string $password): bool
    {
        $hashes = PasswordHistory::where('user_uuid', $user->uuid_user)
            ->latest()->limit(self::HISTORY_SIZE)->pluck('password_hash');

        foreach ($hashes as $hash) {
            if (Hash::check($password, $hash)) return false;
        }
        return true;
    }

    public function addToHistory(User $user, ?string $ip = null, ?string $ua = null): void
    {
        PasswordHistory::create([
            'user_uuid' => $user->uuid_user,
            'password_hash' => $user->password,
            'changed_at' => now(),
            'ip_address' => $ip,
            'user_agent' => $ua,
        ]);

        $idsToKeep = PasswordHistory::where('user_uuid', $user->uuid_user)
            ->latest()->limit(self::HISTORY_SIZE)->pluck('id');

        PasswordHistory::where('user_uuid', $user->uuid_user)->whereNotIn('id', $idsToKeep)->delete();
    }

    public function notifyPasswordChange(User $user, string $type = 'change'): void
    {
        $titles = [
            'forgot' => '🔑 Mot de passe oublé',
            'change' => '🔑 Mot de passe changé',
            'reset' => '🔄 Mot de passe réinitialisé',
            'first_login' => '🎉 Premier mot de passe défini',
        ];

        $bodies = [
            'forgot' => 'Vous avez oublé votre mot de passe. Veuillez le changer pour protéger votre compte.',
            'change' => 'Votre mot de passe a été changé avec succès.',
            'reset' => 'Votre mot de passe a été réinitialisé avec succès.',
            'first_login' => 'Votre premier mot de passe a été défini avec succès.',
        ];

        $this->notificationService->create([
            'user_uuid' => $user->uuid_user,
            'group_notif_uuid' => $this->getSecurityGroupUuid(),
            'title' => $titles[$type] ?? $titles['change'],
            'body' => $bodies[$type] ?? $bodies['change'],
            'type' => 'security',
            'metadata' => [
                'type' => $type,
                'changed_at' => now()->toISOString(),
            ],
            'channel' => 'database',
            'created_by' => null,
        ]);
    }

    public function notifyPasswordExpirationWarning(User $user, int $daysRemaining): void
    {
        $this->notificationService->create([
            'user_uuid' => $user->uuid_user,
            'group_notif_uuid' => $this->getSecurityGroupUuid(),
            'title' => '⏰ Expiration du mot de passe',
            'body' => "Votre mot de passe expirera dans {$daysRemaining} jours. Veuillez le changer avant expiration.",
            'type' => 'security',
            'metadata' => [
                'days_remaining' => $daysRemaining,
                'expires_at' => $user->password_expires_at?->toISOString(),
            ],
            'channel' => 'database',
            'created_by' => null,
        ]);
    }

    private function getSecurityGroupUuid(): ?string
    {
        $group = GroupNotif::where('code', 'securite')->first();
        return $group?->uuid_group_notif;
    }
}