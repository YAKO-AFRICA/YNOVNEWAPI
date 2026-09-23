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
            // MOTIFS DE TRAITEMENT
            // ============================================================
            [
                'module' => [
                    'code' => 'motif_traitements',
                    'libelle' => 'Motifs de traitement',
                    'description' => 'Gestion des motifs de traitement',
                    'icone' => 'clipboard-check',
                    'color' => '#6f42c1',
                    'ordre' => 14,
                ],
                'permissions' => [
                    [
                        'category' => 'crud',
                        'action' => 'afficher',
                        'libelle' => 'Afficher les motifs de traitement',
                        'description' => 'Permet de visualiser la liste et les détails des motifs de traitement'
                    ],
                    [
                        'category' => 'crud',
                        'action' => 'creer',
                        'libelle' => 'Créer un motif de traitement',
                        'description' => 'Permet de créer un motif de traitement'
                    ],
                    [
                        'category' => 'crud',
                        'action' => 'modifier',
                        'libelle' => 'Modifier un motif de traitement',
                        'description' => 'Permet de modifier un motif de traitement'
                    ],
                    [
                        'category' => 'crud',
                        'action' => 'supprimer',
                        'libelle' => 'Supprimer un motif de traitement',
                        'description' => 'Permet de supprimer un motif de traitement'
                    ],
                    [
                        'category' => 'admin',
                        'action' => 'toggle',
                        'libelle' => 'Activer / désactiver un motif',
                        'description' => 'Permet d\'activer ou de désactiver un motif de traitement'
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
                    'ordre' => 15,
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

            // // ============================================================
            // // SIGNATURE ÉLECTRONIQUE
            // // ============================================================
            // [
            //     'module' => [
            //         'code' => 'signature',
            //         'libelle' => 'Signature électronique',
            //         'description' => 'Gestion de la signature électronique',
            //         'icone' => 'pen-fancy',
            //         'color' => '#9b59b6',
            //         'ordre' => 17,
            //     ],
            //     'permissions' => [
            //         [
            //             'category' => 'crud',
            //             'action' => 'envoyer',
            //             'libelle' => 'Envoyer les liens de signature',
            //             'description' => 'Permet d\'envoyer les liens de signature par Email, SMS ou WhatsApp'
            //         ],
            //         [
            //             'category' => 'crud',
            //             'action' => 'afficher',
            //             'libelle' => 'Afficher les demandes de signature',
            //             'description' => 'Permet de visualiser les demandes de signature'
            //         ],
            //     ]
            // ],

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
                    'description' => 'Gestion complète des rendez-vous',
                    'icone' => 'calendar-check',
                    'color' => '#0d6efd',
                    'ordre' => 20,
                ],
                'permissions' => [
                    // ============================================================
                    // CRUD - Actions de base
                    // ============================================================
                    [
                        'category' => 'crud',
                        'action' => 'creer',
                        'libelle' => 'Créer un rendez-vous',
                        'description' => 'Permet de créer un rendez-vous',
                    ],
                    [
                        'category' => 'crud',
                        'action' => 'afficher',
                        'libelle' => 'Afficher les rendez-vous',
                        'description' => 'Permet de visualiser la liste et les détails des rendez-vous',
                    ],
                    [
                        'category' => 'crud',
                        'action' => 'modifier',
                        'libelle' => 'Modifier un rendez-vous',
                        'description' => 'Permet de modifier les informations d\'un rendez-vous',
                    ],
                    [
                        'category' => 'crud',
                        'action' => 'annuler',
                        'libelle' => 'Annuler un rendez-vous',
                        'description' => 'Permet d\'annuler un rendez-vous',
                    ],
                    [
                        'category' => 'crud',
                        'action' => 'supprimer',
                        'libelle' => 'Supprimer un rendez-vous',
                        'description' => 'Permet de supprimer définitivement un rendez-vous',
                    ],

                    // // ============================================================
                    // // DASHBOARD - Tableau de bord
                    // // ============================================================
                    // [
                    //     'category' => 'dashboard',
                    //     'action' => 'voir_dashboard',
                    //     'libelle' => 'Voir le tableau de bord',
                    //     'description' => 'Permet d\'accéder au tableau de bord des rendez-vous',
                    // ],

                    // ============================================================
                    // TRAITEMENT - Gestion des RDV
                    // ============================================================
                    [
                        'category' => 'traitement',
                        'action' => 'traiter',
                        'libelle' => 'Traiter un rendez-vous',
                        'description' => 'Permet de traiter un rendez-vous',
                    ],
                    [
                        'category' => 'traitement',
                        'action' => 'reporter',
                        'libelle' => 'Reporter un rendez-vous',
                        'description' => 'Permet de reporter un rendez-vous (client absent)',
                    ],
                    [
                        'category' => 'traitement',
                        'action' => 'rejeter',
                        'libelle' => 'Rejeter un rendez-vous',
                        'description' => 'Permet de rejeter un rendez-vous',
                    ],
                    [
                        'category' => 'traitement',
                        'action' => 'expirer',
                        'libelle' => 'Marquer un rendez-vous comme expiré',
                        'description' => 'Permet de marquer un rendez-vous comme expiré',
                    ],

                    [
                        'category' => 'traitement',
                        'action' => 'transmettre_bordereau_gest_prestation',
                        'libelle' => 'Transmettre un bordereau de base de gestion de prestations pour calculer la PM',
                        'description' => 'Permet de transmettre un bordereau de gestion de prestations',
                    ],
                    [
                        'category' => 'traitement',
                        'action' => 'import_bordereau_final',
                        'libelle' => 'Importer un bordereau final (bordereau avec PM calculée) pour le traitement des rendez-vous',
                        'description' => 'Permet d\'importer un bordereau final pour le traitement des rendez-vous',
                    ],




                  
                    [
                        'category' => 'gestionnaire',
                        'action' => 'retransmettre',
                        'libelle' => 'Retransmettre un gestionnaire',
                        'description' => 'Permet de retransmettre un rendez-vous à un autre gestionnaire',
                    ],
                    [
                        'category' => 'gestionnaire',
                        'action' => 'calendrier',
                        'libelle' => 'Voir le calendrier des rendez-vous',
                        'description' => 'Permet de voir le calendrier des rendez-vous',
                    ],

                    [
                        'category' => 'routing',
                        'action' => 'reequilibrer',
                        'libelle' => 'Rééquilibrer la charge',
                        'description' => 'Permet de rééquilibrer la charge des gestionnaires',
                    ],


                    // ============================================================
                    // PRESENCE - Signalement client
                    // ============================================================
                    [
                        'category' => 'presence',
                        'action' => 'signaler_presence',
                        'libelle' => 'Signaler la présence',
                        'description' => 'Permet de signaler la présence d\'un client',
                    ],
                    [
                        'category' => 'presence',
                        'action' => 'voir_arrives',
                        'libelle' => 'Voir les clients arrivés',
                        'description' => 'Permet de voir les clients arrivés en agence',
                    ],

                    // ============================================================
                    // HISTORIQUE & OBSERVATIONS
                    // ============================================================
                    [
                        'category' => 'historique',
                        'action' => 'voir_historique',
                        'libelle' => 'Voir l\'historique',
                        'description' => 'Permet de voir l\'historique des traitements d\'un rendez-vous',
                    ],

                    // // ============================================================
                    // // EXPORT - Export des données
                    // // ============================================================
                    // [
                    //     'category' => 'export',
                    //     'action' => 'exporter',
                    //     'libelle' => 'Exporter les rendez-vous',
                    //     'description' => 'Permet d\'exporter la liste des rendez-vous',
                    //     'code' => 'rdvs.exporter'
                    // ],
                ],
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
