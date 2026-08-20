<?php
// app/Http/Middleware/Api/Ynov/DynamicPermissionMiddleware.php

namespace App\Http\Middleware\Api\Ynov;

use App\Models\Api\Ynov\parameter\ActivityLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DynamicPermissionMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $permissionCode)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Non authentifié',
                'code' => 'UNAUTHENTICATED'
            ], 401);
        }

        // Le Super Admin a accès à tout
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Vérifier si l'utilisateur a la permission
        if (!$this->userHasPermission($user, $permissionCode)) {
            // Log de la tentative
            ActivityLog::log([
                'user_uuid' => $user->uuid_user,
                'action' => 'permission_denied',
                'action_type' => 'security',
                'module' => 'auth',
                'description' => "Tentative sans permission : {$permissionCode}",
                'level' => 'warning',
                'metadata' => [
                    'required_permission' => $permissionCode,
                    'route' => $request->route()->getName() ?? $request->path(),
                    'method' => $request->method(),
                ],
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Vous n\'avez pas la permission nécessaire pour effectuer cette action',
                'code' => 'PERMISSION_DENIED',
                'required_permission' => $permissionCode
            ], 403);
        }

        return $next($request);
    }

    /**
     * Vérifier si l'utilisateur a une permission spécifique
     */
    private function userHasPermission($user, string $permissionCode): bool
    {
        // Vérifier via le rôle de l'utilisateur
        if ($user->role) {
            // Utiliser le cache pour optimiser
            $cacheKey = "user_permissions_{$user->uuid_user}";
            
            $userPermissions = Cache::remember($cacheKey, 3600, function () use ($user) {
                return $user->role->permissions()
                    ->where('status', 'actif')
                    ->pluck('code')
                    ->toArray();
            });

            return in_array($permissionCode, $userPermissions);
        }

        return false;
    }
}


// namespace App\Http\Middleware\Api\Ynov;

// use App\Models\Api\Ynov\parameter\ActivityLog;
// use App\Services\Api\Ynov\PermissionService;
// use Closure;
// use Illuminate\Http\Request;

// /**
//  * ================================================================
//  * CORRECTION #15 : Middleware de permission unifié
//  * 
//  * Utilisation :
//  * - permission:permission.code        → vérifie une permission unique
//  * - permission:any:perm1,perm2,perm3  → vérifie au moins une permission
//  * - permission:all:perm1,perm2,perm3  → vérifie toutes les permissions
//  * ================================================================
//  */
// class DynamicPermissionMiddleware
// {
//     public function __construct(
//         private PermissionService $permissionService
//     ) {}

//     /**
//      * Handle an incoming request.
//      */
//     public function handle(Request $request, Closure $next, string $mode = 'single', ...$permissionCodes)
//     {
//         $user = $request->user();

//         if (!$user) {
//             return response()->json([
//                 'status' => 'error',
//                 'message' => 'Non authentifié',
//                 'code' => 'UNAUTHENTICATED'
//             ], 401);
//         }

//         // ================================================================
//         // Le Super Admin a accès à tout
//         // ================================================================
//         if ($user->isSuperAdmin()) {
//             return $next($request);
//         }

//         // ================================================================
//         // Récupérer les permissions de l'utilisateur via le service centralisé
//         // ================================================================
//         $userPermissions = $this->permissionService->getUserPermissions($user);

//         // ================================================================
//         // Vérification selon le mode
//         // ================================================================
//         $hasPermission = match ($mode) {
//             'any' => $this->hasAnyPermission($permissionCodes, $userPermissions),
//             'all' => $this->hasAllPermissions($permissionCodes, $userPermissions),
//             default => $this->hasSinglePermission($permissionCodes, $userPermissions),
//         };

//         if (!$hasPermission) {
//             // ================================================================
//             // Journaliser la tentative échouée
//             // ================================================================
//             ActivityLog::log([
//                 'user_uuid' => $user->uuid_user,
//                 'action' => 'permission_denied',
//                 'action_type' => 'security',
//                 'module' => 'auth',
//                 'description' => "Tentative sans permission (mode: {$mode}) : " . implode(', ', $permissionCodes),
//                 'level' => 'warning',
//                 'metadata' => [
//                     'required_permissions' => $permissionCodes,
//                     'mode' => $mode,
//                     'route' => $request->route()->getName() ?? $request->path(),
//                     'method' => $request->method(),
//                     'user_permissions' => $userPermissions,
//                 ],
//             ]);

//             return response()->json([
//                 'status' => 'error',
//                 'message' => 'Vous n\'avez pas les permissions nécessaires pour accéder à cette ressource.',
//                 'code' => 'PERMISSION_DENIED',
//                 'required_permissions' => $permissionCodes,
//                 'mode' => $mode,
//             ], 403);
//         }

//         return $next($request);
//     }

//     /**
//      * Vérifier une permission unique
//      */
//     private function hasSinglePermission(array $permissionCodes, array $userPermissions): bool
//     {
//         if (empty($permissionCodes)) {
//             return false;
//         }

//         // Le premier élément est la permission
//         $permissionCode = $permissionCodes[0];
        
//         return in_array($permissionCode, $userPermissions);
//     }

//     /**
//      * Vérifier si l'utilisateur a AU MOINS UNE des permissions
//      */
//     private function hasAnyPermission(array $permissionCodes, array $userPermissions): bool
//     {
//         if (empty($permissionCodes)) {
//             return false;
//         }

//         foreach ($permissionCodes as $permissionCode) {
//             if (in_array($permissionCode, $userPermissions)) {
//                 return true;
//             }
//         }

//         return false;
//     }

//     /**
//      * Vérifier si l'utilisateur a TOUTES les permissions
//      */
//     private function hasAllPermissions(array $permissionCodes, array $userPermissions): bool
//     {
//         if (empty($permissionCodes)) {
//             return false;
//         }

//         foreach ($permissionCodes as $permissionCode) {
//             if (!in_array($permissionCode, $userPermissions)) {
//                 return false;
//             }
//         }

//         return true;
//     }
// }