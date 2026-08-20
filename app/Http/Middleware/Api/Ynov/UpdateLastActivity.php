<?php
// namespace App\Http\Middleware\Api\Ynov;

// use Closure;
// use Illuminate\Http\Request;

// class UpdateLastActivity
// {
//     public function handle(Request $request, Closure $next)
//     {
//         if ($user = $request->user()) {
//             $user->update(['last_activity_at' => now()]);
//         }

//         return $next($request);
//     }
// }

namespace App\Http\Middleware\Api\Ynov;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * ================================================================
 * CORRECTION #25 : Optimisation de la mise à jour de last_activity_at
 * Utilisation du cache pour limiter les écritures en base de données
 * ================================================================
 */
class UpdateLastActivity
{
    /**
     * Nombre de secondes entre deux mises à jour
     */
    private const UPDATE_INTERVAL = 60;

    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user) {
            // ================================================================
            // CORRECTION #25 : Utilisation du cache pour limiter les écritures
            // ================================================================
            $cacheKey = "user_last_activity_{$user->uuid_user}";
            $lastUpdate = Cache::get($cacheKey);

            if (!$lastUpdate || now()->diffInSeconds($lastUpdate) >= self::UPDATE_INTERVAL) {
                // Mise à jour en base
                $user->update(['last_activity_at' => now()]);

                // Mettre à jour le cache
                Cache::put($cacheKey, now(), self::UPDATE_INTERVAL);
            }
        }

        return $next($request);
    }
}