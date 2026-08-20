<?php
namespace App\Services\Api\Ynov\Auth;

use App\Mail\Api\Ynov\NewDeviceMail;
use App\Models\Api\Ynov\parameter\User;
use App\Models\Api\Ynov\parameter\UserDevice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class DeviceService
{
    public function fingerprint(Request $request): string
    {
        $data = $request->ip() . '|' . $request->userAgent() . '|' . $request->header('Accept-Language', '');
        return hash('sha256', $data);
    }

    public function isTrusted(User $user, string $fingerprint): bool
    {
        return UserDevice::where('user_uuid', $user->uuid_user)
            ->where('fingerprint', $fingerprint)
            ->where('is_trusted', true)
            ->exists();
    }

    public function updateOrCreate(User $user, array $info): UserDevice
    {
        $result = UserDevice::updateOrCreate(
            ['user_uuid' => $user->uuid_user, 'fingerprint' => $info['fingerprint']],
            [
                'uuid_device' => (string) Str::uuid(),
                'device_name' => $info['device_name'] ?? 'Appareil inconnu',
                'device_type' => $info['device_type'] ?? null,
                'os' => $info['os'] ?? null,
                'browser' => $info['browser'] ?? null,
                'ip_address' => $info['ip'],
                'user_agent' => $info['user_agent'],
                'location' => $info['location'] ?? null,
                'last_used_at' => now(),
            ]
        );

        // wasRecentlyCreated est fiable et atomique (pas de race condition
        // contrairement à un exists() suivi d'un updateOrCreate séparé).
        if ($result->wasRecentlyCreated && $user->email) {
            Mail::to($user->email)->queue(new NewDeviceMail($user->fresh('details'), $result));
        }
        return $result;
    }

    public function trust(User $user, string $fingerprint): void
    {
        UserDevice::where('user_uuid', $user->uuid_user)
            ->where('fingerprint', $fingerprint)
            ->update(['is_trusted' => true, 'trusted_at' => now()]);
    }

    public function revoke(User $user, string $uuidDevice): bool
    {
        $device = UserDevice::where('user_uuid', $user->uuid_user)
            ->where('uuid_device', $uuidDevice)
            ->first();

        if ($device) {
            $device->delete();
            return true;
        }
        return false;
    }
}