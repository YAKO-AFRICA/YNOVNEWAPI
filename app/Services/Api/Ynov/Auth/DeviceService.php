<?php
namespace App\Services\Api\Ynov\Auth;

use App\Mail\Api\Ynov\NewDeviceMail;
use App\Models\Api\Ynov\parameter\GroupNotif;
use App\Models\Api\Ynov\parameter\User;
use App\Models\Api\Ynov\parameter\UserDevice;
use App\Services\Api\Ynov\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class DeviceService
{
    public function __construct(
        private NotificationService $notificationService,
    ) {}

    public function fingerprint(Request $request): string
    {
        $data = $request->ip() . '|' . $request->userAgent() . '|' . $request->header('Accept-Language', '');
        return hash('sha256', $data);
    }

    /**
     * Détecte le navigateur à partir du User-Agent
     */
    public function detectBrowser(string $userAgent): ?string
    {
        $browsers = [
            'Chrome' => '/Chrome/i',
            'Firefox' => '/Firefox/i',
            'Safari' => '/Safari/i',
            'Edge' => '/Edg/i',
            'Opera' => '/OPR/i',
            'IE' => '/MSIE|Trident/i',
        ];

        foreach ($browsers as $name => $pattern) {
            if (preg_match($pattern, $userAgent)) {
                return $name;
            }
        }

        return null;
    }

    /**
     * Détecte l'OS à partir du User-Agent
     */
    public function detectOS(string $userAgent): ?string
    {
        $oses = [
            'Windows' => '/Windows/i',
            'Mac' => '/Macintosh|Mac OS/i',
            'Linux' => '/Linux/i',
            'Android' => '/Android/i',
            'iOS' => '/iPhone|iPad|iPod/i',
            'Ubuntu' => '/Ubuntu/i',
        ];

        foreach ($oses as $name => $pattern) {
            if (preg_match($pattern, $userAgent)) {
                return $name;
            }
        }

        return null;
    }

    /**
     * Détecte la localisation à partir de l'adresse IP
     * Utilise plusieurs API de géolocalisation en fallback
     */
    public function detectLocation(string $ip): ?string
    {
        // Ne pas détecter les IPs locales
        if ($this->isLocalIP($ip)) {
            return null;
        }

        // Liste des APIs à essayer en ordre de préférence
        $apis = [
            [
                'url' => "http://ip-api.com/json/{$ip}?fields=country,city,status",
                'parser' => function($data) {
                    if (($data['status'] ?? '') === 'success') {
                        $parts = array_filter([$data['city'] ?? null, $data['country'] ?? null]);
                        return implode(', ', $parts) ?: null;
                    }
                    return null;
                }
            ],
            [
                'url' => "https://ipapi.co/{$ip}/json/",
                'parser' => function($data) {
                    if (!isset($data['error'])) {
                        $parts = array_filter([$data['city'] ?? null, $data['country_name'] ?? null]);
                        return implode(', ', $parts) ?: null;
                    }
                    return null;
                }
            ],
        ];

        foreach ($apis as $api) {
            try {
                $response = Http::timeout(3)->get($api['url']);

                if ($response->successful()) {
                    $location = ($api['parser'])($response->json());
                    if ($location) {
                        return $location;
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Erreur lors de la détection de localisation', [
                    'ip' => $ip,
                    'api' => $api['url'],
                    'error' => $e->getMessage(),
                ]);
                continue; // Essayer l'API suivante
            }
        }

        return null;
    }

    /**
     * Vérifie si l'IP est une adresse locale
     */
    private function isLocalIP(string $ip): bool
    {
        return in_array($ip, ['127.0.0.1', '::1', 'localhost']) ||
               str_starts_with($ip, '192.168.') ||
               str_starts_with($ip, '10.') ||
               str_starts_with($ip, '172.16.');
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

        if ($result->wasRecentlyCreated) {
            // Créer une notification pour le nouvel appareil
            $this->notificationService->create([
                'user_uuid' => $user->uuid_user,
                'group_notif_uuid' => $this->getSecurityGroupUuid(),
                'title' => '📱 Nouvel appareil détecté',
                'body' => "Un nouvel appareil a été connecté à votre compte : {$info['device_name']} depuis l'adresse IP {$info['ip']}.",
                'type' => 'security',
                'metadata' => [
                    'device_name' => $info['device_name'],
                    'device_type' => $info['device_type'] ?? null,
                    'ip_address' => $info['ip'],
                    'location' => $info['location'] ?? null,
                    'os' => $info['os'] ?? null,
                    'browser' => $info['browser'] ?? null,
                ],
                'channel' => 'database',
                'created_by' => null,
            ]);

            if ($user->email) {
                Mail::to($user->email)->queue(new NewDeviceMail($user->fresh('details'), $result));
            }
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
            // Créer une notification pour la révocation de l'appareil
            $this->notificationService->create([
                'user_uuid' => $user->uuid_user,
                'group_notif_uuid' => $this->getSecurityGroupUuid(),
                'title' => '🔒 Appareil révoqué',
                'body' => "L'appareil {$device->device_name} a été révoqué de votre compte.",
                'type' => 'security',
                'metadata' => [
                    'device_name' => $device->device_name,
                    'device_type' => $device->device_type,
                    'revoked_at' => now()->toISOString(),
                ],
                'channel' => 'database',
                'created_by' => null,
            ]);

            return $device->delete() > 0;
        }

        return false;
    }

    private function getSecurityGroupUuid(): ?string
    {
        $group = GroupNotif::where('code', 'securite')->first();
        return $group?->uuid_group_notif;
    }
}