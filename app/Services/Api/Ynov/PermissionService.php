<?php
// app/Services/Api/Ynov/PermissionService.php

namespace App\Services\Api\Ynov;

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

    // public function suggestedActions(): array
    // {
    //     return [
    //         // ============================================================
    //         // AUTHENTIFICATION
    //         // ============================================================
    //         [
    //             'module' => 'Authentification',
    //             'icon' => 'bi-key',
    //             'color' => '#9b59b6',
    //             'permissions' => [
    //                 [
    //                     'category' => 'security',
    //                     'action' => 'change_password',
    //                     'libelle' => 'Changer le mot de passe',
    //                     'description' => 'Permet de changer le mot de passe de l\'utilisateur connecté'
    //                 ],
    //                 [
    //                     'category' => 'security',
    //                     'action' => 'sessions',
    //                     'libelle' => 'Voir les sessions',
    //                     'description' => 'Permet de visualiser les sessions actives de l\'utilisateur'
    //                 ],
    //                 [
    //                     'category' => 'security',
    //                     'action' => 'revoke_session',
    //                     'libelle' => 'Révoquer une session',
    //                     'description' => 'Permet de révoquer une session active'
    //                 ],
    //                 [
    //                     'category' => 'security',
    //                     'action' => 'revoke_all_sessions',
    //                     'libelle' => 'Révoquer toutes les sessions',
    //                     'description' => 'Permet de révoquer toutes les sessions actives'
    //                 ],
    //                 [
    //                     'category' => 'security',
    //                     'action' => 'devices',
    //                     'libelle' => 'Voir les appareils',
    //                     'description' => 'Permet de visualiser les appareils connectés'
    //                 ],
    //                 [
    //                     'category' => 'security',
    //                     'action' => 'trust_device',
    //                     'libelle' => 'Approuver un appareil',
    //                     'description' => 'Permet d\'approuver un appareil comme appareil de confiance'
    //                 ],
    //                 [
    //                     'category' => 'security',
    //                     'action' => 'revoke_device',
    //                     'libelle' => 'Révoquer un appareil',
    //                     'description' => 'Permet de révoquer un appareil connecté'
    //                 ],
    //                 [
    //                     'category' => 'security',
    //                     'action' => 'login_attempts',
    //                     'libelle' => 'Voir les tentatives de connexion',
    //                     'description' => 'Permet de visualiser l\'historique des tentatives de connexion'
    //                 ],
    //                 [
    //                     'category' => 'security',
    //                     'action' => '2fa',
    //                     'libelle' => 'Gérer la 2FA',
    //                     'description' => 'Permet de gérer l\'authentification à deux facteurs'
    //                 ],
    //             ]
    //         ],

    //         // ============================================================
    //         // PROFIL
    //         // ============================================================
    //         [
    //             'module' => 'Profil',
    //             'icon' => 'bi-person',
    //             'color' => '#0d6efd',
    //             'permissions' => [
    //                 [
    //                     'category' => 'crud',
    //                     'action' => 'show',
    //                     'libelle' => 'Afficher le profil',
    //                     'description' => 'Permet d\'afficher les informations du profil utilisateur'
    //                 ],
    //                 [
    //                     'category' => 'crud',
    //                     'action' => 'update',
    //                     'libelle' => 'Modifier le profil',
    //                     'description' => 'Permet de modifier les informations du profil utilisateur'
    //                 ],
    //                 [
    //                     'category' => 'crud',
    //                     'action' => 'update_photo',
    //                     'libelle' => 'Changer la photo de profil',
    //                     'description' => 'Permet de changer la photo de profil'
    //                 ],
    //                 [
    //                     'category' => 'crud',
    //                     'action' => 'delete_photo',
    //                     'libelle' => 'Supprimer la photo de profil',
    //                     'description' => 'Permet de supprimer la photo de profil'
    //                 ],
    //             ]
    //         ],

    //         // ============================================================
    //         // UTILISATEURS
    //         // ============================================================
    //         [
    //             'module' => 'Utilisateurs',
    //             'icon' => 'bi-people',
    //             'color' => '#3490dc',
    //             'permissions' => [
    //                 [
    //                     'category' => 'crud',
    //                     'action' => 'afficher',
    //                     'libelle' => 'Afficher les utilisateurs',
    //                     'description' => 'Permet de visualiser la liste des utilisateurs'
    //                 ],
    //                 [
    //                     'category' => 'crud',
    //                     'action' => 'creer',
    //                     'libelle' => 'Créer un utilisateur',
    //                     'description' => 'Permet de créer un nouvel utilisateur'
    //                 ],
    //                 [
    //                     'category' => 'crud',
    //                     'action' => 'modifier',
    //                     'libelle' => 'Modifier un utilisateur',
    //                     'description' => 'Permet de modifier les informations d\'un utilisateur'
    //                 ],
    //                 [
    //                     'category' => 'crud',
    //                     'action' => 'supprimer',
    //                     'libelle' => 'Supprimer un utilisateur',
    //                     'description' => 'Permet de supprimer un utilisateur'
    //                 ],
    //                 [
    //                     'category' => 'admin',
    //                     'action' => 'bloquer',
    //                     'libelle' => 'Bloquer un utilisateur',
    //                     'description' => 'Permet de bloquer un utilisateur'
    //                 ],
    //                 [
    //                     'category' => 'admin',
    //                     'action' => 'debloquer',
    //                     'libelle' => 'Débloquer un utilisateur',
    //                     'description' => 'Permet de débloquer un utilisateur'
    //                 ],
    //                 [
    //                     'category' => 'admin',
    //                     'action' => 'geler',
    //                     'libelle' => 'Geler un utilisateur',
    //                     'description' => 'Permet de geler temporairement un compte utilisateur'
    //                 ],
    //                 [
    //                     'category' => 'admin',
    //                     'action' => 'degeler',
    //                     'libelle' => 'Dégeler un utilisateur',
    //                     'description' => 'Permet de dégeler un compte utilisateur'
    //                 ],
    //                 [
    //                     'category' => 'search',
    //                     'action' => 'search',
    //                     'libelle' => 'Rechercher des utilisateurs',
    //                     'description' => 'Permet de rechercher des utilisateurs'
    //                 ],
    //                 [
    //                     'category' => 'report',
    //                     'action' => 'export',
    //                     'libelle' => 'Exporter les utilisateurs',
    //                     'description' => 'Permet d\'exporter la liste des utilisateurs'
    //                 ],
    //                 [
    //                     'category' => 'crud',
    //                     'action' => 'bulk_create',
    //                     'libelle' => 'Création en masse',
    //                     'description' => 'Permet de créer plusieurs utilisateurs en une seule opération'
    //                 ],
    //                 [
    //                     'category' => 'crud',
    //                     'action' => 'bulk_update',
    //                     'libelle' => 'Modification en masse',
    //                     'description' => 'Permet de modifier plusieurs utilisateurs en une seule opération'
    //                 ],
    //             ]
    //         ],

    //         // ============================================================
    //         // RÔLES
    //         // ============================================================
    //         [
    //             'module' => 'Rôles',
    //             'icon' => 'bi-shield',
    //             'color' => '#e67e22',
    //             'permissions' => [
    //                 [
    //                     'category' => 'crud',
    //                     'action' => 'afficher',
    //                     'libelle' => 'Afficher les rôles',
    //                     'description' => 'Permet de visualiser la liste des rôles'
    //                 ],
    //                 [
    //                     'category' => 'crud',
    //                     'action' => 'creer',
    //                     'libelle' => 'Créer un rôle',
    //                     'description' => 'Permet de créer un nouveau rôle'
    //                 ],
    //                 [
    //                     'category' => 'crud',
    //                     'action' => 'modifier',
    //                     'libelle' => 'Modifier un rôle',
    //                     'description' => 'Permet de modifier les informations d\'un rôle'
    //                 ],
    //                 [
    //                     'category' => 'crud',
    //                     'action' => 'supprimer',
    //                     'libelle' => 'Supprimer un rôle',
    //                     'description' => 'Permet de supprimer un rôle'
    //                 ],
    //                 [
    //                     'category' => 'admin',
    //                     'action' => 'gerer_permissions',
    //                     'libelle' => 'Gérer les permissions des rôles',
    //                     'description' => 'Permet d\'attribuer ou de retirer des permissions à un rôle'
    //                 ],
    //                 [
    //                     'category' => 'crud',
    //                     'action' => 'users',
    //                     'libelle' => 'Voir les utilisateurs d\'un rôle',
    //                     'description' => 'Permet de visualiser les utilisateurs associés à un rôle'
    //                 ],
    //             ]
    //         ],

    //         // ============================================================
    //         // PERMISSIONS
    //         // ============================================================
    //         [
    //             'module' => 'Permissions',
    //             'icon' => 'bi-key',
    //             'color' => '#2ecc71',
    //             'permissions' => [
    //                 [
    //                     'category' => 'crud',
    //                     'action' => 'afficher',
    //                     'libelle' => 'Afficher les permissions',
    //                     'description' => 'Permet de visualiser la liste des permissions'
    //                 ],
    //                 [
    //                     'category' => 'crud',
    //                     'action' => 'creer',
    //                     'libelle' => 'Créer une permission',
    //                     'description' => 'Permet de créer une nouvelle permission'
    //                 ],
    //                 [
    //                     'category' => 'crud',
    //                     'action' => 'modifier',
    //                     'libelle' => 'Modifier une permission',
    //                     'description' => 'Permet de modifier les informations d\'une permission'
    //                 ],
    //                 [
    //                     'category' => 'crud',
    //                     'action' => 'supprimer',
    //                     'libelle' => 'Supprimer une permission',
    //                     'description' => 'Permet de supprimer une permission'
    //                 ],
    //                 [
    //                     'category' => 'report',
    //                     'action' => 'suggested_actions',
    //                     'libelle' => 'Voir les actions suggérées',
    //                     'description' => 'Permet de visualiser les actions standards suggérées'
    //                 ],
    //             ]
    //         ],

    //         // ============================================================
    //         // GROUPES DE PERMISSIONS
    //         // ============================================================
    //         [
    //             'module' => 'Groupes de permissions',
    //             'icon' => 'bi-folder',
    //             'color' => '#f39c12',
    //             'permissions' => [
    //                 [
    //                     'category' => 'crud',
    //                     'action' => 'afficher',
    //                     'libelle' => 'Afficher les groupes de permissions',
    //                     'description' => 'Permet de visualiser les groupes de permissions'
    //                 ],
    //                 [
    //                     'category' => 'crud',
    //                     'action' => 'creer',
    //                     'libelle' => 'Créer un groupe de permissions',
    //                     'description' => 'Permet de créer un groupe de permissions'
    //                 ],
    //                 [
    //                     'category' => 'crud',
    //                     'action' => 'modifier',
    //                     'libelle' => 'Modifier un groupe de permissions',
    //                     'description' => 'Permet de modifier un groupe de permissions'
    //                 ],
    //                 [
    //                     'category' => 'crud',
    //                     'action' => 'supprimer',
    //                     'libelle' => 'Supprimer un groupe de permissions',
    //                     'description' => 'Permet de supprimer un groupe de permissions'
    //                 ],
    //             ]
    //         ],

    //         // ============================================================
    //         // PARTENAIRES
    //         // ============================================================
    //         [
    //             'module' => 'Partenaires',
    //             'icon' => 'bi-handshake',
    //             'color' => '#2c3e50',
    //             'permissions' => [
    //                 [
    //                     'category' => 'crud',
    //                     'action' => 'afficher',
    //                     'libelle' => 'Afficher les partenaires',
    //                     'description' => 'Permet de visualiser la liste des partenaires'
    //                 ],
    //                 [
    //                     'category' => 'crud',
    //                     'action' => 'creer',
    //                     'libelle' => 'Créer un partenaire',
    //                     'description' => 'Permet de créer un nouveau partenaire'
    //                 ],
    //                 [
    //                     'category' => 'crud',
    //                     'action' => 'modifier',
    //                     'libelle' => 'Modifier un partenaire',
    //                     'description' => 'Permet de modifier les informations d\'un partenaire'
    //                 ],
    //                 [
    //                     'category' => 'crud',
    //                     'action' => 'supprimer',
    //                     'libelle' => 'Supprimer un partenaire',
    //                     'description' => 'Permet de supprimer un partenaire'
    //                 ],
    //                 [
    //                     'category' => 'crud',
    //                     'action' => 'reseaux',
    //                     'libelle' => 'Voir les réseaux d\'un partenaire',
    //                     'description' => 'Permet de visualiser les réseaux associés à un partenaire'
    //                 ],
    //             ]
    //         ],

    //         // ============================================================
    //         // RÉSEAUX
    //         // ============================================================
    //         [
    //             'module' => 'Réseaux',
    //             'icon' => 'bi-diagram-3',
    //             'color' => '#2980b9',
    //             'permissions' => [
    //                 [
    //                     'category' => 'crud',
    //                     'action' => 'afficher',
    //                     'libelle' => 'Afficher les réseaux',
    //                     'description' => 'Permet de visualiser la liste des réseaux'
    //                 ],
    //                 [
    //                     'category' => 'crud',
    //                     'action' => 'creer',
    //                     'libelle' => 'Créer un réseau',
    //                     'description' => 'Permet de créer un nouveau réseau'
    //                 ],
    //                 [
    //                     'category' => 'crud',
    //                     'action' => 'modifier',
    //                     'libelle' => 'Modifier un réseau',
    //                     'description' => 'Permet de modifier les informations d\'un réseau'
    //                 ],
    //                 [
    //                     'category' => 'crud',
    //                     'action' => 'supprimer',
    //                     'libelle' => 'Supprimer un réseau',
    //                     'description' => 'Permet de supprimer un réseau'
    //                 ],
    //                 [
    //                     'category' => 'crud',
    //                     'action' => 'agences',
    //                     'libelle' => 'Voir les agences d\'un réseau',
    //                     'description' => 'Permet de visualiser les agences associées à un réseau'
    //                 ],
    //             ]
    //         ],

    //         // ============================================================
    //         // AGENCES
    //         // ============================================================
    //         [
    //             'module' => 'Agences',
    //             'icon' => 'bi-building',
    //             'color' => '#27ae60',
    //             'permissions' => [
    //                 [
    //                     'category' => 'crud',
    //                     'action' => 'afficher',
    //                     'libelle' => 'Afficher les agences',
    //                     'description' => 'Permet de visualiser la liste des agences'
    //                 ],
    //                 [
    //                     'category' => 'crud',
    //                     'action' => 'creer',
    //                     'libelle' => 'Créer une agence',
    //                     'description' => 'Permet de créer une nouvelle agence'
    //                 ],
    //                 [
    //                     'category' => 'crud',
    //                     'action' => 'modifier',
    //                     'libelle' => 'Modifier une agence',
    //                     'description' => 'Permet de modifier les informations d\'une agence'
    //                 ],
    //                 [
    //                     'category' => 'crud',
    //                     'action' => 'supprimer',
    //                     'libelle' => 'Supprimer une agence',
    //                     'description' => 'Permet de supprimer une agence'
    //                 ],
    //                 [
    //                     'category' => 'admin',
    //                     'action' => 'assigner_utilisateurs',
    //                     'libelle' => 'Assigner des utilisateurs',
    //                     'description' => 'Permet d\'assigner des utilisateurs à une agence'
    //                 ],
    //                 [
    //                     'category' => 'admin',
    //                     'action' => 'retirer_utilisateurs',
    //                     'libelle' => 'Retirer des utilisateurs',
    //                     'description' => 'Permet de retirer des utilisateurs d\'une agence'
    //                 ],
    //                 [
    //                     'category' => 'crud',
    //                     'action' => 'horaires',
    //                     'libelle' => 'Voir les horaires d\'une agence',
    //                     'description' => 'Permet de visualiser les horaires d\'ouverture d\'une agence'
    //                 ],
    //                 [
    //                     'category' => 'search',
    //                     'action' => 'nearby',
    //                     'libelle' => 'Voir les agences à proximité',
    //                     'description' => 'Permet de rechercher les agences proches d\'une position géographique'
    //                 ],
    //             ]
    //         ],

    //         // ============================================================
    //         // QUESTIONS DE SÉCURITÉ
    //         // ============================================================
    //         [
    //             'module' => 'Questions de sécurité',
    //             'icon' => 'bi-question-circle',
    //             'color' => '#8e44ad',
    //             'permissions' => [
    //                 [
    //                     'category' => 'admin',
    //                     'action' => 'gerer',
    //                     'libelle' => 'Gérer les questions de sécurité',
    //                     'description' => 'Permet de gérer les questions de sécurité (création, modification, suppression)'
    //                 ],
    //                 [
    //                     'category' => 'crud',
    //                     'action' => 'afficher',
    //                     'libelle' => 'Afficher les questions de sécurité',
    //                     'description' => 'Permet d\'afficher les questions de sécurité'
    //                 ],
    //                 [
    //                     'category' => 'public',
    //                     'action' => 'suggested',
    //                     'libelle' => 'Voir les questions suggérées',
    //                     'description' => 'Permet de visualiser les questions de sécurité suggérées'
    //                 ],
    //                 [
    //                     'category' => 'public',
    //                     'action' => 'available',
    //                     'libelle' => 'Voir les questions disponibles',
    //                     'description' => 'Permet de visualiser toutes les questions de sécurité disponibles'
    //                 ],
    //                 [
    //                     'category' => 'crud',
    //                     'action' => 'my_questions',
    //                     'libelle' => 'Voir mes questions configurées',
    //                     'description' => 'Permet de visualiser les questions de sécurité configurées par l\'utilisateur'
    //                 ],
    //                 [
    //                     'category' => 'crud',
    //                     'action' => 'set_questions',
    //                     'libelle' => 'Configurer mes questions',
    //                     'description' => 'Permet de configurer les questions de sécurité de l\'utilisateur'
    //                 ],
    //                 [
    //                     'category' => 'security',
    //                     'action' => 'verify_answer',
    //                     'libelle' => 'Vérifier une réponse',
    //                     'description' => 'Permet de vérifier une réponse à une question de sécurité'
    //                 ],
    //             ]
    //         ],

    //         // ============================================================
    //         // RESTRICTIONS IP
    //         // ============================================================
    //         [
    //             'module' => 'Restrictions IP',
    //             'icon' => 'bi-globe2',
    //             'color' => '#dc3545',
    //             'permissions' => [
    //                 [
    //                     'category' => 'crud',
    //                     'action' => 'afficher',
    //                     'libelle' => 'Afficher les restrictions IP',
    //                     'description' => 'Permet de visualiser les restrictions IP configurées'
    //                 ],
    //                 [
    //                     'category' => 'crud',
    //                     'action' => 'creer',
    //                     'libelle' => 'Créer une restriction IP',
    //                     'description' => 'Permet de créer une nouvelle restriction IP'
    //                 ],
    //                 [
    //                     'category' => 'crud',
    //                     'action' => 'supprimer',
    //                     'libelle' => 'Supprimer une restriction IP',
    //                     'description' => 'Permet de supprimer une restriction IP'
    //                 ],
    //             ]
    //         ],

    //         // ============================================================
    //         // LOGS & AUDIT
    //         // ============================================================
    //         [
    //             'module' => 'Logs & Audit',
    //             'icon' => 'bi-clipboard-data',
    //             'color' => '#6c757d',
    //             'permissions' => [
    //                 [
    //                     'category' => 'report',
    //                     'action' => 'consulter_les_logs',
    //                     'libelle' => 'Consulter les logs d\'activité',
    //                     'description' => 'Permet de consulter l\'ensemble des logs d\'activité du système'
    //                 ],
    //                 [
    //                     'category' => 'report',
    //                     'action' => 'my_activity',
    //                     'libelle' => 'Voir mes logs d\'activité',
    //                     'description' => 'Permet de visualiser ses propres logs d\'activité'
    //                 ],
    //                 [
    //                     'category' => 'report',
    //                     'action' => 'user_logs',
    //                     'libelle' => 'Voir les logs d\'un utilisateur',
    //                     'description' => 'Permet de visualiser les logs d\'activité d\'un utilisateur spécifique'
    //                 ],
    //                 [
    //                     'category' => 'report',
    //                     'action' => 'freeze_logs',
    //                     'libelle' => 'Voir les logs de gel/dégel',
    //                     'description' => 'Permet de visualiser l\'historique des gels et dégels de comptes'
    //                 ],
    //                 [
    //                     'category' => 'report',
    //                     'action' => 'stats',
    //                     'libelle' => 'Voir les statistiques d\'activité',
    //                     'description' => 'Permet de visualiser les statistiques d\'activité du système'
    //                 ],
    //                 [
    //                     'category' => 'report',
    //                     'action' => 'export_logs',
    //                     'libelle' => 'Exporter les logs',
    //                     'description' => 'Permet d\'exporter les logs d\'activité'
    //                 ],
    //             ]
    //         ],

    //         // ============================================================
    //         // FAQ
    //         // ============================================================
    //         [
    //             'module' => 'FAQ',
    //             'icon' => 'bi-question-square',
    //             'color' => '#0d6efd',
    //             'permissions' => [
    //                 [
    //                     'category' => 'crud',
    //                     'action' => 'afficher',
    //                     'libelle' => 'Afficher les FAQs',
    //                     'description' => 'Permet de visualiser les FAQs'
    //                 ],
    //                 [
    //                     'category' => 'crud',
    //                     'action' => 'creer',
    //                     'libelle' => 'Créer une FAQ',
    //                     'description' => 'Permet de créer une nouvelle FAQ'
    //                 ],
    //                 [
    //                     'category' => 'crud',
    //                     'action' => 'modifier',
    //                     'libelle' => 'Modifier une FAQ',
    //                     'description' => 'Permet de modifier une FAQ existante'
    //                 ],
    //                 [
    //                     'category' => 'crud',
    //                     'action' => 'supprimer',
    //                     'libelle' => 'Supprimer une FAQ',
    //                     'description' => 'Permet de supprimer une FAQ'
    //                 ],
    //                 [
    //                     'category' => 'admin',
    //                     'action' => 'activer',
    //                     'libelle' => 'Activer une FAQ',
    //                     'description' => 'Permet d\'activer une FAQ'
    //                 ],
    //                 [
    //                     'category' => 'admin',
    //                     'action' => 'desactiver',
    //                     'libelle' => 'Désactiver une FAQ',
    //                     'description' => 'Permet de désactiver une FAQ'
    //                 ],
    //                 [
    //                     'category' => 'search',
    //                     'action' => 'search',
    //                     'libelle' => 'Rechercher dans les FAQs',
    //                     'description' => 'Permet de rechercher dans les FAQs'
    //                 ],
    //                 [
    //                     'category' => 'crud',
    //                     'action' => 'categories_afficher',
    //                     'libelle' => 'Afficher les catégories de FAQ',
    //                     'description' => 'Permet de visualiser les catégories de FAQ'
    //                 ],
    //                 [
    //                     'category' => 'crud',
    //                     'action' => 'categories_creer',
    //                     'libelle' => 'Créer une catégorie de FAQ',
    //                     'description' => 'Permet de créer une catégorie de FAQ'
    //                 ],
    //                 [
    //                     'category' => 'crud',
    //                     'action' => 'categories_modifier',
    //                     'libelle' => 'Modifier une catégorie de FAQ',
    //                     'description' => 'Permet de modifier une catégorie de FAQ'
    //                 ],
    //                 [
    //                     'category' => 'crud',
    //                     'action' => 'categories_supprimer',
    //                     'libelle' => 'Supprimer une catégorie de FAQ',
    //                     'description' => 'Permet de supprimer une catégorie de FAQ'
    //                 ],
    //                 [
    //                     'category' => 'admin',
    //                     'action' => 'categories_activer',
    //                     'libelle' => 'Activer une catégorie de FAQ',
    //                     'description' => 'Permet d\'activer une catégorie de FAQ'
    //                 ],
    //                 [
    //                     'category' => 'admin',
    //                     'action' => 'categories_desactiver',
    //                     'libelle' => 'Désactiver une catégorie de FAQ',
    //                     'description' => 'Permet de désactiver une catégorie de FAQ'
    //                 ],
    //                 [
    //                     'category' => 'admin',
    //                     'action' => 'reorder',
    //                     'libelle' => 'Réordonner les catégories de FAQ',
    //                     'description' => 'Permet de réordonner les catégories de FAQ'
    //                 ],
    //                 [
    //                     'category' => 'crud',
    //                     'action' => 'duplicate',
    //                     'libelle' => 'Dupliquer une catégorie de FAQ',
    //                     'description' => 'Permet de dupliquer une catégorie de FAQ'
    //                 ],
    //                 [
    //                     'category' => 'report',
    //                     'action' => 'stats',
    //                     'libelle' => 'Voir les statistiques des FAQs',
    //                     'description' => 'Permet de visualiser les statistiques des FAQs'
    //                 ],
    //             ]
    //         ],

    //         // ============================================================
    //         // GROUPES DE NOTIFICATION
    //         // ============================================================
    //         [
    //             'module' => 'Groupes de notification',
    //             'icon' => 'bi-bell-fill',
    //             'color' => '#fd7e14',
    //             'permissions' => [
    //                 [
    //                     'category' => 'crud',
    //                     'action' => 'afficher',
    //                     'libelle' => 'Afficher les groupes de notification',
    //                     'description' => 'Permet de visualiser les groupes de notification'
    //                 ],
    //                 [
    //                     'category' => 'crud',
    //                     'action' => 'creer',
    //                     'libelle' => 'Créer un groupe de notification',
    //                     'description' => 'Permet de créer un groupe de notification'
    //                 ],
    //                 [
    //                     'category' => 'crud',
    //                     'action' => 'modifier',
    //                     'libelle' => 'Modifier un groupe de notification',
    //                     'description' => 'Permet de modifier un groupe de notification'
    //                 ],
    //                 [
    //                     'category' => 'crud',
    //                     'action' => 'supprimer',
    //                     'libelle' => 'Supprimer un groupe de notification',
    //                     'description' => 'Permet de supprimer un groupe de notification'
    //                 ],
    //                 [
    //                     'category' => 'crud',
    //                     'action' => 'duplicate',
    //                     'libelle' => 'Dupliquer un groupe de notification',
    //                     'description' => 'Permet de dupliquer un groupe de notification'
    //                 ],
    //                 [
    //                     'category' => 'admin',
    //                     'action' => 'assigner',
    //                     'libelle' => 'Assigner des utilisateurs à un groupe',
    //                     'description' => 'Permet d\'assigner des utilisateurs à un groupe de notification'
    //                 ],
    //                 [
    //                     'category' => 'admin',
    //                     'action' => 'retirer',
    //                     'libelle' => 'Retirer un utilisateur d\'un groupe',
    //                     'description' => 'Permet de retirer un utilisateur d\'un groupe de notification'
    //                 ],
    //                 [
    //                     'category' => 'admin',
    //                     'action' => 'set_primary',
    //                     'libelle' => 'Définir le groupe principal',
    //                     'description' => 'Permet de définir le groupe principal d\'un utilisateur'
    //                 ],
    //                 [
    //                     'category' => 'crud',
    //                     'action' => 'my_groups',
    //                     'libelle' => 'Voir mes groupes',
    //                     'description' => 'Permet de visualiser les groupes de notification de l\'utilisateur connecté'
    //                 ],
    //                 [
    //                     'category' => 'report',
    //                     'action' => 'channels',
    //                     'libelle' => 'Voir les canaux disponibles',
    //                     'description' => 'Permet de visualiser les canaux de notification disponibles'
    //                 ],
    //                 [
    //                     'category' => 'report',
    //                     'action' => 'stats',
    //                     'libelle' => 'Voir les statistiques des groupes',
    //                     'description' => 'Permet de visualiser les statistiques des groupes de notification'
    //                 ],
    //             ]
    //         ],

    //         // ============================================================
    //         // NOTIFICATIONS
    //         // ============================================================
    //         [
    //             'module' => 'Notifications',
    //             'icon' => 'bi-bell',
    //             'color' => '#fd7e14',
    //             'permissions' => [
    //                 [
    //                     'category' => 'crud',
    //                     'action' => 'afficher',
    //                     'libelle' => 'Afficher les notifications',
    //                     'description' => 'Permet de visualiser les notifications de l\'utilisateur connecté'
    //                 ],
    //                 [
    //                     'category' => 'crud',
    //                     'action' => 'mark_read',
    //                     'libelle' => 'Marquer une notification comme lue',
    //                     'description' => 'Permet de marquer une notification comme lue'
    //                 ],
    //                 [
    //                     'category' => 'crud',
    //                     'action' => 'mark_all_read',
    //                     'libelle' => 'Marquer toutes les notifications comme lues',
    //                     'description' => 'Permet de marquer toutes les notifications comme lues'
    //                 ],
    //                 [
    //                     'category' => 'crud',
    //                     'action' => 'mark_important',
    //                     'libelle' => 'Marquer une notification comme importante',
    //                     'description' => 'Permet de marquer une notification comme importante'
    //                 ],
    //                 [
    //                     'category' => 'crud',
    //                     'action' => 'unmark_important',
    //                     'libelle' => 'Retirer le statut important d\'une notification',
    //                     'description' => 'Permet de retirer le statut important d\'une notification'
    //                 ],
    //                 [
    //                     'category' => 'crud',
    //                     'action' => 'supprimer',
    //                     'libelle' => 'Supprimer une notification',
    //                     'description' => 'Permet de supprimer une notification'
    //                 ],
    //                 [
    //                     'category' => 'report',
    //                     'action' => 'unread_count',
    //                     'libelle' => 'Voir le nombre de notifications non lues',
    //                     'description' => 'Permet de visualiser le nombre de notifications non lues'
    //                 ],
    //                 [
    //                     'category' => 'crud',
    //                     'action' => 'creer',
    //                     'libelle' => 'Créer une notification (admin)',
    //                     'description' => 'Permet de créer une notification pour un utilisateur (admin)'
    //                 ],
    //                 [
    //                     'category' => 'crud',
    //                     'action' => 'creer_groupe',
    //                     'libelle' => 'Créer une notification pour un groupe (admin)',
    //                     'description' => 'Permet de créer une notification pour tous les utilisateurs d\'un groupe (admin)'
    //                 ],
    //             ]
    //         ],

    //         // ============================================================
    //         // ESPACE CLIENT - CONTRATS
    //         // ============================================================
    //         [
    //             'module' => 'Espace Client - Contrats',
    //             'icon' => 'bi-file-earmark-text',
    //             'color' => '#e67e22',
    //             'permissions' => [
    //                 [
    //                     'category' => 'crud',
    //                     'action' => 'liste',
    //                     'libelle' => 'Afficher la liste des contrats',
    //                     'description' => 'Permet de visualiser la liste des contrats du client'
    //                 ],
    //                 [
    //                     'category' => 'crud',
    //                     'action' => 'details',
    //                     'libelle' => 'Afficher les détails d\'un contrat',
    //                     'description' => 'Permet de visualiser les détails complets d\'un contrat'
    //                 ],
    //                 [
    //                     'category' => 'report',
    //                     'action' => 'etat_cotisation',
    //                     'libelle' => 'Afficher l\'état de cotisation d\'un contrat',
    //                     'description' => 'Permet de visualiser l\'état de cotisation d\'un contrat'
    //                 ],
    //                 [
    //                     'category' => 'report',
    //                     'action' => 'factures',
    //                     'libelle' => 'Afficher les contrats avec factures impayées',
    //                     'description' => 'Permet de visualiser les contrats ayant des factures impayées'
    //                 ],
    //                 [
    //                     'category' => 'crud',
    //                     'action' => 'ajouter',
    //                     'libelle' => 'Ajouter un contrat au compte',
    //                     'description' => 'Permet d\'ajouter un contrat au compte du client'
    //                 ],
    //                 [
    //                     'category' => 'search',
    //                     'action' => 'filtrer',
    //                     'libelle' => 'Filtrer les contrats par période',
    //                     'description' => 'Permet de filtrer les contrats par période (aujourd\'hui, semaine, mois, année)'
    //                 ],
    //                 [
    //                     'category' => 'search',
    //                     'action' => 'rechercher',
    //                     'libelle' => 'Rechercher des contrats',
    //                     'description' => 'Permet de rechercher des contrats par produit ou numéro'
    //                 ],
    //             ]
    //         ],

    //         // ============================================================
    //         // ESPACE CLIENT - DASHBOARD
    //         // ============================================================
    //         [
    //             'module' => 'Espace Client - Dashboard',
    //             'icon' => 'bi-speedometer2',
    //             'color' => '#0d6efd',
    //             'permissions' => [
    //                 [
    //                     'category' => 'report',
    //                     'action' => 'dashboard',
    //                     'libelle' => 'Afficher le tableau de bord client',
    //                     'description' => 'Permet d\'afficher le tableau de bord du client'
    //                 ],
    //                 [
    //                     'category' => 'report',
    //                     'action' => 'statistiques',
    //                     'libelle' => 'Voir les statistiques client',
    //                     'description' => 'Permet de visualiser les statistiques du client'
    //                 ],
    //                 [
    //                     'category' => 'report',
    //                     'action' => 'echeances',
    //                     'libelle' => 'Voir les prochaines échéances',
    //                     'description' => 'Permet de visualiser les prochaines échéances de paiement'
    //                 ],
    //                 [
    //                     'category' => 'report',
    //                     'action' => 'paiements_recents',
    //                     'libelle' => 'Voir les paiements récents',
    //                     'description' => 'Permet de visualiser les paiements récents'
    //                 ],
    //             ]
    //         ],

    //         // ============================================================
    //         // ESPACE CLIENT - PAIEMENTS
    //         // ============================================================
    //         [
    //             'module' => 'Espace Client - Paiements',
    //             'icon' => 'bi-credit-card',
    //             'color' => '#2ecc71',
    //             'permissions' => [
    //                 [
    //                     'category' => 'report',
    //                     'action' => 'historique',
    //                     'libelle' => 'Afficher l\'historique des paiements',
    //                     'description' => 'Permet d\'afficher l\'historique des paiements du client'
    //                 ],
    //                 [
    //                     'category' => 'search',
    //                     'action' => 'filtrer',
    //                     'libelle' => 'Filtrer les paiements par contrat',
    //                     'description' => 'Permet de filtrer les paiements par contrat'
    //                 ],
    //                 [
    //                     'category' => 'report',
    //                     'action' => 'contrat',
    //                     'libelle' => 'Voir les paiements d\'un contrat',
    //                     'description' => 'Permet de visualiser les paiements associés à un contrat'
    //                 ],
    //             ]
    //         ],

    //         // ============================================================
    //         // ADMINISTRATION SYSTÈME
    //         // ============================================================
    //         [
    //             'module' => 'Administration système',
    //             'icon' => 'bi-gear',
    //             'color' => '#dc3545',
    //             'permissions' => [
    //                 [
    //                     'category' => 'admin',
    //                     'action' => 'configurer_permissions',
    //                     'libelle' => 'Configurer les permissions',
    //                     'description' => 'Permet de configurer les permissions du système'
    //                 ],
    //                 [
    //                     'category' => 'admin',
    //                     'action' => 'gerer_roles_systeme',
    //                     'libelle' => 'Gérer les rôles système',
    //                     'description' => 'Permet de gérer les rôles système'
    //                 ],
    //                 [
    //                     'category' => 'report',
    //                     'action' => 'statistiques_globales',
    //                     'libelle' => 'Voir les statistiques globales',
    //                     'description' => 'Permet de visualiser les statistiques globales du système'
    //                 ],
    //                 [
    //                     'category' => 'admin',
    //                     'action' => 'gerer_utilisateurs_systeme',
    //                     'libelle' => 'Gérer les utilisateurs système',
    //                     'description' => 'Permet de gérer les utilisateurs système'
    //                 ],
    //             ]
    //         ],
    //     ];
    // }

    /**
     * Actions suggérées pour la création de permissions
     * Structure utilisée par PermissionSeeder
     */
    public function suggestedActions(): array
    {
        return [
            // ============================================================
            // AUTHENTIFICATION
            // ============================================================
            [
                'module' => [
                    'code' => 'auth',
                    'libelle' => 'Authentification',
                    'description' => 'Gestion de l\'authentification et de la sécurité du compte',
                    'icone' => 'lock',
                    'color' => '#9b59b6',
                    'ordre' => 1,
                ],
                'permissions' => [
                    [
                        'category' => 'security',
                        'action' => 'change_password',
                        'libelle' => 'Changer le mot de passe',
                        'description' => 'Permet de changer le mot de passe de l\'utilisateur connecté'
                    ],
                    [
                        'category' => 'security',
                        'action' => 'sessions',
                        'libelle' => 'Voir les sessions',
                        'description' => 'Permet de visualiser les sessions actives de l\'utilisateur'
                    ],
                    [
                        'category' => 'security',
                        'action' => 'devices',
                        'libelle' => 'Voir les appareils',
                        'description' => 'Permet de visualiser les appareils connectés'
                    ],
                    [
                        'category' => 'security',
                        'action' => 'login_attempts',
                        'libelle' => 'Voir les tentatives de connexion',
                        'description' => 'Permet de visualiser l\'historique des tentatives de connexion'
                    ],
                    [
                        'category' => 'security',
                        'action' => '2fa',
                        'libelle' => 'Gérer la 2FA',
                        'description' => 'Permet de gérer l\'authentification à deux facteurs'
                    ],
                ]
            ],

            // ============================================================
            // PROFIL
            // ============================================================
            [
                'module' => [
                    'code' => 'profile',
                    'libelle' => 'Profil',
                    'description' => 'Gestion du profil utilisateur',
                    'icone' => 'user',
                    'color' => '#0d6efd',
                    'ordre' => 2,
                ],
                'permissions' => [
                    [
                        'category' => 'crud',
                        'action' => 'afficher',
                        'libelle' => 'Afficher le profil',
                        'description' => 'Permet d\'afficher les informations du profil utilisateur'
                    ],
                    [
                        'category' => 'crud',
                        'action' => 'modifier',
                        'libelle' => 'Modifier le profil',
                        'description' => 'Permet de modifier les informations du profil utilisateur'
                    ],
                ]
            ],

            // ============================================================
            // UTILISATEURS
            // ============================================================
            [
                'module' => [
                    'code' => 'users',
                    'libelle' => 'Utilisateurs',
                    'description' => 'Gestion des utilisateurs',
                    'icone' => 'users',
                    'color' => '#3490dc',
                    'ordre' => 3,
                ],
                'permissions' => [
                    [
                        'category' => 'crud',
                        'action' => 'afficher',
                        'libelle' => 'Afficher les utilisateurs',
                        'description' => 'Permet de visualiser la liste des utilisateurs'
                    ],
                    [
                        'category' => 'crud',
                        'action' => 'creer',
                        'libelle' => 'Créer un utilisateur',
                        'description' => 'Permet de créer un nouvel utilisateur'
                    ],
                    [
                        'category' => 'crud',
                        'action' => 'modifier',
                        'libelle' => 'Modifier un utilisateur',
                        'description' => 'Permet de modifier les informations d\'un utilisateur'
                    ],
                    [
                        'category' => 'crud',
                        'action' => 'supprimer',
                        'libelle' => 'Supprimer un utilisateur',
                        'description' => 'Permet de supprimer un utilisateur'
                    ],
                    [
                        'category' => 'admin',
                        'action' => 'bloquer',
                        'libelle' => 'Bloquer un utilisateur',
                        'description' => 'Permet de bloquer un utilisateur'
                    ],
                    [
                        'category' => 'admin',
                        'action' => 'geler',
                        'libelle' => 'Geler un utilisateur',
                        'description' => 'Permet de geler temporairement un compte utilisateur'
                    ],
                    [
                        'category' => 'admin',
                        'action' => 'degeler',
                        'libelle' => 'Dégeler un utilisateur',
                        'description' => 'Permet de dégeler un compte utilisateur'
                    ],
                ]
            ],

            // ============================================================
            // RÔLES
            // ============================================================
            [
                'module' => [
                    'code' => 'roles',
                    'libelle' => 'Rôles',
                    'description' => 'Gestion des rôles',
                    'icone' => 'shield-alt',
                    'color' => '#e67e22',
                    'ordre' => 4,
                ],
                'permissions' => [
                    [
                        'category' => 'crud',
                        'action' => 'afficher',
                        'libelle' => 'Afficher les rôles',
                        'description' => 'Permet de visualiser la liste des rôles'
                    ],
                    [
                        'category' => 'crud',
                        'action' => 'creer',
                        'libelle' => 'Créer un rôle',
                        'description' => 'Permet de créer un nouveau rôle'
                    ],
                    [
                        'category' => 'crud',
                        'action' => 'modifier',
                        'libelle' => 'Modifier un rôle',
                        'description' => 'Permet de modifier les informations d\'un rôle'
                    ],
                    [
                        'category' => 'crud',
                        'action' => 'supprimer',
                        'libelle' => 'Supprimer un rôle',
                        'description' => 'Permet de supprimer un rôle'
                    ],
                    [
                        'category' => 'admin',
                        'action' => 'gerer_permissions',
                        'libelle' => 'Gérer les permissions des rôles',
                        'description' => 'Permet d\'attribuer ou de retirer des permissions à un rôle'
                    ],
                ]
            ],

            // ============================================================
            // PERMISSIONS
            // ============================================================
            [
                'module' => [
                    'code' => 'permissions',
                    'libelle' => 'Permissions',
                    'description' => 'Gestion des permissions',
                    'icone' => 'key',
                    'color' => '#2ecc71',
                    'ordre' => 5,
                ],
                'permissions' => [
                    [
                        'category' => 'crud',
                        'action' => 'afficher',
                        'libelle' => 'Afficher les permissions',
                        'description' => 'Permet de visualiser la liste des permissions'
                    ],
                    [
                        'category' => 'crud',
                        'action' => 'creer',
                        'libelle' => 'Créer une permission',
                        'description' => 'Permet de créer une nouvelle permission'
                    ],
                    [
                        'category' => 'crud',
                        'action' => 'modifier',
                        'libelle' => 'Modifier une permission',
                        'description' => 'Permet de modifier les informations d\'une permission'
                    ],
                    [
                        'category' => 'crud',
                        'action' => 'supprimer',
                        'libelle' => 'Supprimer une permission',
                        'description' => 'Permet de supprimer une permission'
                    ],
                ]
            ],

            // ============================================================
            // GROUPES DE PERMISSIONS
            // ============================================================
            [
                'module' => [
                    'code' => 'permission_groups',
                    'libelle' => 'Groupes de permissions',
                    'description' => 'Gestion des groupes de permissions',
                    'icone' => 'folder',
                    'color' => '#f39c12',
                    'ordre' => 6,
                ],
                'permissions' => [
                    [
                        'category' => 'crud',
                        'action' => 'afficher',
                        'libelle' => 'Afficher les groupes de permissions',
                        'description' => 'Permet de visualiser les groupes de permissions'
                    ],
                    [
                        'category' => 'crud',
                        'action' => 'creer',
                        'libelle' => 'Créer un groupe de permissions',
                        'description' => 'Permet de créer un groupe de permissions'
                    ],
                    [
                        'category' => 'crud',
                        'action' => 'modifier',
                        'libelle' => 'Modifier un groupe de permissions',
                        'description' => 'Permet de modifier un groupe de permissions'
                    ],
                    [
                        'category' => 'crud',
                        'action' => 'supprimer',
                        'libelle' => 'Supprimer un groupe de permissions',
                        'description' => 'Permet de supprimer un groupe de permissions'
                    ],
                ]
            ],

            // ============================================================
            // PARTENAIRES
            // ============================================================
            [
                'module' => [
                    'code' => 'partners',
                    'libelle' => 'Partenaires',
                    'description' => 'Gestion des partenaires',
                    'icone' => 'handshake',
                    'color' => '#2c3e50',
                    'ordre' => 7,
                ],
                'permissions' => [
                    [
                        'category' => 'crud',
                        'action' => 'afficher',
                        'libelle' => 'Afficher les partenaires',
                        'description' => 'Permet de visualiser la liste des partenaires'
                    ],
                    [
                        'category' => 'crud',
                        'action' => 'creer',
                        'libelle' => 'Créer un partenaire',
                        'description' => 'Permet de créer un nouveau partenaire'
                    ],
                    [
                        'category' => 'crud',
                        'action' => 'modifier',
                        'libelle' => 'Modifier un partenaire',
                        'description' => 'Permet de modifier les informations d\'un partenaire'
                    ],
                    [
                        'category' => 'crud',
                        'action' => 'supprimer',
                        'libelle' => 'Supprimer un partenaire',
                        'description' => 'Permet de supprimer un partenaire'
                    ],
                ]
            ],

            // ============================================================
            // RÉSEAUX
            // ============================================================
            [
                'module' => [
                    'code' => 'reseaux',
                    'libelle' => 'Réseaux',
                    'description' => 'Gestion des réseaux',
                    'icone' => 'network-wired',
                    'color' => '#2980b9',
                    'ordre' => 8,
                ],
                'permissions' => [
                    [
                        'category' => 'crud',
                        'action' => 'afficher',
                        'libelle' => 'Afficher les réseaux',
                        'description' => 'Permet de visualiser la liste des réseaux'
                    ],
                    [
                        'category' => 'crud',
                        'action' => 'creer',
                        'libelle' => 'Créer un réseau',
                        'description' => 'Permet de créer un nouveau réseau'
                    ],
                    [
                        'category' => 'crud',
                        'action' => 'modifier',
                        'libelle' => 'Modifier un réseau',
                        'description' => 'Permet de modifier les informations d\'un réseau'
                    ],
                    [
                        'category' => 'crud',
                        'action' => 'supprimer',
                        'libelle' => 'Supprimer un réseau',
                        'description' => 'Permet de supprimer un réseau'
                    ],
                ]
            ],

            // ============================================================
            // AGENCES
            // ============================================================
            [
                'module' => [
                    'code' => 'agences',
                    'libelle' => 'Agences',
                    'description' => 'Gestion des agences',
                    'icone' => 'building',
                    'color' => '#27ae60',
                    'ordre' => 9,
                ],
                'permissions' => [
                    [
                        'category' => 'crud',
                        'action' => 'afficher',
                        'libelle' => 'Afficher les agences',
                        'description' => 'Permet de visualiser la liste des agences'
                    ],
                    [
                        'category' => 'crud',
                        'action' => 'creer',
                        'libelle' => 'Créer une agence',
                        'description' => 'Permet de créer une nouvelle agence'
                    ],
                    [
                        'category' => 'crud',
                        'action' => 'modifier',
                        'libelle' => 'Modifier une agence',
                        'description' => 'Permet de modifier les informations d\'une agence'
                    ],
                    [
                        'category' => 'crud',
                        'action' => 'supprimer',
                        'libelle' => 'Supprimer une agence',
                        'description' => 'Permet de supprimer une agence'
                    ],
                    [
                        'category' => 'admin',
                        'action' => 'assigner_utilisateurs',
                        'libelle' => 'Assigner des utilisateurs',
                        'description' => 'Permet d\'assigner des utilisateurs à une agence'
                    ],
                ]
            ],

            // ============================================================
            // QUESTIONS DE SÉCURITÉ
            // ============================================================
            [
                'module' => [
                    'code' => 'security_questions',
                    'libelle' => 'Questions de sécurité',
                    'description' => 'Gestion des questions de sécurité',
                    'icone' => 'question-circle',
                    'color' => '#8e44ad',
                    'ordre' => 10,
                ],
                'permissions' => [
                    [
                        'category' => 'admin',
                        'action' => 'gerer',
                        'libelle' => 'Gérer les questions de sécurité',
                        'description' => 'Permet de gérer les questions de sécurité (création, modification, suppression)'
                    ],
                    [
                        'category' => 'crud',
                        'action' => 'afficher',
                        'libelle' => 'Afficher les questions de sécurité',
                        'description' => 'Permet d\'afficher les questions de sécurité'
                    ],
                ]
            ],

            // ============================================================
            // RESTRICTIONS IP
            // ============================================================
            [
                'module' => [
                    'code' => 'ip_restrictions',
                    'libelle' => 'Restrictions IP',
                    'description' => 'Gestion des restrictions d\'IP',
                    'icone' => 'globe2',
                    'color' => '#dc3545',
                    'ordre' => 11,
                ],
                'permissions' => [
                    [
                        'category' => 'crud',
                        'action' => 'afficher',
                        'libelle' => 'Afficher les restrictions IP',
                        'description' => 'Permet de visualiser les restrictions IP configurées'
                    ],
                    [
                        'category' => 'crud',
                        'action' => 'creer',
                        'libelle' => 'Créer une restriction IP',
                        'description' => 'Permet de créer une nouvelle restriction IP'
                    ],
                    [
                        'category' => 'crud',
                        'action' => 'supprimer',
                        'libelle' => 'Supprimer une restriction IP',
                        'description' => 'Permet de supprimer une restriction IP'
                    ],
                ]
            ],

            // ============================================================
            // LOGS & AUDIT
            // ============================================================
            [
                'module' => [
                    'code' => 'audit',
                    'libelle' => 'Logs & Audit',
                    'description' => 'Gestion des logs et de l\'audit',
                    'icone' => 'clipboard-data',
                    'color' => '#6c757d',
                    'ordre' => 12,
                ],
                'permissions' => [
                    [
                        'category' => 'report',
                        'action' => 'consulter_les_logs',
                        'libelle' => 'Consulter les logs d\'activité',
                        'description' => 'Permet de consulter l\'ensemble des logs d\'activité du système'
                    ],
                ]
            ],

            // ============================================================
            // FAQ
            // ============================================================
            [
                'module' => [
                    'code' => 'faqs',
                    'libelle' => 'FAQ',
                    'description' => 'Gestion des questions fréquentes',
                    'icone' => 'question-square',
                    'color' => '#0d6efd',
                    'ordre' => 13,
                ],
                'permissions' => [
                    [
                        'category' => 'crud',
                        'action' => 'creer',
                        'libelle' => 'Créer une FAQ',
                        'description' => 'Permet de créer une nouvelle FAQ'
                    ],
                    [
                        'category' => 'crud',
                        'action' => 'modifier',
                        'libelle' => 'Modifier une FAQ',
                        'description' => 'Permet de modifier une FAQ existante'
                    ],
                    [
                        'category' => 'crud',
                        'action' => 'supprimer',
                        'libelle' => 'Supprimer une FAQ',
                        'description' => 'Permet de supprimer une FAQ'
                    ],
                ]
            ],

            // ============================================================
            // GROUPES DE NOTIFICATION
            // ============================================================
            [
                'module' => [
                    'code' => 'group_notifs',
                    'libelle' => 'Groupes de notification',
                    'description' => 'Gestion des groupes de notification',
                    'icone' => 'bell-fill',
                    'color' => '#fd7e14',
                    'ordre' => 14,
                ],
                'permissions' => [
                    [
                        'category' => 'crud',
                        'action' => 'afficher',
                        'libelle' => 'Afficher les groupes de notification',
                        'description' => 'Permet de visualiser les groupes de notification'
                    ],
                    [
                        'category' => 'crud',
                        'action' => 'creer',
                        'libelle' => 'Créer un groupe de notification',
                        'description' => 'Permet de créer un groupe de notification'
                    ],
                    [
                        'category' => 'crud',
                        'action' => 'modifier',
                        'libelle' => 'Modifier un groupe de notification',
                        'description' => 'Permet de modifier un groupe de notification'
                    ],
                    [
                        'category' => 'crud',
                        'action' => 'supprimer',
                        'libelle' => 'Supprimer un groupe de notification',
                        'description' => 'Permet de supprimer un groupe de notification'
                    ],
                    [
                        'category' => 'admin',
                        'action' => 'assigner',
                        'libelle' => 'Assigner des utilisateurs à un groupe',
                        'description' => 'Permet d\'assigner des utilisateurs à un groupe de notification'
                    ],
                ]
            ],

            // ============================================================
            // NOTIFICATIONS
            // ============================================================
            [
                'module' => [
                    'code' => 'notifications',
                    'libelle' => 'Notifications',
                    'description' => 'Gestion des notifications utilisateur',
                    'icone' => 'bell',
                    'color' => '#fd7e14',
                    'ordre' => 15,
                ],
                'permissions' => [
                    [
                        'category' => 'crud',
                        'action' => 'creer',
                        'libelle' => 'Créer une notification (admin)',
                        'description' => 'Permet de créer une notification pour un utilisateur (admin)'
                    ],
                ]
            ],

            // ============================================================
            // ESPACE CLIENT
            // ============================================================
            [
                'module' => [
                    'code' => 'espace_client',
                    'libelle' => 'Espace Client',
                    'description' => 'Espace Client',
                    'icone' => 'file-earmark-text',
                    'color' => '#e67e22',
                    'ordre' => 16,
                ],
                'permissions' => [
                    [
                        'category' => 'report',
                        'action' => 'dashboard',
                        'libelle' => 'Afficher le tableau de bord client',
                        'description' => 'Permet d\'afficher le tableau de bord du client'
                    ],
                    [
                        'category' => 'report',
                        'action' => 'statistiques',
                        'libelle' => 'Voir les statistiques client',
                        'description' => 'Permet de visualiser les statistiques du client'
                    ],
                    // [
                    //     'category' => 'report',
                    //     'action' => 'echeances',
                    //     'libelle' => 'Voir les prochaines échéances',
                    //     'description' => 'Permet de visualiser les prochaines échéances de paiement'
                    // ],
                    [
                        'category' => 'crud',
                        'action' => 'liste_contrats',
                        'libelle' => 'Afficher la liste des contrats',
                        'description' => 'Permet de visualiser la liste des contrats du client'
                    ],
                    [
                        'category' => 'crud',
                        'action' => 'details_contrat',
                        'libelle' => 'Afficher les détails d\'un contrat',
                        'description' => 'Permet de visualiser les détails complets d\'un contrat'
                    ],
                    [
                        'category' => 'report',
                        'action' => 'etat_cotisation',
                        'libelle' => 'Afficher l\'état de cotisation d\'un contrat',
                        'description' => 'Permet de visualiser l\'état de cotisation d\'un contrat'
                    ],
                    [
                        'category' => 'report',
                        'action' => 'contrats_factures_impayees',
                        'libelle' => 'Afficher les contrats avec factures impayées',
                        'description' => 'Permet de visualiser les contrats ayant des factures impayées'
                    ],
                    [
                        'category' => 'crud',
                        'action' => 'ajouter_contrat',
                        'libelle' => 'Ajouter un contrat au compte',
                        'description' => 'Permet d\'ajouter un contrat au compte du client'
                    ],
                ]

            ],

            // ============================================================
            // TYPES DE PRODUITS
            // ============================================================
            [
                'module' => [
                    'code' => 'produits',
                    'libelle' => 'Types de produits',
                    'description' => 'Gestion des types de produits',
                    'icone' => 'tags',
                    'color' => '#8e44ad',
                    'ordre' => 17,
                ],
                'permissions' => [
                    [
                        'category' => 'crud',
                        'action' => 'afficher',
                        'libelle' => 'Afficher les types de produits',
                        'description' => 'Permet de visualiser la liste des types de produits'
                    ],
                    [
                        'category' => 'crud',
                        'action' => 'creer',
                        'libelle' => 'Créer un type de produit',
                        'description' => 'Permet de créer un nouveau type de produit'
                    ],
                    [
                        'category' => 'crud',
                        'action' => 'modifier',
                        'libelle' => 'Modifier un type de produit',
                        'description' => 'Permet de modifier un type de produit'
                    ],
                    [
                        'category' => 'crud',
                        'action' => 'supprimer',
                        'libelle' => 'Supprimer un type de produit',
                        'description' => 'Permet de supprimer un type de produit'
                    ],
                ]
            ],

            // ============================================================
            // PRODUITS
            // ============================================================
            [
                'module' => [
                    'code' => 'produits',
                    'libelle' => 'Produits',
                    'description' => 'Gestion des produits d\'assurance',
                    'icone' => 'box',
                    'color' => '#2ecc71',
                    'ordre' => 18,
                ],
                'permissions' => [
                    [
                        'category' => 'crud',
                        'action' => 'afficher',
                        'libelle' => 'Afficher les produits',
                        'description' => 'Permet de visualiser la liste des produits'
                    ],
                    [
                        'category' => 'crud',
                        'action' => 'creer',
                        'libelle' => 'Créer un produit',
                        'description' => 'Permet de créer un nouveau produit'
                    ],
                    [
                        'category' => 'crud',
                        'action' => 'modifier',
                        'libelle' => 'Modifier un produit',
                        'description' => 'Permet de modifier un produit'
                    ],
                    [
                        'category' => 'crud',
                        'action' => 'supprimer',
                        'libelle' => 'Supprimer un produit',
                        'description' => 'Permet de supprimer un produit'
                    ],
                    [
                        'category' => 'admin',
                        'action' => 'gerer_formules',
                        'libelle' => 'Gérer les formules de produits',
                        'description' => 'Permet de gérer les formules associées aux produits'
                    ],
                    [
                        'category' => 'admin',
                        'action' => 'gerer_prestations',
                        'libelle' => 'Gérer les prestations de produits',
                        'description' => 'Permet de gérer les prestations associées aux produits'
                    ],
                ]
            ],

            // ============================================================
            // PRESTATIONS
            // ============================================================
            [
                'module' => [
                    'code' => 'prestations',
                    'libelle' => 'Prestations',
                    'description' => 'Gestion des prestations et catégories',
                    'icone' => 'clipboard-list',
                    'color' => '#f39c12',
                    'ordre' => 19,
                ],
                'permissions' => [
                    [
                        'category' => 'crud',
                        'action' => 'afficher',
                        'libelle' => 'Afficher les prestations',
                        'description' => 'Permet de visualiser la liste des prestations'
                    ],
                    [
                        'category' => 'crud',
                        'action' => 'creer',
                        'libelle' => 'Créer une prestation',
                        'description' => 'Permet de créer une nouvelle prestation'
                    ],
                    [
                        'category' => 'crud',
                        'action' => 'modifier',
                        'libelle' => 'Modifier une prestation',
                        'description' => 'Permet de modifier une prestation'
                    ],
                    [
                        'category' => 'crud',
                        'action' => 'supprimer',
                        'libelle' => 'Supprimer une prestation',
                        'description' => 'Permet de supprimer une prestation'
                    ],
                    [
                        'category' => 'admin',
                        'action' => 'gerer_categories',
                        'libelle' => 'Gérer les catégories de prestations',
                        'description' => 'Permet de gérer les catégories de prestations'
                    ],
                ]
            ],

            // ============================================================
            // RENDEZ-VOUS (RDV)
            // ============================================================
            [
                'module' => [
                    'code' => 'rdvs',
                    'libelle' => 'Rendez-vous',
                    'description' => 'Gestion des rendez-vous',
                    'icone' => 'calendar-check',
                    'color' => '#0d6efd',
                    'ordre' => 20,
                ],
                'permissions' => [
                    [
                        'category' => 'crud',
                        'action' => 'creer',
                        'libelle' => 'Créer un rendez-vous',
                        'description' => 'Permet de créer un rendez-vous'
                    ],
                    [
                        'category' => 'crud',
                        'action' => 'afficher',
                        'libelle' => 'Afficher les rendez-vous',
                        'description' => 'Permet de visualiser les rendez-vous'
                    ],
                    [
                        'category' => 'crud',
                        'action' => 'annuler',
                        'libelle' => 'Annuler un rendez-vous',
                        'description' => 'Permet d\'annuler un rendez-vous'
                    ],
                    [
                        'category' => 'admin',
                        'action' => 'admin',
                        'libelle' => 'Administrer les rendez-vous',
                        'description' => 'Permet d\'administrer tous les rendez-vous (changement de statut, assignation)'
                    ],
                ]
            ],

            // ============================================================
            // JOURS FÉRIÉS
            // ============================================================
            [
                'module' => [
                    'code' => 'jour_feries',
                    'libelle' => 'Jours fériés',
                    'description' => 'Gestion des jours fériés',
                    'icone' => 'calendar-day',
                    'color' => '#dc3545',
                    'ordre' => 21,
                ],
                'permissions' => [
                    [
                        'category' => 'crud',
                        'action' => 'afficher',
                        'libelle' => 'Afficher les jours fériés',
                        'description' => 'Permet de visualiser la liste des jours fériés'
                    ],
                    [
                        'category' => 'crud',
                        'action' => 'creer',
                        'libelle' => 'Créer un jour férié',
                        'description' => 'Permet de créer un nouveau jour férié'
                    ],
                    [
                        'category' => 'crud',
                        'action' => 'modifier',
                        'libelle' => 'Modifier un jour férié',
                        'description' => 'Permet de modifier un jour férié'
                    ],
                    [
                        'category' => 'crud',
                        'action' => 'supprimer',
                        'libelle' => 'Supprimer un jour férié',
                        'description' => 'Permet de supprimer un jour férié'
                    ],
                ]
            ],
        ];
    }
}
