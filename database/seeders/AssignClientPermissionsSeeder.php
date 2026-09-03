<?php
// database/seeders/AssignClientPermissionsSeeder.php

namespace Database\Seeders;

use App\Models\Api\Ynov\parameter\Permission;
use App\Models\Api\Ynov\parameter\Role;
use App\Models\Api\Ynov\parameter\RolePermission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AssignClientPermissionsSeeder extends Seeder
{
    /**
     * Liste des permissions à assigner au rôle client
     */
    private const CLIENT_PERMISSIONS = [
        // ============================================================
        // AUTHENTIFICATION - Sécurité de base
        // ============================================================
        'auth.change_password',
        'auth.sessions',
        'auth.devices',
        'auth.login_attempts',
        'auth.2fa',
        
        // ============================================================
        // PROFIL - Gestion du profil
        // ============================================================
        'profile.afficher',
        'profile.modifier',
        
        // ============================================================
        // ESPACE CLIENT - Tableau de bord et contrats
        // ============================================================
        'espace_client.dashboard',
        'espace_client.statistiques',
        'espace_client.liste_contrats',
        'espace_client.details_contrat',
        'espace_client.etat_cotisation',
        'espace_client.contrats_factures_impayees',
        'espace_client.ajouter_contrat',

        // 'agences.afficher',
        
        // ============================================================
        // RENDEZ-VOUS - Création et gestion des rendez-vous
        // ============================================================
        'rdvs.creer',
        'rdvs.afficher',
        'rdvs.annuler',
        
        // ============================================================
        // NOTIFICATIONS - Consultation des notifications
        // ============================================================
        'notifications.afficher',
        
        // ============================================================
        // GROUPES DE NOTIFICATION - Consultation des groupes
        // ============================================================
        // 'group_notifs.afficher',

        
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $clientRole = Role::where('code', 'client')->first();
        
        if (!$clientRole) {
            $this->command->warn('⚠️  Le rôle "client" n\'existe pas. Veuillez exécuter le seeder des rôles d\'abord.');
            return;
        }

        $this->command->info('🔍 Recherche des permissions pour le rôle client...');
        
        // Récupérer toutes les permissions
        $allPermissions = Permission::where('status', 'actif')->get();
        
        if ($allPermissions->isEmpty()) {
            $this->command->warn('⚠️  Aucune permission trouvée.');
            return;
        }

        // Filtrer les permissions autorisées pour le client
        $allowedPermissions = $allPermissions->filter(function ($permission) {
            return in_array($permission->code, self::CLIENT_PERMISSIONS);
        });

        $this->command->info("📋 {$allowedPermissions->count()} permissions autorisées pour le client sur {$allPermissions->count()} total.");

        $this->assignPermissionsToRole($clientRole, $allowedPermissions);
    }

    /**
     * Assigner les permissions au rôle client
     */
    private function assignPermissionsToRole(Role $role, $permissions): void
    {
        $assignedCount = 0;
        $skippedCount = 0;
        $notFound = [];
        $errors = [];

        $this->command->info('🔄 Assignation des permissions en cours...');

        foreach ($permissions as $permission) {
            try {
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
                            'role_type' => 'client',
                        ],
                    ]);
                    $assignedCount++;
                } else {
                    $skippedCount++;
                }
            } catch (\Exception $e) {
                $errors[] = [
                    'permission' => $permission->code,
                    'error' => $e->getMessage(),
                ];
            }
        }

        // Afficher les permissions non trouvées
        foreach (self::CLIENT_PERMISSIONS as $code) {
            if (!$permissions->contains('code', $code)) {
                $notFound[] = $code;
            }
        }

        // Afficher le résumé
        $this->command->newLine();
        $this->command->info('📊 Résumé de l\'assignation :');
        $this->command->info("  ✅ {$assignedCount} permissions assignées");
        
        if ($skippedCount > 0) {
            $this->command->info("  ⏭️  {$skippedCount} permissions déjà existantes (ignorées)");
        }
        
        if (!empty($notFound)) {
            $this->command->warn('  ⚠️  Permissions non trouvées :');
            foreach ($notFound as $code) {
                $this->command->warn("     - {$code}");
            }
        }

        if (!empty($errors)) {
            $this->command->error('  ❌ Erreurs rencontrées :');
            foreach ($errors as $error) {
                $this->command->error("     - {$error['permission']}: {$error['error']}");
            }
        }

        if ($assignedCount > 0) {
            $this->command->info('✅ Assignation des permissions client terminée avec succès !');
        } else {
            $this->command->info('ℹ️  Aucune nouvelle permission à assigner pour le rôle client.');
        }
    }
}