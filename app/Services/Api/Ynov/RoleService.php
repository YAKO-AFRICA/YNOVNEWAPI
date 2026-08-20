<?php
namespace App\Services\Api\Ynov;


use App\Models\Api\Ynov\parameter\Role;
use Illuminate\Support\Str;

class RoleService
{
    public function create(array $data, string $creatorUuid)
    {
        return Role::create([
            // 'uuid_role' => (string) Str::uuid(),
            // 'code' => Str::slug($data['libelle'], '_'),
            'libelle' => $data['libelle'],
            'description' => $data['description'] ?? null,
            'is_system' => false,
            'level' => $data['level'] ?? 1,
            'priority' => $data['priority'] ?? 0,
            'created_by' => $creatorUuid,
        ]);
    }

    public function update(Role $role, array $data, string $updaterUuid)
    {
        $role->update([
            'libelle' => $data['libelle'] ?? $role->libelle,
            'description' => $data['description'] ?? $role->description,
            'level' => $data['level'] ?? $role->level,
            'priority' => $data['priority'] ?? $role->priority,
            'updated_by' => $updaterUuid,
        ]);
        return $role->fresh();
    }

    public function assignPermissions(Role $role, array $permissionUuids, string $granterUuid): void
    {
        $syncData = collect($permissionUuids)->mapWithKeys(fn ($uuid) => [
            $uuid => [
                'uuid_role_permission' => (string) Str::uuid(),
                'granted_by' => $granterUuid,
                'granted_at' => now(),
            ],
        ])->toArray();

        $role->permissions()->sync($syncData);
    }
}