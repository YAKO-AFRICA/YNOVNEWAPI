<?php
// database/seeders/PermissionSeeder.php

namespace Database\Seeders;

use App\Models\Api\Ynov\parameter\Permission;
use App\Models\Api\Ynov\parameter\PermissionGroup;
use App\Services\Api\Ynov\PermissionService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissionService = app(PermissionService::class);
        $suggestedActions = $permissionService->suggestedActions();

        $this->command->info('Début de la création des permissions...');

        foreach ($suggestedActions as $moduleData) {
            // Créer ou récupérer le groupe de permissions
            $group = PermissionGroup::firstOrCreate(
                ['code' => $moduleData['module']['code']],
                [
                    'uuid_permission_group' => (string) Str::uuid(),
                    'libelle' => $moduleData['module']['libelle'],
                    'description' => $moduleData['module']['description'],
                    'icone' => $moduleData['module']['icone'],
                    'color' => $moduleData['module']['color'],
                    'ordre_affichage' => $moduleData['module']['ordre'],
                    'status' => 'actif',
                ]
            );

            $this->command->info("  - Groupe: {$group->libelle}");

            // Créer les permissions associées
            foreach ($moduleData['permissions'] as $permData) {
                $code = $group->code . '.' . $permData['action'];

                Permission::firstOrCreate(
                    ['code' => $code],
                    [
                        'uuid_permission' => (string) Str::uuid(),
                        'permission_group_uuid' => $group->uuid_permission_group,
                        'action' => $permData['action'],
                        'libelle' => $permData['libelle'],
                        'description' => $permData['description'] ?? $permData['libelle'],
                        'category' => $permData['category'] ?? 'crud',
                        'is_guard' => false,
                        'status' => 'actif',
                    ]
                );

                $this->command->info("      - Permission: {$code}");
            }
        }

        $this->command->info('Permissions créées avec succès.');
    }
}