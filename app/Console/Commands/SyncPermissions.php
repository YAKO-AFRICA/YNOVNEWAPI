<?php
// app/Console/Commands/SyncPermissions.php

namespace App\Console\Commands;

use App\Models\Api\Ynov\parameter\Permission;
use App\Models\Api\Ynov\parameter\PermissionGroup;
use App\Models\Api\Ynov\parameter\Role;
use App\Models\Api\Ynov\parameter\RolePermission;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SyncPermissions extends Command
{
    protected $signature = 'permissions:sync 
                            {--force : Forcer la synchronisation et mettre à jour les groupes existants}
                            {--assign : Assigner automatiquement les nouvelles permissions au rôle Super Admin}
                            {--clean : Supprimer les permissions orphelines (non définies dans la config)}';
    
    protected $description = 'Synchroniser les permissions avec les groupes existants';

    /**
     * Configuration des permissions par défaut
     */
    private array $defaultPermissions = [
        'users' => [
            'actions' => ['afficher', 'creer', 'modifier', 'supprimer', 'bloquer'],
            'libelle' => 'Utilisateurs',
            'description' => 'Gestion des utilisateurs',
            'icone' => 'users',
            'color' => '#3490dc',
            'route_prefix' => 'users',
        ],
        'roles' => [
            'actions' => ['afficher', 'creer', 'modifier', 'supprimer', 'gerer_permissions'],
            'libelle' => 'Rôles',
            'description' => 'Gestion des rôles',
            'icone' => 'shield-alt',
            'color' => '#e67e22',
            'route_prefix' => 'roles',
        ],
        'permissions' => [
            'actions' => ['afficher', 'creer', 'modifier', 'supprimer'],
            'libelle' => 'Permissions',
            'description' => 'Gestion des permissions',
            'icone' => 'key',
            'color' => '#2ecc71',
            'route_prefix' => 'permissions',
        ],
        'permission_groups' => [
            'actions' => ['afficher', 'creer', 'modifier', 'supprimer'],
            'libelle' => 'Groupes de permissions',
            'description' => 'Gestion des groupes de permissions',
            'icone' => 'folder',
            'color' => '#f39c12',
            'route_prefix' => 'permission-groups',
        ],
        'ip_restrictions' => [
            'actions' => ['afficher', 'creer', 'supprimer'],
            'libelle' => 'Restrictions IP',
            'description' => 'Gestion des restrictions d\'IP',
            'icone' => 'ip',
            'color' => '#e74c3c',
            'route_prefix' => 'ip-restrictions',
        ],
        'profile' => [
            'actions' => ['afficher', 'modifier'],
            'libelle' => 'Profil',
            'description' => 'Gestion du profil utilisateur',
            'icone' => 'user',
            'color' => '#3490dc',
            'route_prefix' => 'profile',
        ],
        'auth' => [
            'actions' => ['sessions', 'devices', 'login_attempts', '2fa', 'change_password'],
            'libelle' => 'Authentification',
            'description' => 'Gestion de l\'authentification',
            'icone' => 'lock',
            'color' => '#9b59b6',
            'route_prefix' => 'auth',
        ],
    ];

    public function handle(): int
    {
        $this->info('🚀 Synchronisation des permissions...');
        $this->newLine();

        // Vérifier si la table existe
        // if (!$this->checkTables()) {
        //     return 1;
        // }

        $stats = [
            'groups_created' => 0,
            'groups_updated' => 0,
            'permissions_created' => 0,
            'permissions_updated' => 0,
            'permissions_deleted' => 0,
            'permissions_assigned' => 0,
        ];

        $allExistingPermissions = [];

        // Traiter chaque groupe
        foreach ($this->defaultPermissions as $groupCode => $config) {
            $this->info("📦 Traitement du groupe: {$groupCode}");
            
            // Créer ou mettre à jour le groupe
            $group = $this->syncGroup($groupCode, $config, $stats);
            
            if (!$group) {
                $this->error("❌ Impossible de créer le groupe {$groupCode}");
                continue;
            }

            // Créer les permissions du groupe
            $groupPermissions = $this->syncGroupPermissions($group, $config['actions'], $stats);
            $allExistingPermissions = array_merge($allExistingPermissions, $groupPermissions);

            $this->line("   ✅ Groupe traité avec succès");
            $this->newLine();
        }

        // Nettoyer les permissions orphelines
        if ($this->option('clean')) {
            $this->cleanOrphanPermissions($allExistingPermissions, $stats);
        }

        // Assigner les permissions au Super Admin
        if ($this->option('assign')) {
            $this->assignPermissionsToSuperAdmin($stats);
        }

        // Vider le cache des permissions
        $this->clearPermissionCache();

        // Afficher le résumé
        $this->displaySummary($stats);

        return 0;
    }

    /**
     * Vérifier l'existence des tables
     */
    // private function checkTables(): bool
    // {
    //     try {
    //         if (!Schema::hasTable('permission_groups')) {
    //             $this->error('❌ La table permission_groups n\'existe pas.');
    //             $this->warn('⚠️  Veuillez exécuter les migrations d\'abord: php artisan migrate');
    //             return false;
    //         }

    //         if (!Schema::hasTable('permissions')) {
    //             $this->error('❌ La table permissions n\'existe pas.');
    //             return false;
    //         }

    //         return true;
    //     } catch (\Exception $e) {
    //         $this->error('❌ Erreur lors de la vérification des tables: ' . $e->getMessage());
    //         return false;
    //     }
    // }

    /**
     * Synchroniser un groupe de permissions
     */
    private function syncGroup(string $groupCode, array $config, array &$stats): ?PermissionGroup
    {
        try {
            $group = PermissionGroup::where('code', $groupCode)->first();

            $data = [
                'libelle' => $config['libelle'],
                'description' => $config['description'],
                'icone' => $config['icone'],
                'color' => $config['color'],
                'route_prefix' => $config['route_prefix'] ?? null,
                'ordre_affichage' => array_search($groupCode, array_keys($this->defaultPermissions)) + 1,
                'status' => 'actif',
            ];

            if (!$group) {
                $data['uuid_permission_group'] = (string) Str::uuid();
                $data['code'] = $groupCode;
                
                $group = PermissionGroup::create($data);
                $stats['groups_created']++;
                $this->line("   ✅ Groupe créé: {$groupCode}");
            } elseif ($this->option('force')) {
                $group->update($data);
                $stats['groups_updated']++;
                $this->line("   🔄 Groupe mis à jour: {$groupCode}");
            } else {
                $this->line("   ℹ️  Groupe existant: {$groupCode} (utilisez --force pour mettre à jour)");
            }

            return $group;

        } catch (\Exception $e) {
            $this->error("   ❌ Erreur lors de la synchronisation du groupe {$groupCode}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Synchroniser les permissions d'un groupe
     */
    private function syncGroupPermissions(PermissionGroup $group, array $actions, array &$stats): array
    {
        $permissionCodes = [];

        foreach ($actions as $action) {
            $code = $group->code . '.' . $action;
            $permissionCodes[] = $code;

            try {
                $permission = Permission::where('code', $code)->first();

                $data = [
                    'permission_group_uuid' => $group->uuid_permission_group,
                    'action' => $action,
                    'libelle' => $action . ' - ' . $group->libelle,
                    'description' => $action . ' - ' . $group->libelle,
                    'category' => 'crud', // Valeur valide pour l'ENUM
                    'status' => 'actif',
                ];

                if (!$permission) {
                    $data['uuid_permission'] = (string) Str::uuid();
                    $data['code'] = $code;
                    
                    Permission::create($data);
                    $stats['permissions_created']++;
                    $this->line("      ✅ Permission créée: {$code}");
                } else {
                    // Mettre à jour seulement si nécessaire
                    if ($this->option('force') || $permission->status !== 'actif') {
                        $permission->update($data);
                        $stats['permissions_updated']++;
                        $this->line("      🔄 Permission mise à jour: {$code}");
                    } else {
                        // S'assurer que la permission est active
                        if ($permission->status !== 'actif') {
                            $permission->update(['status' => 'actif']);
                            $stats['permissions_updated']++;
                            $this->line("      🔄 Permission activée: {$code}");
                        }
                    }
                }

            } catch (\Exception $e) {
                $this->error("      ❌ Erreur pour la permission {$code}: " . $e->getMessage());
            }
        }

        return $permissionCodes;
    }

    /**
     * Nettoyer les permissions orphelines
     */
    private function cleanOrphanPermissions(array $validPermissions, array &$stats): void
    {
        $this->info('🧹 Nettoyage des permissions orphelines...');

        try {
            $orphans = Permission::whereNotIn('code', $validPermissions)->get();

            if ($orphans->isEmpty()) {
                $this->line('   ℹ️  Aucune permission orpheline trouvée.');
                return;
            }

            $this->warn("   ⚠️  {$orphans->count()} permissions orphelines trouvées.");

            if ($this->confirm('Voulez-vous supprimer les permissions orphelines ?', false)) {
                foreach ($orphans as $orphan) {
                    // Vérifier si la permission est utilisée
                    if ($orphan->roles()->count() > 0) {
                        $this->warn("      ⚠️  Permission {$orphan->code} utilisée par des rôles, ignorée.");
                        continue;
                    }

                    $orphan->delete();
                    $stats['permissions_deleted']++;
                    $this->line("      🗑️  Permission supprimée: {$orphan->code}");
                }
            } else {
                $this->line('   ℹ️  Nettoyage annulé.');
            }

        } catch (\Exception $e) {
            $this->error('   ❌ Erreur lors du nettoyage: ' . $e->getMessage());
        }
    }

    /**
     * Assigner les permissions au rôle Super Admin
     */
    private function assignPermissionsToSuperAdmin(array &$stats): void
    {
        $this->info('🔗 Assignation des permissions au Super Admin...');

        try {
            $superAdminRole = Role::where('code', 'super_admin')->first();

            if (!$superAdminRole) {
                $this->warn('⚠️  Rôle Super Admin non trouvé. Création...');
                
                $superAdminRole = Role::create([
                    'uuid_role' => (string) Str::uuid(),
                    'code' => 'super_admin',
                    'libelle' => 'Super Administrateur',
                    'description' => 'Rôle disposant de tous les droits sur la plateforme.',
                    'is_system' => true,
                    'is_super_admin' => true,
                    'level' => 1,
                    'priority' => 0,
                    'status' => 'actif',
                ]);
                
                $this->line('   ✅ Rôle Super Admin créé');
            }

            // Récupérer toutes les permissions actives
            $permissions = Permission::where('status', 'actif')->get();
            $assignedCount = 0;

            foreach ($permissions as $permission) {
                $exists = RolePermission::where('role_uuid', $superAdminRole->uuid_role)
                                        ->where('permission_uuid', $permission->uuid_permission)
                                        ->exists();

                if (!$exists) {
                    RolePermission::create([
                        'uuid_role_permission' => (string) Str::uuid(),
                        'role_uuid' => $superAdminRole->uuid_role,
                        'permission_uuid' => $permission->uuid_permission,
                        'granted_at' => now(),
                        'metadata' => [
                            'assigned_by_sync' => true,
                            'assigned_at' => now()->toDateTimeString(),
                        ],
                    ]);
                    $assignedCount++;
                }
            }

            $stats['permissions_assigned'] = $assignedCount;
            $this->line("   ✅ {$assignedCount} permissions assignées au rôle Super Admin");

        } catch (\Exception $e) {
            $this->error('   ❌ Erreur lors de l\'assignation: ' . $e->getMessage());
        }
    }

    /**
     * Vider le cache des permissions
     */
    private function clearPermissionCache(): void
    {
        $this->info('🗑️  Nettoyage du cache des permissions...');

        try {
            // Vider le cache des permissions utilisateur
            $cachePrefix = 'user_permissions_';
            $keys = Cache::getStore()->getPrefix() . $cachePrefix . '*';
            
            // Cette méthode peut varier selon le driver de cache
            if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
                Cache::getStore()->getRedis()->del($keys);
            } else {
                // Pour les autres drivers, on efface tout le cache lié aux permissions
                Cache::flush();
            }

            $this->line('   ✅ Cache des permissions vidé');

        } catch (\Exception $e) {
            $this->warn('   ⚠️  Impossible de vider le cache: ' . $e->getMessage());
        }
    }

    /**
     * Afficher le résumé de la synchronisation
     */
    private function displaySummary(array $stats): void
    {
        $this->newLine();
        $this->info('📊 RÉSUMÉ DE LA SYNCHRONISATION');
        $this->line(str_repeat('─', 40));

        $this->line(sprintf('   📦 Groupes créés   : %d', $stats['groups_created']));
        $this->line(sprintf('   🔄 Groupes mis à jour : %d', $stats['groups_updated']));
        $this->line(sprintf('   ✅ Permissions créées : %d', $stats['permissions_created']));
        $this->line(sprintf('   🔄 Permissions mises à jour : %d', $stats['permissions_updated']));
        
        if ($this->option('clean')) {
            $this->line(sprintf('   🗑️  Permissions supprimées : %d', $stats['permissions_deleted']));
        }
        
        if ($this->option('assign')) {
            $this->line(sprintf('   🔗 Permissions assignées : %d', $stats['permissions_assigned']));
        }

        $this->line(str_repeat('─', 40));

        $total = $stats['groups_created'] + $stats['groups_updated'] +
                 $stats['permissions_created'] + $stats['permissions_updated'] +
                 $stats['permissions_deleted'] + $stats['permissions_assigned'];

        if ($total === 0) {
            $this->info('✅ Aucune modification nécessaire. Tout est à jour !');
        } else {
            $this->info('✅ Synchronisation terminée avec succès !');
        }

        $this->newLine();
    }
}