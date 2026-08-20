<?php
// app/Http/Middleware/Api/Ynov/DynamicPermissionAnyMiddleware.php

namespace App\Http\Middleware\Api\Ynov;

use App\Models\Api\Ynov\parameter\ActivityLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DynamicPermissionAnyMiddleware
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

        // Vérifier si l'utilisateur a au moins une des permissions
        foreach ($permissionCodes as $permissionCode) {
            if ($this->userHasPermission($user, $permissionCode)) {
                return $next($request);
            }
        }

        // Log de la tentative
        ActivityLog::log([
            'user_uuid' => $user->uuid_user,
            'action' => 'permission_denied',
            'action_type' => 'security',
            'module' => 'auth',
            'description' => "Tentative sans permission (any) : " . implode(', ', $permissionCodes),
            'level' => 'warning',
            'metadata' => [
                'required_permissions' => $permissionCodes,
                'route' => $request->route()->getName() ?? $request->path(),
                'method' => $request->method(),
            ],
        ]);

        return response()->json([
            'status' => 'error',
            'message' => 'Vous n\'avez pas les permissions nécessaires pour acceder à cette ressource.',
            'code' => 'PERMISSION_DENIED',
            'required_permissions' => $permissionCodes
        ], 403);
    }

    private function userHasPermission($user, string $permissionCode): bool
    {
        $cacheKey = "user_permissions_{$user->uuid_user}";
        
        $userPermissions = Cache::remember($cacheKey, 3600, function () use ($user) {
            return $user->role->permissions()
                ->where('status', 'actif')
                ->pluck('code')
                ->toArray();
        });

        return in_array($permissionCode, $userPermissions);
    }
}