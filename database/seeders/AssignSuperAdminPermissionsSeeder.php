<?php
// database/seeders/AssignSuperAdminPermissionsSeeder.php

namespace Database\Seeders;

use App\Models\Api\Ynov\parameter\Permission;
use App\Models\Api\Ynov\parameter\Role;
use App\Models\Api\Ynov\parameter\RolePermission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AssignSuperAdminPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $superAdmin = Role::where('code', 'super_admin')->first();
        
        if (!$superAdmin) {
            $this->command->warn('⚠️  Super Admin role not found. Please run SuperAdminRoleSeeder first.');
            return;
        }

        $this->command->info('🔍 Recherche des permissions disponibles...');
        
        $permissions = Permission::where('status', 'actif')->get();
        
        if ($permissions->isEmpty()) {
            $this->command->warn('⚠️  No permissions found. Please run PermissionSeeder first.');
            return;
        }

        $this->command->info("📋 {$permissions->count()} permissions trouvées.");

        $this->assignAllPermissionsToRole($superAdmin, $permissions);
    }

    /**
     * Assigner toutes les permissions au rôle Super Admin
     */
    private function assignAllPermissionsToRole(Role $role, $permissions): void
    {
        $assignedCount = 0;
        $skippedCount = 0;
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
                        ],
                    ]);
                    $assignedCount++;
                    
                    // Afficher une progression
                    if ($assignedCount % 10 === 0) {
                        $this->command->info("  - {$assignedCount} permissions assignées...");
                    }
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

        // Afficher le résumé
        $this->command->newLine();
        $this->command->info('📊 Résumé de l\'assignation :');
        $this->command->info("  ✅ {$assignedCount} permissions assignées");
        
        if ($skippedCount > 0) {
            $this->command->info("  ⏭️  {$skippedCount} permissions déjà existantes (ignorées)");
        }
        
        if (!empty($errors)) {
            $this->command->error('  ❌ Erreurs rencontrées :');
            foreach ($errors as $error) {
                $this->command->error("     - {$error['permission']}: {$error['error']}");
            }
        }

        if ($assignedCount > 0) {
            $this->command->info('✅ Assignation terminée avec succès !');
        } else {
            $this->command->info('ℹ️  Aucune nouvelle permission à assigner.');
        }
    }
}