<?php
// database/seeders/SuperAdminUserSeeder.php

namespace Database\Seeders;

use App\Models\Api\Ynov\parameter\User;
use App\Models\Api\Ynov\parameter\Role;
use App\Models\Api\Ynov\parameter\UserDetails;
use App\Models\Api\Ynov\parameter\Permission;
use App\Models\Api\Ynov\parameter\PermissionGroup;
use App\Models\Api\Ynov\parameter\RolePermission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SuperAdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🚀 Début de la création du Super Admin...');

        // 1. Créer les groupes de permissions (modules)
        $this->command->info('📦 Création des groupes de permissions...');
        $this->createPermissionGroups();

        // 2. Créer toutes les permissions
        $this->command->info('🔐 Création des permissions...');
        $this->createPermissions();

        // 3. Vérifier que le rôle Super Admin existe
        $superAdminRole = Role::where('code', 'super_admin')->first();

        if (!$superAdminRole) {
            $this->command->info('👤 Création du rôle Super Admin...');
            $superAdminRole = Role::create([
                'uuid_role' => (string) Str::uuid(),
                'code' => 'super_admin',
                'libelle' => 'Super Administrateur',
                'description' => 'Rôle disposant de tous les droits sur la plateforme. Non modifiable et non supprimable via interface.',
                'is_system' => true,
                'is_super_admin' => true,
                'level' => 1,
                'priority' => 0,
                'status' => 'actif',
                'created_by' => null,
                'updated_by' => null,
            ]);
        }

        // 4. Assigner toutes les permissions au rôle Super Admin
        $this->command->info('🔗 Assignation des permissions au rôle Super Admin...');
        $this->assignAllPermissionsToRole($superAdminRole);

        // 5. Créer ou mettre à jour l'utilisateur Super Admin
        $this->command->info('👤 Création de l\'utilisateur Super Admin...');
        $user = User::updateOrCreate(
            ['email' => 'brucedev2022@gmail.com'],
            [
                'uuid_user' => (string) Str::uuid(),
                'login' => 'super_admin',
                'password' => Hash::make('SuperAdmin@2026'),
                'role_uuid' => $superAdminRole->uuid_role,
                'user_type' => 'super_admin',
                'partner_uuid' => null,
                'reseau_uuid' => null,
                'status' => 'actif',
                'is_first_login' => true,
                'is_online' => false,
                'is_locked' => false,
                'password_changed_at' => now(),
                'password_expires_at' => now()->addDays(90),
                'last_login_at' => null,
                'last_activity_at' => null,
                'email_verified_at' => now(),
                'failed_login_count' => 0,
                'freeze_level' => 0,
                'frozen_until' => null,
                'freeze_count' => 0,
                'blocked_reason' => null,
                'blocked_by' => null,
                'blocked_at' => null,
                'two_factor_secret' => null,
                'two_factor_enabled' => false,
                'two_factor_recovery_codes' => null,
                'preferences' => [
                    'theme' => 'light',
                    'language' => 'fr',
                    'timezone' => 'Europe/Paris',
                    'notifications' => [
                        'email' => true,
                        'push' => true,
                    ],
                ],
                'metadata' => [
                    'created_by_seeder' => true,
                    'seeded_at' => now()->toDateTimeString(),
                ],
                'remember_token' => null,
            ]
        );

        // 6. Créer les détails de l'utilisateur
        $this->command->info('📝 Création des détails de l\'utilisateur...');
        UserDetails::updateOrCreate(
            ['user_uuid' => $user->uuid_user],
            [
                'uuid_user_details' => (string) Str::uuid(),
                'code_agent' => 'SA001',
                'matricule' => 'SA-2026-001',
                'nom' => 'Admin',
                'prenoms' => 'Super',
                'fonction' => 'Super Administrateur',
                'service' => 'Administration',
                'departement' => 'IT',
                'mobile_1' => '+225 00 00 00 00',
                'mobile_2' => null,
                'telephone_fixe' => null,
                'email_pro' => 'brucedev2022@gmail.com',
                'photo' => null,
                'date_naissance' => null,
                'lieu_naissance' => null,
                'lieu_residence' => null,
                'nationalite' => 'Côte d\'Ivoire',
                'genre' => 'M',
                'civilite' => 'M.',
                'adresse_complete' => null,
                'ville' => 'Abidjan',
                'code_postal' => null,
                'pays' => 'Côte d\'Ivoire',
                'date_embauche' => now(),
                'statut_employe' => 'actif',
                'type_contrat' => 'CDI',
                'preferences' => [],
                'created_by' => null,
                'updated_by' => null,
                'deleted_by' => null,
            ]
        );

        $this->command->info('✅ Super Admin créé avec succès !');
        $this->command->newLine();
        $this->command->info('📧 Email: brucedev2022@gmail.com');
        $this->command->info('🔑 Mot de passe: SuperAdmin@2026');
        $this->command->newLine();
        $this->command->warn('⚠️  Pensez à modifier le mot de passe après la première connexion !');
        $this->command->newLine();
        $this->command->info('📋 Toutes les permissions ont été assignées au rôle Super Admin.');
    }

    /**
     * Créer les groupes de permissions
     */
    // private function createPermissionGroups(): void
    // {
    //     $groups = [
    //         [
    //             'code' => 'users',
    //             'libelle' => 'Utilisateurs',
    //             'description' => 'Gestion des utilisateurs',
    //             'icone' => 'users',
    //             'color' => '#3490dc',
    //             'ordre' => 1,
    //         ],
    //         [
    //             'code' => 'roles',
    //             'libelle' => 'Rôles',
    //             'description' => 'Gestion des rôles',
    //             'icone' => 'shield-alt',
    //             'color' => '#e67e22',
    //             'ordre' => 2,
    //         ],
    //         [
    //             'code' => 'permissions',
    //             'libelle' => 'Permissions',
    //             'description' => 'Gestion des permissions',
    //             'icone' => 'key',
    //             'color' => '#2ecc71',
    //             'ordre' => 3,
    //         ],
    //         [
    //             'code' => 'permission_groups',
    //             'libelle' => 'Groupes de permissions',
    //             'description' => 'Gestion des groupes de permissions',
    //             'icone' => 'folder',
    //             'color' => '#f39c12',
    //             'ordre' => 4,
    //         ],
    //         [
    //             'code' => 'ip_restrictions',
    //             'libelle' => 'Restrictions IP',
    //             'description' => 'Gestion des restrictions d\'IP',
    //             'icone' => 'ip',
    //             'color' => '#e74c3c',
    //             'ordre' => 5,
    //         ],
    //         [
    //             'code' => 'profile',
    //             'libelle' => 'Profil',
    //             'description' => 'Gestion du profil utilisateur',
    //             'icone' => 'user',
    //             'color' => '#3490dc',
    //             'ordre' => 6,
    //         ],
    //         [
    //             'code' => 'auth',
    //             'libelle' => 'Authentification',
    //             'description' => 'Gestion de l\'authentification',
    //             'icone' => 'lock',
    //             'color' => '#9b59b6',
    //             'ordre' => 7,
    //         ],
    //     ];

    //     foreach ($groups as $group) {
    //         PermissionGroup::updateOrCreate(
    //             ['code' => $group['code']],
    //             [
    //                 'uuid_permission_group' => (string) Str::uuid(),
    //                 'libelle' => $group['libelle'],
    //                 'description' => $group['description'],
    //                 'icone' => $group['icone'],
    //                 'color' => $group['color'],
    //                 'ordre_affichage' => $group['ordre'],
    //                 'status' => 'actif',
    //                 'created_by' => null,
    //                 'updated_by' => null,
    //                 'deleted_by' => null,
    //             ]
    //         );
    //     }
    // }

    /**
     * Créer les groupes de permissions
     */
    private function createPermissionGroups(): void
    {
        $groups = [
            [
                'code' => 'users',
                'libelle' => 'Utilisateurs',
                'description' => 'Gestion des utilisateurs',
                'icone' => 'users',
                'color' => '#3490dc',
                'ordre' => 1,
            ],
            [
                'code' => 'roles',
                'libelle' => 'Rôles',
                'description' => 'Gestion des rôles',
                'icone' => 'shield-alt',
                'color' => '#e67e22',
                'ordre' => 2,
            ],
            [
                'code' => 'permissions',
                'libelle' => 'Permissions',
                'description' => 'Gestion des permissions',
                'icone' => 'key',
                'color' => '#2ecc71',
                'ordre' => 3,
            ],
            [
                'code' => 'permission_groups',
                'libelle' => 'Groupes de permissions',
                'description' => 'Gestion des groupes de permissions',
                'icone' => 'folder',
                'color' => '#f39c12',
                'ordre' => 4,
            ],
            [
                'code' => 'ip_restrictions',
                'libelle' => 'Restrictions IP',
                'description' => 'Gestion des restrictions d\'IP',
                'icone' => 'ip',
                'color' => '#e74c3c',
                'ordre' => 5,
            ],
            [
                'code' => 'profile',
                'libelle' => 'Profil',
                'description' => 'Gestion du profil utilisateur',
                'icone' => 'user',
                'color' => '#3490dc',
                'ordre' => 6,
            ],
            [
                'code' => 'auth',
                'libelle' => 'Authentification',
                'description' => 'Gestion de l\'authentification',
                'icone' => 'lock',
                'color' => '#9b59b6',
                'ordre' => 7,
            ],
            // ============================================================
            // NOUVEAUX GROUPES
            // ============================================================
            [
                'code' => 'partners',
                'libelle' => 'Partenaires',
                'description' => 'Gestion des partenaires',
                'icone' => 'handshake',
                'color' => '#2c3e50',
                'ordre' => 8,
            ],
            [
                'code' => 'reseaux',
                'libelle' => 'Réseaux',
                'description' => 'Gestion des réseaux',
                'icone' => 'network-wired',
                'color' => '#2980b9',
                'ordre' => 9,
            ],
            [
                'code' => 'agences',
                'libelle' => 'Agences',
                'description' => 'Gestion des agences',
                'icone' => 'building',
                'color' => '#27ae60',
                'ordre' => 10,
            ],
            [
                'code' => 'audit',
                'libelle' => 'Audit & Logs',
                'description' => 'Gestion des logs et audit',
                'icone' => 'clipboard-list',
                'color' => '#7f8c8d',
                'ordre' => 11,
            ],
            [
                'code' => 'security_questions',
                'libelle' => 'Questions de sécurité',
                'description' => 'Gestion des questions de sécurité',
                'icone' => 'question-circle',
                'color' => '#8e44ad',
                'ordre' => 12,
            ],
        ];

        foreach ($groups as $group) {
            PermissionGroup::updateOrCreate(
                ['code' => $group['code']],
                [
                    'uuid_permission_group' => (string) Str::uuid(),
                    'libelle' => $group['libelle'],
                    'description' => $group['description'],
                    'icone' => $group['icone'],
                    'color' => $group['color'],
                    'ordre_affichage' => $group['ordre'],
                    'status' => 'actif',
                    'created_by' => null,
                    'updated_by' => null,
                    'deleted_by' => null,
                ]
            );
        }
    }

    /**
     * Créer toutes les permissions
     */
    // private function createPermissions(): void
    // {
    //     $permissionsByGroup = [
    //         'users' => [
    //             ['action' => 'afficher', 'libelle' => 'Afficher les utilisateurs'],
    //             ['action' => 'creer', 'libelle' => 'Créer un utilisateur'],
    //             ['action' => 'modifier', 'libelle' => 'Modifier un utilisateur'],
    //             ['action' => 'supprimer', 'libelle' => 'Supprimer un utilisateur'],
    //             ['action' => 'bloquer', 'libelle' => 'Bloquer/Débloquer un utilisateur'],
    //         ],
    //         'roles' => [
    //             ['action' => 'afficher', 'libelle' => 'Afficher les rôles'],
    //             ['action' => 'creer', 'libelle' => 'Créer un rôle'],
    //             ['action' => 'modifier', 'libelle' => 'Modifier un rôle'],
    //             ['action' => 'supprimer', 'libelle' => 'Supprimer un rôle'],
    //             ['action' => 'gerer_permissions', 'libelle' => 'Gérer les permissions des rôles'],
    //         ],
    //         'permissions' => [
    //             ['action' => 'afficher', 'libelle' => 'Afficher les permissions'],
    //             ['action' => 'creer', 'libelle' => 'Créer une permission'],
    //             ['action' => 'modifier', 'libelle' => 'Modifier une permission'],
    //             ['action' => 'supprimer', 'libelle' => 'Supprimer une permission'],
    //         ],
    //         'permission_groups' => [
    //             ['action' => 'afficher', 'libelle' => 'Afficher les groupes de permissions'],
    //             ['action' => 'creer', 'libelle' => 'Créer un groupe de permissions'],
    //             ['action' => 'modifier', 'libelle' => 'Modifier un groupe de permissions'],
    //             ['action' => 'supprimer', 'libelle' => 'Supprimer un groupe de permissions'],
    //         ],
    //         'ip_restrictions' => [
    //             ['action' => 'afficher', 'libelle' => 'Afficher les restrictions IP'],
    //             ['action' => 'creer', 'libelle' => 'Créer une restriction IP'],
    //             ['action' => 'supprimer', 'libelle' => 'Supprimer une restriction IP'],
    //         ],
    //         'profile' => [
    //             ['action' => 'afficher', 'libelle' => 'Afficher le profil'],
    //             ['action' => 'modifier', 'libelle' => 'Modifier le profil'],
    //         ],
    //         'auth' => [
    //             ['action' => 'sessions', 'libelle' => 'Voir les sessions'],
    //             ['action' => 'devices', 'libelle' => 'Voir les appareils'],
    //             ['action' => 'login_attempts', 'libelle' => 'Voir les tentatives de connexion'],
    //             ['action' => '2fa', 'libelle' => 'Gérer l\'authentification à deux facteurs'],
    //             ['action' => 'change_password', 'libelle' => 'Changer le mot de passe'],
    //         ],
    //     ];

    //     foreach ($permissionsByGroup as $groupCode => $permissions) {
    //         $group = PermissionGroup::where('code', $groupCode)->first();
            
    //         if ($group) {
    //             foreach ($permissions as $permData) {
    //                 $code = $groupCode . '.' . $permData['action'];
                    
    //                 Permission::updateOrCreate(
    //                     ['code' => $code],
    //                     [
    //                         'uuid_permission' => (string) Str::uuid(),
    //                         'permission_group_uuid' => $group->uuid_permission_group,
    //                         'action' => $permData['action'],
    //                         'libelle' => $permData['libelle'],
    //                         'description' => $permData['libelle'],
    //                         'category' => 'crud', // Valeur valide pour l'ENUM
    //                         'is_guard' => false,
    //                         'status' => 'actif',
    //                         'created_by' => null,
    //                         'updated_by' => null,
    //                         'deleted_by' => null,
    //                     ]
    //                 );
    //             }
    //         }
    //     }
    // }

    /**
     * Créer toutes les permissions
     */
    private function createPermissions(): void
    {
        $permissionsByGroup = [
            // ============================================================
            // GROUPES EXISTANTS
            // ============================================================
            'users' => [
                ['action' => 'afficher', 'libelle' => 'Afficher les utilisateurs'],
                ['action' => 'creer', 'libelle' => 'Créer un utilisateur'],
                ['action' => 'modifier', 'libelle' => 'Modifier un utilisateur'],
                ['action' => 'supprimer', 'libelle' => 'Supprimer un utilisateur'],
                ['action' => 'bloquer', 'libelle' => 'Bloquer/Débloquer un utilisateur'],
                ['action' => 'geler', 'libelle' => 'Geler un utilisateur'],
                ['action' => 'degeler', 'libelle' => 'Dégeler un utilisateur'],
            ],
            'roles' => [
                ['action' => 'afficher', 'libelle' => 'Afficher les rôles'],
                ['action' => 'creer', 'libelle' => 'Créer un rôle'],
                ['action' => 'modifier', 'libelle' => 'Modifier un rôle'],
                ['action' => 'supprimer', 'libelle' => 'Supprimer un rôle'],
                ['action' => 'gerer_permissions', 'libelle' => 'Gérer les permissions des rôles'],
            ],
            'permissions' => [
                ['action' => 'afficher', 'libelle' => 'Afficher les permissions'],
                ['action' => 'creer', 'libelle' => 'Créer une permission'],
                ['action' => 'modifier', 'libelle' => 'Modifier une permission'],
                ['action' => 'supprimer', 'libelle' => 'Supprimer une permission'],
            ],
            'permission_groups' => [
                ['action' => 'afficher', 'libelle' => 'Afficher les groupes de permissions'],
                ['action' => 'creer', 'libelle' => 'Créer un groupe de permissions'],
                ['action' => 'modifier', 'libelle' => 'Modifier un groupe de permissions'],
                ['action' => 'supprimer', 'libelle' => 'Supprimer un groupe de permissions'],
            ],
            'ip_restrictions' => [
                ['action' => 'afficher', 'libelle' => 'Afficher les restrictions IP'],
                ['action' => 'creer', 'libelle' => 'Créer une restriction IP'],
                ['action' => 'supprimer', 'libelle' => 'Supprimer une restriction IP'],
            ],
            'profile' => [
                ['action' => 'afficher', 'libelle' => 'Afficher le profil'],
                ['action' => 'modifier', 'libelle' => 'Modifier le profil'],
            ],
            'auth' => [
                ['action' => 'sessions', 'libelle' => 'Voir les sessions'],
                ['action' => 'devices', 'libelle' => 'Voir les appareils'],
                ['action' => 'login_attempts', 'libelle' => 'Voir les tentatives de connexion'],
                ['action' => '2fa', 'libelle' => 'Gérer l\'authentification à deux facteurs'],
                ['action' => 'change_password', 'libelle' => 'Changer le mot de passe'],
            ],

            // ============================================================
            // NOUVEAUX GROUPES
            // ============================================================
            'partners' => [
                ['action' => 'afficher', 'libelle' => 'Afficher les partenaires'],
                ['action' => 'creer', 'libelle' => 'Créer un partenaire'],
                ['action' => 'modifier', 'libelle' => 'Modifier un partenaire'],
                ['action' => 'supprimer', 'libelle' => 'Supprimer un partenaire'],
            ],
            'reseaux' => [
                ['action' => 'afficher', 'libelle' => 'Afficher les réseaux'],
                ['action' => 'creer', 'libelle' => 'Créer un réseau'],
                ['action' => 'modifier', 'libelle' => 'Modifier un réseau'],
                ['action' => 'supprimer', 'libelle' => 'Supprimer un réseau'],
            ],
            'agences' => [
                ['action' => 'afficher', 'libelle' => 'Afficher les agences'],
                ['action' => 'creer', 'libelle' => 'Créer une agence'],
                ['action' => 'modifier', 'libelle' => 'Modifier une agence'],
                ['action' => 'supprimer', 'libelle' => 'Supprimer une agence'],
                ['action' => 'assigner_utilisateurs', 'libelle' => 'Assigner/Retirer des utilisateurs à une agence'],
            ],
            'audit' => [
                ['action' => 'consulter_les_logs', 'libelle' => 'Consulter les logs d\'activité'],
                ['action' => 'exporter_les_logs', 'libelle' => 'Exporter les logs'],
                ['action' => 'voir_statistiques', 'libelle' => 'Voir les statistiques d\'activité'],
            ],
            'security_questions' => [
                ['action' => 'gerer', 'libelle' => 'Gérer les questions de sécurité'],
                ['action' => 'afficher', 'libelle' => 'Afficher les questions de sécurité'],
            ],
        ];

        foreach ($permissionsByGroup as $groupCode => $permissions) {
            $group = PermissionGroup::where('code', $groupCode)->first();

            if ($group) {
                foreach ($permissions as $permData) {
                    $code = $groupCode . '.' . $permData['action'];

                    Permission::updateOrCreate(
                        ['code' => $code],
                        [
                            'uuid_permission' => (string) Str::uuid(),
                            'permission_group_uuid' => $group->uuid_permission_group,
                            'action' => $permData['action'],
                            'libelle' => $permData['libelle'],
                            'description' => $permData['libelle'],
                            'category' => 'crud',
                            'is_guard' => false,
                            'status' => 'actif',
                            'created_by' => null,
                            'updated_by' => null,
                            'deleted_by' => null,
                        ]
                    );
                }
            }
        }
    }

    /**
     * Assigner toutes les permissions au rôle Super Admin
     */
    private function assignAllPermissionsToRole(Role $role): void
    {
        // Récupérer toutes les permissions actives
        $permissions = Permission::where('status', 'actif')->get();

        $assignedCount = 0;

        foreach ($permissions as $permission) {
            // Vérifier si la permission est déjà assignée
            $exists = RolePermission::where('role_uuid', $role->uuid_role)
                ->where('permission_uuid', $permission->uuid_permission)
                ->exists();

            if (!$exists) {
                RolePermission::create([
                    'uuid_role_permission' => (string) Str::uuid(),
                    'role_uuid' => $role->uuid_role,
                    'permission_uuid' => $permission->uuid_permission,
                    'granted_by' => null,
                    'granted_at' => now(),
                    'expires_at' => null,
                    'metadata' => [
                        'assigned_by_seeder' => true,
                        'assigned_at' => now()->toDateTimeString(),
                    ],
                ]);
                $assignedCount++;
            }
        }

        $this->command->info("📋 {$assignedCount} permissions assignées au rôle Super Admin.");
    }
}
