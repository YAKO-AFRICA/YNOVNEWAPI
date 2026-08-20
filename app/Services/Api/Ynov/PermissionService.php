<?php
// app/Services/Api/Ynov/PermissionService.php

namespace App\Services\Api\Ynov;

// use App\Models\Api\Ynov\parameter\User;
// use App\Models\Api\Ynov\parameter\Permission;
// use App\Models\Api\Ynov\parameter\PermissionGroup;
// use App\Models\Api\Ynov\parameter\Role;
// use Illuminate\Support\Facades\Cache;
// use Illuminate\Support\Str;

// class PermissionService
// {
//     /**
//      * Vérifier si un utilisateur a une permission
//      */
//     public function userHasPermission(User $user, string $permissionCode): bool
//     {
//         if ($user->isSuperAdmin()) {
//             return true;
//         }

//         $permissions = $this->getUserPermissions($user);
//         return in_array($permissionCode, $permissions);
//     }

//     /**
//      * Récupérer les permissions d'un utilisateur
//      */
//     public function getUserPermissions(User $user): array
//     {
//         return Cache::remember("user_permissions_{$user->uuid_user}", 3600, function () use ($user) {
//             if (!$user->role) {
//                 return [];
//             }

//             return $user->role->permissions()
//                 ->where('status', 'actif')
//                 ->pluck('code')
//                 ->toArray();
//         });
//     }

//     public function invalidateUserCache(User $user): void
//     {
//         Cache::forget("user_permissions_{$user->uuid_user}");
//     }

//     /**
//      * Invalider le cache des permissions d'un rôle (tous les utilisateurs)
//      */
//     public function invalidateRoleCache(Role $role): void
//     {
//         foreach ($role->users as $user) {
//             $this->invalidateUserCache($user);
//         }
//     }

//     /**
//      * Récupérer toutes les permissions d'un groupe
//      */
//     public function getPermissionsByGroup(string $groupCode): array
//     {
//         $group = PermissionGroup::where('code', $groupCode)->first();
//         if (!$group) {
//             return [];
//         }

//         return $group->permissions()
//             ->where('status', 'actif')
//             ->pluck('code')
//             ->toArray();
//     }

//     public function create(array $data, string $creatorUuid): Permission
//     {
//         $group = PermissionGroup::where('uuid_permission_group', $data['permission_group_uuid'])->firstOrFail();

//         $code = $group->code . '.' . Str::slug($data['action'], '_');

//         return Permission::create([
//             'uuid_permission' => (string) Str::uuid(),
//             'permission_group_uuid' => $group->uuid_permission_group,
//             'code' => $code,
//             'action' => $data['action'],
//             'libelle' => $data['action'] . ' - ' . $group->libelle,
//             'description' => $data['description'] ?? null,
//             'category' => $data['category'] ?? null,
//             'created_by' => $creatorUuid,
//         ]);
//     }

//     public function update(Permission $permission, array $data, string $updaterUuid): Permission
//     {
//         $permission->update([
//             'action' => $data['action'] ?? $permission->action,
//             'description' => $data['description'] ?? $permission->description,
//             'category' => $data['category'] ?? $permission->category,
//             'updated_by' => $updaterUuid,
//         ]);
//         return $permission->fresh();
//     }


//     /**
//      * Synchroniser les permissions d'un rôle
//      */
//     public function syncRolePermissions(string $roleUuid, array $permissionUuids): void
//     {
//         $role = Role::where('uuid_role', $roleUuid)->firstOrFail();
//         $role->permissions()->sync($permissionUuids);
        
//         // Invalider le cache
//         $role->invalidateRoleCache($role);
//         // $role->invalidateCache();
//     }

//     /**
//      * Obtenir toutes les permissions avec leurs groupes
//      */
//     public function getAllPermissionsWithGroups(): array
//     {
//         $groups = PermissionGroup::with(['permissions' => function ($query) {
//             $query->where('status', 'actif')->orderBy('action');
//         }])->where('status', 'actif')->orderBy('ordre_affichage')->get();

