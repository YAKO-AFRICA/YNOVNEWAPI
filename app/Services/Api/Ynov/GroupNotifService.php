<?php
// app/Services/Api/Ynov/GroupNotifService.php
namespace App\Services\Api\Ynov;

use App\Models\Api\Ynov\parameter\GroupNotif;
use App\Models\Api\Ynov\parameter\UserGroupNotif;
use App\Models\Api\Ynov\parameter\ActivityLog;
use App\Models\Api\Ynov\parameter\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GroupNotifService
{
    /**
     * Créer un groupe de notification
     */
    public function create(array $data, string $creatorUuid): GroupNotif
    {
        return DB::transaction(function () use ($data, $creatorUuid) {
            $group = GroupNotif::create([
                'uuid_group_notif' => (string) Str::uuid(),
                'code' => $data['code'] ?? Str::slug($data['libelle'], '_'),
                'libelle' => $data['libelle'],
                'description' => $data['description'] ?? null,
                'channels' => $data['channels'] ?? ['database'],
                'preferences' => $data['preferences'] ?? null,
                'status' => $data['status'] ?? 'actif',
                'created_by' => $creatorUuid,
            ]);

            ActivityLog::log([
                'user_uuid' => $creatorUuid,
                'action' => 'create',
                'action_type' => 'crud',
                'module' => 'group_notifs',
                'description' => "Création du groupe de notification : {$group->libelle}",
                'resource_type' => 'group_notif',
                'resource_id' => $group->uuid_group_notif,
                'new_values' => $group->toArray(),
                'level' => 'info',
            ]);

            return $group;
        });
    }

    /**
     * Mettre à jour un groupe de notification
     */
    public function update(GroupNotif $group, array $data, string $updaterUuid): GroupNotif
    {
        return DB::transaction(function () use ($group, $data, $updaterUuid) {
            $oldValues = $group->toArray();

            $group->update([
                'libelle' => $data['libelle'] ?? $group->libelle,
                'code' => isset($data['libelle']) ? Str::slug($data['libelle'], '_') : $group->code,
                'description' => $data['description'] ?? $group->description,
                'channels' => $data['channels'] ?? $group->channels,
                'preferences' => $data['preferences'] ?? $group->preferences,
                'status' => $data['status'] ?? $group->status,
                'updated_by' => $updaterUuid,
            ]);

            ActivityLog::log([
                'user_uuid' => $updaterUuid,
                'action' => 'update',
                'action_type' => 'crud',
                'module' => 'group_notifs',
                'description' => "Mise à jour du groupe de notification : {$group->libelle}",
                'resource_type' => 'group_notif',
                'resource_id' => $group->uuid_group_notif,
                'old_values' => $oldValues,
                'new_values' => $group->toArray(),
                'level' => 'info',
            ]);

            return $group->fresh();
        });
    }

    /**
     * Supprimer un groupe de notification (soft delete)
     */
    public function delete(GroupNotif $group, string $deleterUuid): void
    {
        // Vérifier si le groupe a des utilisateurs
        if ($group->users()->count() > 0) {
            throw ValidationException::withMessages([
                'group' => ['Ce groupe contient des utilisateurs et ne peut pas être supprimé.']
            ]);
        }

        $group->update([
            'status' => 'inactif',
            'deleted_by' => $deleterUuid,
        ]);
        
        $group->delete();

        ActivityLog::log([
            'user_uuid' => $deleterUuid,
            'action' => 'delete',
            'action_type' => 'crud',
            'module' => 'group_notifs',
            'description' => "Suppression du groupe de notification : {$group->libelle}",
            'resource_type' => 'group_notif',
            'resource_id' => $group->uuid_group_notif,
            'level' => 'warning',
        ]);
    }

    /**
     * Assigner des utilisateurs à un groupe
     */
    public function assignUsers(GroupNotif $group, array $userUuids, string $assignerUuid): array
    {
        return DB::transaction(function () use ($group, $userUuids, $assignerUuid) {
            $assigned = [];
            
            foreach ($userUuids as $userUuid) {
                $user = User::where('uuid_user', $userUuid)->first();
                if (!$user) {
                    continue;
                }

                $existing = UserGroupNotif::where('user_uuid', $userUuid)
                    ->where('group_notif_uuid', $group->uuid_group_notif)
                    ->first();

                if (!$existing) {
                    $pivot = UserGroupNotif::create([
                        'uuid_user_group_notif' => (string) Str::uuid(),
                        'user_uuid' => $userUuid,
                        'group_notif_uuid' => $group->uuid_group_notif,
                        'is_primary' => false,
                        'is_active' => true,
                        'assigned_at' => now(),
                        'assigned_by' => $assignerUuid,
                    ]);
                    $assigned[] = $userUuid;
                }
            }

            ActivityLog::log([
                'user_uuid' => $assignerUuid,
                'action' => 'assign_users',
                'action_type' => 'crud',
                'module' => 'group_notifs',
                'description' => "Assignation d'utilisateurs au groupe : {$group->libelle}",
                'resource_type' => 'group_notif',
                'resource_id' => $group->uuid_group_notif,
                'metadata' => ['assigned_count' => count($assigned)],
                'level' => 'info',
            ]);

            return $assigned;
        });
    }

    /**
     * Retirer un utilisateur d'un groupe
     */
    public function removeUser(GroupNotif $group, string $userUuid, string $removerUuid): bool
    {
        return DB::transaction(function () use ($group, $userUuid, $removerUuid) {
            $pivot = UserGroupNotif::where('user_uuid', $userUuid)
                ->where('group_notif_uuid', $group->uuid_group_notif)
                ->first();

            if (!$pivot) {
                return false;
            }

            $pivot->delete();

            ActivityLog::log([
                'user_uuid' => $removerUuid,
                'action' => 'remove_user',
                'action_type' => 'crud',
                'module' => 'group_notifs',
                'description' => "Retrait d'un utilisateur du groupe : {$group->libelle}",
                'resource_type' => 'group_notif',
                'resource_id' => $group->uuid_group_notif,
                'metadata' => ['user_uuid' => $userUuid],
                'level' => 'info',
            ]);

            return true;
        });
    }

    /**
     * Définir le groupe principal d'un utilisateur
     */
    public function setPrimaryGroup(User $user, string $groupUuid, string $updaterUuid): bool
    {
        return DB::transaction(function () use ($user, $groupUuid, $updaterUuid) {
            // Vérifier que l'utilisateur appartient au groupe
            $pivot = UserGroupNotif::where('user_uuid', $user->uuid_user)
                ->where('group_notif_uuid', $groupUuid)
                ->first();

            if (!$pivot) {
                return false;
            }

            // Retirer le statut principal des autres groupes
            UserGroupNotif::where('user_uuid', $user->uuid_user)
                ->where('group_notif_uuid', '!=', $groupUuid)
                ->update(['is_primary' => false]);

            // Définir ce groupe comme principal
            $pivot->update(['is_primary' => true]);

            ActivityLog::log([
                'user_uuid' => $updaterUuid,
                'action' => 'set_primary_group',
                'action_type' => 'crud',
                'module' => 'group_notifs',
                'description' => "Définition du groupe principal pour l'utilisateur",
                'resource_type' => 'user',
                'resource_id' => $user->uuid_user,
                'metadata' => ['group_uuid' => $groupUuid],
                'level' => 'info',
            ]);

            return true;
        });
    }

    /**
     * Récupérer les groupes avec leurs compteurs
     */
    public function getGroupsWithCount(array $filters = [], int $perPage = 20)
    {
        $query = GroupNotif::query()->withCount('users');

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['search'])) {
            $query->search($filters['search']);
        }

        if (isset($filters['channel'])) {
            $query->withChannel($filters['channel']);
        }

        return $query->orderBy('libelle')->paginate($perPage);
    }

    /**
     * Récupérer les groupes d'un utilisateur
     */
    public function getUserGroups(string $userUuid): array
    {
        $groups = GroupNotif::whereHas('users', function ($query) use ($userUuid) {
            $query->where('user_uuid', $userUuid);
        })->with(['users' => function ($query) use ($userUuid) {
            $query->where('user_uuid', $userUuid)->withPivot('is_primary', 'is_active');
        }])->get();

        return $groups->map(function ($group) {
            $pivot = $group->users->first()?->pivot;
            return [
                'uuid_group_notif' => $group->uuid_group_notif,
                'code' => $group->code,
                'libelle' => $group->libelle,
                'description' => $group->description,
                'channels' => $group->channels,
                'status' => $group->status,
                'is_primary' => $pivot->is_primary ?? false,
                'is_active' => $pivot->is_active ?? true,
                'assigned_at' => $pivot->assigned_at ?? null,
            ];
        })->toArray();
    }

    /**
     * Récupérer les canaux disponibles
     */
    public function getAvailableChannels(): array
    {
        return [
            ['code' => 'database', 'label' => 'Base de données', 'icon' => 'bi-database'],
            ['code' => 'email', 'label' => 'Email', 'icon' => 'bi-envelope'],
            ['code' => 'sms', 'label' => 'SMS', 'icon' => 'bi-phone'],
            ['code' => 'push', 'label' => 'Push mobile', 'icon' => 'bi-bell'],
            ['code' => 'whatsapp', 'label' => 'WhatsApp', 'icon' => 'bi-whatsapp'],
        ];
    }

    /**
     * Dupliquer un groupe de notification
     */
    public function duplicate(GroupNotif $group, string $creatorUuid): GroupNotif
    {
        return DB::transaction(function () use ($group, $creatorUuid) {
            $newLabel = $group->libelle . ' (copie)';
            $newCode = Str::slug($newLabel, '_');
            
            // Vérifier si le code existe déjà
            $counter = 1;
            while (GroupNotif::where('code', $newCode)->exists()) {
                $newCode = Str::slug($group->libelle, '_') . '_copy_' . $counter;
                $counter++;
            }

            $newGroup = GroupNotif::create([
                'uuid_group_notif' => (string) Str::uuid(),
                'code' => $newCode,
                'libelle' => $newLabel,
                'description' => $group->description,
                'channels' => $group->channels,
                'preferences' => $group->preferences,
                'status' => 'inactif', // Par défaut inactif pour éviter les envois accidentels
                'created_by' => $creatorUuid,
            ]);

            ActivityLog::log([
                'user_uuid' => $creatorUuid,
                'action' => 'duplicate',
                'action_type' => 'crud',
                'module' => 'group_notifs',
                'description' => "Duplication du groupe de notification : {$group->libelle}",
                'resource_type' => 'group_notif',
                'resource_id' => $newGroup->uuid_group_notif,
                'level' => 'info',
            ]);

            return $newGroup;
        });
    }

    /**
     * Obtenir les statistiques des groupes
     */
    public function getStats(): array
    {
        return [
            'total' => GroupNotif::count(),
            'active' => GroupNotif::active()->count(),
            'inactive' => GroupNotif::where('status', 'inactif')->count(),
            'total_users_assigned' => UserGroupNotif::distinct('user_uuid')->count('user_uuid'),
        ];
    }
}