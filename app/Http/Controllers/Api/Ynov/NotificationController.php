<?php
// app/Http/Controllers/Api/Ynov/NotificationController.php
namespace App\Http\Controllers\Api\Ynov;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Ynov\NotificationResource;
use App\Services\Api\Ynov\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    /**
     * Liste des notifications de l'utilisateur
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $filters = $request->only([
            'read', 'important', 'type', 'group_notif_uuid', 'search'
        ]);
        
        $perPage = $request->integer('per_page', 20);
        $notifications = $this->notificationService->getUserNotifications(
            $user->uuid_user,
            $filters,
            $perPage
        );

        return response()->json([
            'success' => true,
            'message' => 'Notifications récupérées.',
            'code' => 'NOTIFICATIONS_LISTED',
            'data' => NotificationResource::collection($notifications),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
                'last_page' => $notifications->lastPage(),
                'unread_count' => $this->notificationService->countUnread($user->uuid_user),
                'important_count' => $this->notificationService->countImportant($user->uuid_user),
            ]
        ]);
    }

    /**
     * Récupérer le nombre de notifications non lues
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $user = $request->user();
        $count = $this->notificationService->countUnread($user->uuid_user);

        return response()->json([
            'success' => true,
            'message' => 'Nombre de notifications non lues.',
            'code' => 'UNREAD_COUNT',
            'data' => [
                'unread_count' => $count
            ]
        ]);
    }

    /**
     * Marquer une notification comme lue
     */
    public function markAsRead(Request $request, string $uuid_notification): JsonResponse
    {
        $user = $request->user();
        $notification = $this->notificationService->markAsRead($uuid_notification, $user->uuid_user);

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification non trouvée.',
                'code' => 'NOTIFICATION_NOT_FOUND',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Notification marquée comme lue.',
            'code' => 'NOTIFICATION_READ',
            'data' => new NotificationResource($notification),
        ]);
    }

    /**
     * Marquer toutes les notifications comme lues
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $user = $request->user();
        $count = $this->notificationService->markAllAsRead($user->uuid_user);

        return response()->json([
            'success' => true,
            'message' => 'Toutes les notifications ont été marquées comme lues.',
            'code' => 'ALL_NOTIFICATIONS_READ',
            'data' => [
                'marked_count' => $count
            ]
        ]);
    }

    /**
     * Marquer une notification comme importante
     */
    public function markAsImportant(Request $request, string $uuid_notification): JsonResponse
    {
        $user = $request->user();
        $notification = $this->notificationService->markAsImportant($uuid_notification, $user->uuid_user);

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification non trouvée.',
                'code' => 'NOTIFICATION_NOT_FOUND',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Notification marquée comme importante.',
            'code' => 'NOTIFICATION_IMPORTANT',
            'data' => new NotificationResource($notification),
        ]);
    }

    /**
     * Retirer le statut important d'une notification
     */
    public function unmarkImportant(Request $request, string $uuid_notification): JsonResponse
    {
        $user = $request->user();
        $notification = $this->notificationService->unmarkImportant($uuid_notification, $user->uuid_user);

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification non trouvée.',
                'code' => 'NOTIFICATION_NOT_FOUND',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Statut important retiré.',
            'code' => 'NOTIFICATION_UNIMPORTANT',
            'data' => new NotificationResource($notification),
        ]);
    }

    /**
     * Supprimer une notification
     */
    public function destroy(Request $request, string $uuid_notification): JsonResponse
    {
        $user = $request->user();
        $deleted = $this->notificationService->delete($uuid_notification, $user->uuid_user);

        if (!$deleted) {
            return response()->json([
                'success' => false,
                'message' => 'Notification non trouvée.',
                'code' => 'NOTIFICATION_NOT_FOUND',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Notification supprimée.',
            'code' => 'NOTIFICATION_DELETED',
        ]);
    }

    /**
     * [Admin] Créer une notification pour un utilisateur
     */
    public function create(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_uuid' => ['required', 'exists:users,uuid_user'],
            'group_notif_uuid' => ['nullable', 'exists:group_notifs,uuid_group_notif'],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'type' => ['nullable', 'string', 'max:50'],
            'action_url' => ['nullable', 'string', 'max:500'],
            'action_label' => ['nullable', 'string', 'max:100'],
            'channel' => ['nullable', 'string', 'max:30'],
            'metadata' => ['nullable', 'array'],
        ]);

        $notification = $this->notificationService->create([
            'user_uuid' => $validated['user_uuid'],
            'group_notif_uuid' => $validated['group_notif_uuid'] ?? null,
            'title' => $validated['title'],
            'body' => $validated['body'],
            'type' => $validated['type'] ?? 'system',
            'action_url' => $validated['action_url'] ?? null,
            'action_label' => $validated['action_label'] ?? null,
            'channel' => $validated['channel'] ?? 'database',
            'metadata' => $validated['metadata'] ?? null,
            'created_by' => $request->user()->uuid_user,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Notification créée.',
            'code' => 'NOTIFICATION_CREATED',
            'data' => new NotificationResource($notification),
        ], 201);
    }

    /**
     * [Admin] Créer une notification pour un groupe
     */
    public function createForGroup(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'group_notif_uuid' => ['required', 'exists:group_notifs,uuid_group_notif'],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'type' => ['nullable', 'string', 'max:50'],
            'action_url' => ['nullable', 'string', 'max:500'],
            'action_label' => ['nullable', 'string', 'max:100'],
            'metadata' => ['nullable', 'array'],
        ]);

        $notifications = $this->notificationService->createForGroup(
            $validated['group_notif_uuid'],
            [
                'title' => $validated['title'],
                'body' => $validated['body'],
                'type' => $validated['type'] ?? 'system',
                'action_url' => $validated['action_url'] ?? null,
                'action_label' => $validated['action_label'] ?? null,
                'metadata' => $validated['metadata'] ?? null,
                'created_by' => $request->user()->uuid_user,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Notifications créées pour le groupe.',
            'code' => 'NOTIFICATIONS_CREATED_FOR_GROUP',
            'data' => [
                'count' => count($notifications),
                'group_notif_uuid' => $validated['group_notif_uuid'],
            ]
        ], 201);
    }
}