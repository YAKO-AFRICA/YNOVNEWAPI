<?php
namespace App\Services\Api\Ynov\Auth;

use App\Models\Api\Ynov\parameter\TwoFactorRecoveryCode;
use App\Models\Api\Ynov\parameter\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorService
{
    private Google2FA $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    public function generateSecret(): string
    {
        return $this->google2fa->generateSecretKey();
    }

    public function getQRCodeUrl(string $company, string $email, string $secret): string
    {
        return $this->google2fa->getQRCodeUrl($company, $email, $secret);
    }

    public function verify(string $secret, string $code): bool
    {
        return $this->google2fa->verifyKey($secret, $code);
    }

    public function generateRecoveryCodes(User $user): array
    {
        $codes = [];
        $hashed = [];
        for ($i = 0; $i < 8; $i++) {
            $plain = Str::random(10);
            $codes[] = $plain;
            $hashed[] = [
                'user_uuid' => $user->uuid_user,
                'code' => Hash::make($plain),
                'expires_at' => now()->addMonths(6),
                'created_at' => now(),
            ];
        }
        TwoFactorRecoveryCode::insert($hashed);
        return $codes;
    }

    public function verifyRecoveryCode(User $user, string $code): bool
    {
        $records = TwoFactorRecoveryCode::where('user_uuid', $user->uuid_user)
            ->where('is_used', false)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->get();

        foreach ($records as $record) {
            if (Hash::check($code, $record->code)) {
                $record->update(['is_used' => true, 'used_at' => now()]);
                return true;
            }
        }
        return false;
    }
}