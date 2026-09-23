<?php
namespace Database\Seeders;

use App\Models\Api\Ynov\parameter\Permission;
use App\Models\Api\Ynov\parameter\Role;
use App\Models\Api\Ynov\parameter\RolePermission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AssignGestionnairePrestationPermissionsSeeder extends Seeder
{
    private const PERMISSIONS = [
        'prestations.afficher',
        'prestations.creer',
        'prestations.modifier',

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
        // NOTIFICATIONS - Consultation des notifications
        // ============================================================
        'notifications.afficher',
    ];

    public function run(): void
    {
        $role = Role::where('code', 'gestionnaire_prestation')->first();

        if (!$role) {
            $this->command->warn('⚠️  Le rôle "gestionnaire_prestation" n\'existe pas. Exécutez d\'abord le seeder des rôles.');
            return;
        }

        $allPermissions = Permission::where('status', 'actif')->get();
        $allowed = $allPermissions->filter(fn($p) => in_array($p->code, self::PERMISSIONS));

        $this->assignPermissionsToRole($role, $allowed);
    }

    private function assignPermissionsToRole(Role $role, $permissions): void
    {
        $assigned = 0;
        $skipped = 0;

        foreach ($permissions as $permission) {
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
                        'role_type' => 'gestionnaire_prestation',
                    ],
                ]);
                $assigned++;
            } else {
                $skipped++;
            }
        }

        $this->command->info("✅ {$assigned} permissions assignées pour le rôle gestionnaire_prestation");
        if ($skipped > 0) {
            $this->command->info("⏭️  {$skipped} permissions déjà existantes (ignorées)");
        }
    }
}
