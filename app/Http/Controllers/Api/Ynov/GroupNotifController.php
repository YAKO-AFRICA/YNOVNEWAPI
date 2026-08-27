<?php
// app/Http/Controllers/Api/Ynov/GroupNotifController.php
namespace App\Http\Controllers\Api\Ynov;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Ynov\StoreGroupNotifRequest;
use App\Http\Requests\Api\Ynov\UpdateGroupNotifRequest;
use App\Http\Resources\Api\Ynov\GroupNotifResource;
use App\Models\Api\Ynov\parameter\GroupNotif;
use App\Services\Api\Ynov\GroupNotifService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class GroupNotifController extends Controller
{
    public function __construct(
        private GroupNotifService $groupNotifService
    ) {}

    /**
     * Liste des groupes de notification
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['status', 'search', 'channel']);
        $perPage = $request->integer('per_page', 20);
        
        $groups = $this->groupNotifService->getGroupsWithCount($filters, $perPage);

        return response()->json([
            'success' => true,
            'message' => 'Groupes de notification récupérés.',
            'code' => 'GROUPS_LISTED',
            'data' => GroupNotifResource::collection($groups),
            'meta' => [
                'current_page' => $groups->currentPage(),
                'per_page' => $groups->perPage(),
                'total' => $groups->total(),
                'last_page' => $groups->lastPage(),
            ]
        ]);
    }

    /**
     * Créer un groupe de notification
     */
    public function store(StoreGroupNotifRequest $request): JsonResponse
    {
        $group = $this->groupNotifService->create(
            $request->validated(),
            $request->user()->uuid_user
        );

        return response()->json([
            'success' => true,
            'message' => 'Groupe de notification créé.',
            'code' => 'GROUP_CREATED',
            'data' => new GroupNotifResource($group),
        ], 201);
    }

    /**
     * Détails d'un groupe de notification
     */
    public function show(string $uuid_group_notif): JsonResponse
    {
        $group = GroupNotif::where('uuid_group_notif', $uuid_group_notif)
            ->with(['users' => function ($query) {
                $query->withPivot('is_primary', 'is_active', 'assigned_at');
            }])
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'message' => 'Détails du groupe.',
            'code' => 'GROUP_FOUND',
            'data' => new GroupNotifResource($group),
        ]);
    }

    /**
     * Mettre à jour un groupe de notification
     */
    public function update(UpdateGroupNotifRequest $request, string $uuid_group_notif): JsonResponse
    {
        $group = GroupNotif::where('uuid_group_notif', $uuid_group_notif)->firstOrFail();
        
        $updated = $this->groupNotifService->update(
            $group,
            $request->validated(),
            $request->user()->uuid_user
        );

        return response()->json([
            'success' => true,
            'message' => 'Groupe de notification mis à jour.',
            'code' => 'GROUP_UPDATED',
            'data' => new GroupNotifResource($updated),
        ]);
    }

    /**
     * Supprimer un groupe de notification
     */
    public function destroy(Request $request, string $uuid_group_notif): JsonResponse
    {
        try {
            $group = GroupNotif::where('uuid_group_notif', $uuid_group_notif)->firstOrFail();
            $this->groupNotifService->delete($group, $request->user()->uuid_user);

            return response()->json([
                'success' => true,
                'message' => 'Groupe de notification supprimé.',
                'code' => 'GROUP_DELETED',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation.',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * Assigner des utilisateurs à un groupe
     */
    public function assignUsers(Request $request, string $uuid_group_notif): JsonResponse
    {
        $request->validate([
            'user_uuids' => ['required', 'array', 'min:1'],
            'user_uuids.*' => ['exists:users,uuid_user'],
        ]);

        $group = GroupNotif::where('uuid_group_notif', $uuid_group_notif)->firstOrFail();
        
        $assigned = $this->groupNotifService->assignUsers(
            $group,
            $request->user_uuids,
            $request->user()->uuid_user
        );

        return response()->json([
            'success' => true,
            'message' => 'Utilisateurs assignés au groupe.',
            'code' => 'USERS_ASSIGNED',
            'data' => [
                'assigned_count' => count($assigned),
                'group' => new GroupNotifResource($group->fresh()),
            ]
        ]);
    }

    /**
     * Retirer un utilisateur d'un groupe
     */
    public function removeUser(Request $request, string $uuid_group_notif, string $uuid_user): JsonResponse
    {
        $group = GroupNotif::where('uuid_group_notif', $uuid_group_notif)->firstOrFail();
        
        $removed = $this->groupNotifService->removeUser(
            $group,
            $uuid_user,
            $request->user()->uuid_user
        );

        if (!$removed) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non trouvé dans ce groupe.',
                'code' => 'USER_NOT_IN_GROUP',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Utilisateur retiré du groupe.',
            'code' => 'USER_REMOVED',
        ]);
    }

    /**
     * Définir le groupe principal d'un utilisateur
     */
    public function setPrimaryGroup(Request $request, string $uuid_group_notif): JsonResponse
    {
        $group = GroupNotif::where('uuid_group_notif', $uuid_group_notif)->firstOrFail();
        
        $set = $this->groupNotifService->setPrimaryGroup(
            $request->user(),
            $group->uuid_group_notif,
            $request->user()->uuid_user
        );

        if (!$set) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'appartenez pas à ce groupe.',
                'code' => 'USER_NOT_IN_GROUP',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Groupe principal défini.',
            'code' => 'PRIMARY_GROUP_SET',
        ]);
    }

    /**
     * Récupérer mes groupes
     */
    public function myGroups(Request $request): JsonResponse
    {
        $groups = $this->groupNotifService->getUserGroups($request->user()->uuid_user);

        return response()->json([
            'success' => true,
            'message' => 'Mes groupes de notification.',
            'code' => 'MY_GROUPS_LISTED',
            'data' => $groups,
        ]);
    }

    /**
     * Récupérer les canaux disponibles
     */
    public function channels(): JsonResponse
    {
        $channels = $this->groupNotifService->getAvailableChannels();

        return response()->json([
            'success' => true,
            'message' => 'Canaux disponibles.',
            'code' => 'CHANNELS_LISTED',
            'data' => $channels,
        ]);
    }

    /**
     * Dupliquer un groupe de notification
     */
    public function duplicate(Request $request, string $uuid_group_notif): JsonResponse
    {
        $group = GroupNotif::where('uuid_group_notif', $uuid_group_notif)->firstOrFail();
        
        $newGroup = $this->groupNotifService->duplicate(
            $group,
            $request->user()->uuid_user
        );

        return response()->json([
            'success' => true,
            'message' => 'Groupe dupliqué.',
            'code' => 'GROUP_DUPLICATED',
            'data' => new GroupNotifResource($newGroup),
        ], 201);
    }

    /**
     * Statistiques des groupes
     */
    public function stats(): JsonResponse
    {
        $stats = $this->groupNotifService->getStats();

        return response()->json([
            'success' => true,
            'message' => 'Statistiques des groupes.',
            'code' => 'GROUP_STATS',
            'data' => $stats,
        ]);
    }
}