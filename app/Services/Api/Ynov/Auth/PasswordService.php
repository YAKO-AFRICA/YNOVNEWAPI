<?php
namespace App\Services\Api\Ynov\Auth;

use App\Models\Api\Ynov\parameter\PasswordHistory;
use App\Models\Api\Ynov\parameter\User;
use Illuminate\Support\Facades\Hash;

class PasswordService
{
    private const EXPIRATION_DAYS = 90;
    private const HISTORY_SIZE = 5;

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
}