//         return $groups->map(function ($group) {
//             return [
//                 'group' => [
//                     'uuid' => $group->uuid_permission_group,
//                     'code' => $group->code,
//                     'libelle' => $group->libelle,
//                     'icone' => $group->icone,
//                     'color' => $group->color,
//                 ],
//                 'permissions' => $group->permissions->map(function ($permission) {
//                     return [
//                         'uuid' => $permission->uuid_permission,
//                         'code' => $permission->code,
//                         'action' => $permission->action,
//                         'libelle' => $permission->libelle,
//                         'description' => $permission->description,
//                     ];
//                 }),
//             ];
//         })->toArray();
//     }
// }

use App\Models\Api\Ynov\parameter\ActivityLog;
use App\Models\Api\Ynov\parameter\Permission;
use App\Models\Api\Ynov\parameter\PermissionGroup;
use App\Models\Api\Ynov\parameter\Role;
use App\Models\Api\Ynov\parameter\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class PermissionService
{
    /**
     * ================================================================
     * CORRECTION #12 : Source unique de vérité pour les permissions
     * ================================================================
     */

    /**
     * Vérifier si un utilisateur a une permission
     */
    public function userHasPermission(User $user, string $permissionCode): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        $permissions = $this->getUserPermissions($user);
        return in_array($permissionCode, $permissions);
    }

    /**
     * Vérifier si un utilisateur a toutes les permissions
     */
    public function userHasAllPermissions(User $user, array $permissionCodes): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        $permissions = $this->getUserPermissions($user);
        foreach ($permissionCodes as $permissionCode) {
            if (!in_array($permissionCode, $permissions)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Vérifier si un utilisateur a au moins une des permissions
     */
    public function userHasAnyPermission(User $user, array $permissionCodes): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        $permissions = $this->getUserPermissions($user);
        foreach ($permissionCodes as $permissionCode) {
            if (in_array($permissionCode, $permissions)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Récupérer les permissions d'un utilisateur
     * ================================================================
     * CORRECTION #11 : Filtrage des permissions expirées
     * ================================================================
     */
    public function getUserPermissions(User $user): array
    {
        return Cache::remember("user_permissions_{$user->uuid_user}", 3600, function () use ($user) {
            if (!$user->role) {
                return [];
            }

            // ================================================================
            // CORRECTION #11 : Filtrage des permissions expirées
            // La relation permissions() dans le modèle Role filtre déjà expires_at
            // ================================================================
            return $user->role->permissions()
                ->where('status', 'actif')
                ->pluck('code')
                ->toArray();
        });
    }

    /**
     * ================================================================
     * CORRECTION : Invalider le cache des permissions d'un utilisateur
     * ================================================================
     */
    public function invalidateUserCache(User $user): void
    {
        Cache::forget("user_permissions_{$user->uuid_user}");
    }

    /**
     * ================================================================
     * CORRECTION : Invalider le cache des permissions d'un rôle (tous les utilisateurs)
     * ================================================================
     */
    public function invalidateRoleCache(Role $role): void
    {
        // Charger les utilisateurs du rôle avec une requête efficace
        $users = $role->users()->get();
        
        foreach ($users as $user) {
            $this->invalidateUserCache($user);
        }
    }

    /**
     * Invalider le cache pour tous les utilisateurs (utile après une mise à jour globale)
     * ATTENTION : Cette méthode peut être coûteuse en production
     */
    public function invalidateAllCaches(): void
    {
        // Récupérer tous les utilisateurs et invalider leur cache
        // Utiliser chunk pour éviter la surcharge mémoire
        User::chunk(100, function ($users) {
            foreach ($users as $user) {
                $this->invalidateUserCache($user);
            }
        });
    }

    /**
     * Récupérer toutes les permissions d'un groupe
     */
    public function getPermissionsByGroup(string $groupCode): array
    {
        $group = PermissionGroup::where('code', $groupCode)->first();
        if (!$group) {
            return [];
        }

        return $group->permissions()
            ->where('status', 'actif')
            ->pluck('code')
            ->toArray();
    }

    /**
     * Récupérer toutes les permissions avec leurs groupes
     */
    public function getAllPermissionsWithGroups(): array
    {
        $groups = PermissionGroup::with(['permissions' => function ($query) {
            $query->where('status', 'actif')
                  ->where(function ($q) {
                      $q->whereNull('role_permissions.expires_at')
                        ->orWhere('role_permissions.expires_at', '>', now());
                  })
                  ->orderBy('action');
        }])->where('status', 'actif')->orderBy('ordre_affichage')->get();

        return $groups->map(function ($group) {
            return [
                'group' => [
                    'uuid' => $group->uuid_permission_group,
                    'code' => $group->code,
                    'libelle' => $group->libelle,
                    'icone' => $group->icone,
                    'color' => $group->color,
                ],
                'permissions' => $group->permissions->map(function ($permission) {
                    return [
                        'uuid' => $permission->uuid_permission,
                        'code' => $permission->code,
                        'action' => $permission->action,
                        'libelle' => $permission->libelle,
                        'description' => $permission->description,
                    ];
                }),
            ];
        })->toArray();
    }

    /**
     * Créer une nouvelle permission
     */
    public function create(array $data, string $creatorUuid): Permission
    {
        $group = PermissionGroup::where('uuid_permission_group', $data['permission_group_uuid'])->firstOrFail();

        $code = $group->code . '.' . Str::slug($data['action'], '_');

        return Permission::create([
            'uuid_permission' => (string) Str::uuid(),
            'permission_group_uuid' => $group->uuid_permission_group,
            'code' => $code,
            'action' => $data['action'],
            'libelle' => $data['libelle'] ?? $data['action'] . ' - ' . $group->libelle,
            'description' => $data['description'] ?? null,
            'category' => $data['category'] ?? 'crud',
            'created_by' => $creatorUuid,
        ]);
    }

    /**
     * Mettre à jour une permission
     */
    public function update(Permission $permission, array $data, string $updaterUuid): Permission
    {
        $permission->update([
            'action' => $data['action'] ?? $permission->action,
            'description' => $data['description'] ?? $permission->description,
            'category' => $data['category'] ?? $permission->category,
            'updated_by' => $updaterUuid,
        ]);

        // Mettre à jour le code si l'action change
        if (isset($data['action']) && $data['action'] !== $permission->getOriginal('action')) {
            $group = $permission->group;
            $permission->update([
                'code' => $group->code . '.' . Str::slug($data['action'], '_'),
            ]);
        }

        // Invalider les caches des utilisateurs ayant ce rôle
        $roles = $permission->roles;
        foreach ($roles as $role) {
            $this->invalidateRoleCache($role);
        }

        return $permission->fresh();
    }

    /**
     * Supprimer une permission
     */
    public function delete(Permission $permission, string $deleterUuid): void
    {
        // Vérifier si la permission est utilisée par des rôles
        if ($permission->roles()->count() > 0) {
            throw new \RuntimeException('Cette permission est attribuée à un ou plusieurs rôles et ne peut donc pas être supprimée.');
        }

        $permission->update([
            'status' => 'inactif',
            'deleted_by' => $deleterUuid,
        ]);

        $permission->delete();
    }

    /**
     * Synchroniser les permissions d'un rôle
     */
    public function syncRolePermissions(Role $role, array $permissionUuids, string $granterUuid): void
    {
        // Vérifier les permissions sensibles
        $permissions = Permission::whereIn('uuid_permission', $permissionUuids)->get();

        foreach ($permissions as $permission) {
            if ($permission->is_guard) {
                // Journaliser l'attribution d'une permission sensible
                ActivityLog::log([
                    'action' => 'assign_guard_permission',
                    'action_type' => 'security',
                    'module' => 'permissions',
                    'description' => "Attribution d'une permission sensible : {$permission->code}",
                    'level' => 'warning',
                    'metadata' => [
                        'role_uuid' => $role->uuid_role,
                        'permission_uuid' => $permission->uuid_permission,
                        'granter_uuid' => $granterUuid,
                    ],
                ]);
            }
        }

        // Synchroniser
        $role->permissions()->sync($permissionUuids);

        // Invalider le cache
        $this->invalidateRoleCache($role);
    }
}