<?php
// app/Services/Api/Ynov/NotificationService.php
namespace App\Services\Api\Ynov;

use App\Models\Api\Ynov\parameter\Notification;
use App\Models\Api\Ynov\parameter\ActivityLog;
use App\Models\Api\Ynov\parameter\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class NotificationService
{
    /**
     * Créer une notification
     */
    public function create(array $data): Notification
    {
        return DB::transaction(function () use ($data) {
            $notification = Notification::create([
                'uuid_notification' => (string) Str::uuid(),
                'user_uuid' => $data['user_uuid'],
                'group_notif_uuid' => $data['group_notif_uuid'] ?? null,
                'title' => $data['title'],
                'body' => $data['body'],
                'type' => $data['type'] ?? 'system',
                'action_url' => $data['action_url'] ?? null,
                'action_label' => $data['action_label'] ?? null,
                'metadata' => $data['metadata'] ?? null,
                'channel' => $data['channel'] ?? 'database',
                'created_by' => $data['created_by'] ?? null,
            ]);

            ActivityLog::log([
                'user_uuid' => $data['user_uuid'],
                'action' => 'notification_created',
                'action_type' => 'notification',
                'module' => 'notifications',
                'description' => "Notification créée : {$data['title']}",
                'resource_type' => 'notification',
                'resource_id' => $notification->uuid_notification,
                'level' => 'info',
            ]);

            return $notification;
        });
    }

    /**
     * Créer une notification pour plusieurs utilisateurs
     */
    public function createForUsers(array $userUuids, array $data): array
    {
        $notifications = [];
        foreach ($userUuids as $userUuid) {
            $notifications[] = $this->create(array_merge($data, ['user_uuid' => $userUuid]));
        }
        return $notifications;
    }

    /**
     * Créer une notification pour tous les utilisateurs d'un groupe
     */
    public function createForGroup(string $groupNotifUuid, array $data): array
    {
        $users = User::whereHas('groupNotifs', function ($query) use ($groupNotifUuid) {
            $query->where('group_notif_uuid', $groupNotifUuid);
        })->get();

        $notifications = [];
        foreach ($users as $user) {
            $notifications[] = $this->create(array_merge($data, ['user_uuid' => $user->uuid_user]));
        }
        return $notifications;
    }

    /**
     * Récupérer les notifications d'un utilisateur
     */
    public function getUserNotifications(string $userUuid, array $filters = [], int $perPage = 20)
    {
        $query = Notification::where('user_uuid', $userUuid);

        // Filtres
        if (isset($filters['read'])) {
            if ($filters['read']) {
                $query->read();
            } else {
                $query->unread();
            }
        }

        if (isset($filters['important'])) {
            if ($filters['important']) {
                $query->important();
            }
        }

        if (isset($filters['type'])) {
            $query->ofType($filters['type']);
        }

        if (isset($filters['group_notif_uuid'])) {
            $query->inGroup($filters['group_notif_uuid']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                  ->orWhere('body', 'LIKE', "%{$search}%");
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Marquer une notification comme lue
     */
    public function markAsRead(string $notificationUuid, string $userUuid): ?Notification
    {
        $notification = Notification::where('uuid_notification', $notificationUuid)
            ->where('user_uuid', $userUuid)
            ->first();

        if ($notification) {
            $notification->markAsRead();
        }

        return $notification;
    }

    /**
     * Marquer toutes les notifications comme lues
     */
    public function markAllAsRead(string $userUuid): int
    {
        return Notification::where('user_uuid', $userUuid)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    /**
     * Marquer une notification comme importante
     */
    public function markAsImportant(string $notificationUuid, string $userUuid): ?Notification
    {
        $notification = Notification::where('uuid_notification', $notificationUuid)
            ->where('user_uuid', $userUuid)
            ->first();

        if ($notification) {
            $notification->markAsImportant();
        }

        return $notification;
    }

    /**
     * Retirer le statut important
     */
    public function unmarkImportant(string $notificationUuid, string $userUuid): ?Notification
    {
        $notification = Notification::where('uuid_notification', $notificationUuid)
            ->where('user_uuid', $userUuid)
            ->first();

        if ($notification) {
            $notification->unmarkImportant();
        }

        return $notification;
    }

    /**
     * Supprimer une notification (soft delete)
     */
    public function delete(string $notificationUuid, string $userUuid): bool
    {
        return Notification::where('uuid_notification', $notificationUuid)
            ->where('user_uuid', $userUuid)
            ->delete();
    }

    /**
     * Compter les notifications non lues
     */
    public function countUnread(string $userUuid): int
    {
        return Notification::where('user_uuid', $userUuid)
            ->whereNull('read_at')
            ->count();
    }

    /**
     * Compter les notifications importantes
     */
    public function countImportant(string $userUuid): int
    {
        return Notification::where('user_uuid', $userUuid)
            ->where('is_important', true)
            ->count();
    }
}