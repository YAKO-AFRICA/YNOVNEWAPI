<?php
namespace App\Services\Api\Ynov;

use App\Models\Api\Ynov\parameter\PermissionGroup;
use Illuminate\Support\Str;

class PermissionGroupService
{
    public function create(array $data, string $creatorUuid): PermissionGroup
    {
        return PermissionGroup::create([
            'uuid_permission_group' => (string) Str::uuid(),
            'code' => Str::slug($data['libelle'], '_'),
            'libelle' => $data['libelle'],
            'description' => $data['description'] ?? null,
            'icone' => $data['icone'] ?? null,
            'color' => $data['color'] ?? null,
            'ordre_affichage' => $data['ordre_affichage'] ?? 0,
            'parent_uuid' => $data['parent_uuid'] ?? null,
            'route_prefix' => $data['route_prefix'] ?? null,
            'created_by' => $creatorUuid,
        ]);
    }

    public function update(PermissionGroup $group, array $data, string $updaterUuid): PermissionGroup
    {
        $group->update([
            'libelle' => $data['libelle'] ?? $group->libelle,
            'description' => $data['description'] ?? $group->description,
            'icone' => $data['icone'] ?? $group->icone,
            'color' => $data['color'] ?? $group->color,
            'ordre_affichage' => $data['ordre_affichage'] ?? $group->ordre_affichage,
            'parent_uuid' => $data['parent_uuid'] ?? $group->parent_uuid,
            'route_prefix' => $data['route_prefix'] ?? $group->route_prefix,
            'updated_by' => $updaterUuid,
        ]);
        return $group->fresh();
    }
}