<?php
namespace App\Http\Controllers\Api\Ynov;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Ynov\StorePermissionGroupRequest;
use App\Http\Requests\Api\Ynov\UpdatePermissionGroupRequest;
use App\Models\Api\Ynov\parameter\ActivityLog;
use App\Models\Api\Ynov\parameter\PermissionGroup;
use App\Services\Api\Ynov\PermissionGroupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PermissionGroupController extends Controller
{
    public function __construct(private PermissionGroupService $groupService) {}

    public function index(Request $request): JsonResponse
    {
        $groups = PermissionGroup::query()->with('permissions')
        ->when($request->status, fn ($q) => $q->where('status', $request->status))
        ->when($request->code, fn ($q) => $q->where('code', $request->code))
        ->when($request->search, fn ($q) => $q->where('libelle', 'LIKE', "%{$request->search}%"))
        ->when($request->search, fn ($q) => $q->where('description', 'LIKE', "%{$request->search}%"))
        ->when($request->parent_uuid, fn ($q) => $q->where('parent_uuid', 'LIKE', "%{$request->parent_uuid}%"))
        ->when($request->created_by, fn ($q) => $q->where('parent_uuid', 'LIKE', "%{$request->created_by}%"))
        ->orderBy('ordre_affichage')->get();

        return response()->json([
            'success' => true,
            'message' => 'Groupes de permissions récupérés.',
            'code' => 'PERMISSION_GROUPS_LISTED',
            'data' => $groups,
        ]);
    }

    

    public function store(StorePermissionGroupRequest $request): JsonResponse
    {
        $group = $this->groupService->create($request->validated(), $request->user()->details?->uuid_user_details);

        // Log l'action
        ActivityLog::log([
            'user_uuid' => $request->user()->uuid_user,
            'action' => 'create',
            'action_type' => 'crud',
            'module' => 'permission_groups',
            'description' => "Création du groupe de permissions : {$group->libelle}",
            'resource_type' => 'permission_group',
            'resource_id' => $group->uuid_permission_group,
            'new_values' => $group->toArray(),
            'level' => 'info',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Groupe de permissions créé avec success.',
            'code' => 'PERMISSION_GROUP_CREATED',
            'data' => $group,
        ], 201);
    }

    public function show($uuid_permissionGroup): JsonResponse
    {
        $permissionGroup = PermissionGroup::where('uuid_permission_group', $uuid_permissionGroup)->firstOrFail();
        ActivityLog::log([
            'user_uuid' => request()->user()->uuid_user,
            'action' => 'read',
            'action_type' => 'crud',
            'module' => 'permission_groups',
            'description' => "Affichage des details du groupe de permissions : {$permissionGroup->libelle}",
            'resource_type' => 'permission_group',
            'resource_id' => $permissionGroup->uuid_permission_group,
            'level' => 'info',
        ]);
        return response()->json([
            'success' => true,
            'message' => 'Details du groupe de permissions récupéré.',
            'code' => 'PERMISSION_GROUP_FOUND',
            'data' => $permissionGroup->load('permissions'),
        ]);
    }

    public function update(UpdatePermissionGroupRequest $request, $uuid_permissionGroup): JsonResponse
    {
        $permissionGroup = PermissionGroup::where('uuid_permission_group', $uuid_permissionGroup)->firstOrFail();
        $updated = $this->groupService->update($permissionGroup, $request->validated(), $request->user()->details?->uuid_user_details);

        // Log l'action
        ActivityLog::log([
            'user_uuid' => $request->user()->uuid_user,
            'action' => 'update',
            'action_type' => 'crud',
            'module' => 'permission_groups',
            'description' => "Mise à jour du groupe de permissions : {$permissionGroup->libelle}",
            'resource_type' => 'permission_group',
            'resource_id' => $permissionGroup->uuid_permission_group,
            'old_values' => $permissionGroup->toArray(),
            'new_values' => $updated->toArray(),
            'level' => 'info',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Module mis à jour.',
            'code' => 'PERMISSION_GROUP_UPDATED',
            'data' => $updated,
        ]);
    }

    public function destroy(Request $request, $uuid_permissionGroup): JsonResponse
    {
        $permissionGroup = PermissionGroup::where('uuid_permission_group', $uuid_permissionGroup)->firstOrFail();
        if ($permissionGroup->permissions()->exists()) {
            ActivityLog::log([
                'user_uuid' => $request->user()->uuid_user,
                'action' => 'delete',
                'action_type' => 'crud',
                'module' => 'permission_groups',
                'description' => "Tentative de suppression du groupe de permissions : {$permissionGroup->libelle} mais ce groupe contient des permissions et ne peut donc pas etre supprimé.",
                'resource_type' => 'permission_group',
                'resource_id' => $permissionGroup->uuid_permission_group,
                'level' => 'warning',
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Le groupe contient des permissions et ne peut donc pas étre supprimé.',
                'code' => 'PERMISSION_GROUP_NOT_EMPTY',
            ], 422);
        }

        $permissionGroup->update([
            'status' => 'inactif',
            'deleted_by' => $request->user()->details?->uuid_user_details
            ]);
        $permissionGroup->delete();

        // Log l'action
        ActivityLog::log([
            'user_uuid' => $request->user()->uuid_user,
            'action' => 'delete',
            'action_type' => 'crud',
            'module' => 'permission_groups',
            'description' => "Suppression du groupe de permissions : {$permissionGroup->libelle}",
            'resource_type' => 'permission_group',
            'resource_id' => $permissionGroup->uuid_permission_group,
            'level' => 'info',
        ]);

        return response()->json(['success' => true, 'message' => 'Module supprimé.', 'code' => 'PERMISSION_GROUP_DELETED']);
    }
}