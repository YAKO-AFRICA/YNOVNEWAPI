<?php
namespace App\Services\Api\Ynov\Auth;

use App\Models\Api\Ynov\parameter\IpRestriction;

class IpRestrictionService
{
    public function isAllowed(string $ip): bool
    {
        $hasWhitelist = IpRestriction::where('type', 'whitelist')->where('status', 'actif')->exists();
        if ($hasWhitelist) {
            return IpRestriction::where('ip_address', $ip)->where('type', 'whitelist')->where('status', 'actif')->exists();
        }
        return !IpRestriction::where('ip_address', $ip)->where('type', 'blacklist')->where('status', 'actif')->exists();
    }
}