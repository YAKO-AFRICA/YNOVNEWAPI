<?php
// app/Http/Middleware/Api/Ynov/DynamicPermissionAllMiddleware.php

namespace App\Http\Middleware\Api\Ynov;

use App\Models\Api\Ynov\parameter\ActivityLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DynamicPermissionAllMiddleware
{
    public function handle(Request $request, Closure $next, ...$permissionCodes)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Non authentifié',
                'code' => 'UNAUTHENTICATED'
            ], 401);
        }

        // Super Admin a accès à tout
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Vérifier si l'utilisateur a TOUTES les permissions
        $cacheKey = "user_permissions_{$user->uuid_user}";
        
        $userPermissions = Cache::remember($cacheKey, 3600, function () use ($user) {
            return $user->role->permissions()
                ->where('status', 'actif')
                ->pluck('code')
                ->toArray();
        });

        $missingPermissions = [];
        foreach ($permissionCodes as $permissionCode) {
            if (!in_array($permissionCode, $userPermissions)) {
                $missingPermissions[] = $permissionCode;
            }
        }

        if (!empty($missingPermissions)) {
            // Log de la tentative
            ActivityLog::log([
                'user_uuid' => $user->uuid_user,
                'action' => 'permission_denied',
                'action_type' => 'security',
                'module' => 'auth',
                'description' => "Tentative sans permission (all) : " . implode(', ', $missingPermissions),
                'level' => 'warning',
                'metadata' => [
                    'missing_permissions' => $missingPermissions,
                    'required_permissions' => $permissionCodes,
                    'route' => $request->route()->getName() ?? $request->path(),
                    'method' => $request->method(),
                ],
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Vous n\'avez pas toutes les permissions nécessaires',
                'code' => 'PERMISSION_DENIED',
                'missing_permissions' => $missingPermissions,
                'required_permissions' => $permissionCodes
            ], 403);
        }

        return $next($request);
    }
}