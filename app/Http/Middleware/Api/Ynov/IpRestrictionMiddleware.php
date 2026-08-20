<?php
namespace App\Http\Middleware\Api\Ynov;

use App\Services\Api\Ynov\Auth\IpRestrictionService;
use Closure;
use Illuminate\Http\Request;

class IpRestrictionMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!app(IpRestrictionService::class)->isAllowed($request->ip())) {
            return response()->json([
                'success' => false,
                'code' => 'IP_BLOCKED',
                'message' => 'Accès refusé depuis cette adresse IP.',
            ], 403);
        }

        return $next($request);
    }
}