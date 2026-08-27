<?php
// app/Http/Controllers/Api/Ynov/PermissionController.php

namespace App\Http\Controllers\Api\Ynov;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Ynov\StorePermissionRequest;
use App\Http\Requests\Api\Ynov\UpdatePermissionRequest;
use App\Models\Api\Ynov\parameter\ActivityLog;
use App\Models\Api\Ynov\parameter\Permission;
use App\Services\Api\Ynov\PermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    public function __construct(private PermissionService $permissionService) {}


    public function suggestedActions(): JsonResponse
    {
        $data = $this->permissionService->suggestedActions();
        return response()->json([
            'success' => true,
            'message' => 'Actions standards par catégorie.',
            'code' => 'ACTIONS_SUGGESTED',
            'data' =>  $data
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $permissions = Permission::query()
            ->with('group')
            ->when(
                $request->permission_group_uuid,
                fn($q) =>
                $q->where('permission_group_uuid', $request->permission_group_uuid)
            )
            ->when(
                $request->status,
                fn($q) =>
                $q->where('status', $request->status)
            )
            ->when(
                $request->search,
                fn($q) =>
                $q->where(function ($query) use ($request) {
                    $query->where('code', 'LIKE', "%{$request->search}%")
                        ->orWhere('libelle', 'LIKE', "%{$request->search}%")
                        ->orWhere('action', 'LIKE', "%{$request->search}%");
                })
            )
            ->orderBy('permission_group_uuid')
            ->orderBy('action')
            ->paginate($request->integer('per_page', 20));

        return response()->json([
            'success' => true,
            'message' => 'Permissions récupérées.',
            'code' => 'PERMISSIONS_LISTED',
            'data' => $permissions,
        ]);
    }


    public function store(StorePermissionRequest $request): JsonResponse
    {
        // Log::info($request->all());
        $permission = $this->permissionService->create(
            $request->validated(),
            $request->user()->details?->uuid_user_details
        );

        // Log l'action
        ActivityLog::log([
            'user_uuid' => $request->user()->uuid_user,
            'action' => 'create',
            'action_type' => 'crud',
            'module' => 'permissions',
            'description' => "Création de la permission : {$permission->code}",
            'resource_type' => 'permission',
            'resource_id' => $permission->uuid_permission,
            'new_values' => $permission->toArray(),
            'level' => 'info',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Permission créée avec success.',
            'code' => 'PERMISSION_CREATED',
            'data' => $permission,
        ], 201);
    }

    public function show($uuid_permission): JsonResponse
    {
        $permission = Permission::where('uuid_permission', $uuid_permission)->firstOrFail();
        ActivityLog::log([
            'user_uuid' => request()->user()->uuid_user,
            'action' => 'read',
            'action_type' => 'crud',
            'module' => 'permissions',
            'description' => "Affichage des details de la permission : {$permission->code}",
            'resource_type' => 'permission',
            'resource_id' => $permission->uuid_permission,
            'level' => 'info',
        ]);
        return response()->json([
            'success' => true,
            'message' => 'Details de la permission récupérée avec success.',
            'code' => 'PERMISSION_FOUND',
            'data' => $permission->load('group'),
        ]);
    }

    public function update(UpdatePermissionRequest $request, $uuid_permission): JsonResponse
    {
        $permission = Permission::where('uuid_permission', $uuid_permission)->firstOrFail();
        $oldValues = $permission->toArray();
        $updated = $this->permissionService->update(
            $permission,
            $request->validated(),
            $request->user()->details?->uuid_user_details
        );

        // Log l'action
        ActivityLog::log([
            'user_uuid' => $request->user()->uuid_user,
            'action' => 'update',
            'action_type' => 'crud',
            'module' => 'permissions',
            'description' => "Mise à jour de la permission : {$permission->code}",
            'resource_type' => 'permission',
            'resource_id' => $permission->uuid_permission,
            'old_values' => $oldValues,
            'new_values' => $updated->toArray(),
            'level' => 'info',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Permission mise à jour.',
            'code' => 'PERMISSION_UPDATED',
            'data' => $updated,
        ]);
    }

    public function destroy(Request $request, $uuid_permission): JsonResponse
    {
        $permission = Permission::where('uuid_permission', $uuid_permission)->firstOrFail();
        // Vérifier si la permission est utilisée par un rôle
        if ($permission->roles()->count() > 0) {
            ActivityLog::log([
                'user_uuid' => $request->user()->uuid_user,
                'action' => 'delete',
                'action_type' => 'crud',
                'module' => 'permissions',
                'description' => "Tentative de suppression de la permission : {$permission->code}, mais cette permission est attribuée à un ou plusieurs rôles et ne peut donc pas etre supprimée.",
                'resource_type' => 'permission',
                'resource_id' => $permission->uuid_permission,
                'level' => 'warning',
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Cette permission est attribuée à un ou plusieurs rôles et ne peut donc pas être supprimée.',
                'code' => 'PERMISSION_IN_USE',
            ], 422);
        }

        $permission->update([
            'status' => 'inactif',
            'deleted_by' => $request->user()->details?->uuid_user_details
        ]);
        $permission->delete();

        // Log l'action
        ActivityLog::log([
            'user_uuid' => $request->user()->uuid_user,
            'action' => 'delete',
            'action_type' => 'crud',
            'module' => 'permissions',
            'description' => "Suppression de la permission : {$permission->code}",
            'resource_type' => 'permission',
            'resource_id' => $permission->uuid_permission,
            'level' => 'warning',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Permission supprimée.',
            'code' => 'PERMISSION_DELETED'
        ]);
    }

    /**
     * Récupérer toutes les permissions avec leurs groupes
     */
    public function allWithGroups(Request $request): JsonResponse
    {
        $data = $this->permissionService->getAllPermissionsWithGroups();

        ActivityLog::log([
            'user_uuid' => $request->user()->uuid_user,
            'action' => 'read',
            'action_type' => 'crud',
            'module' => 'permissions',
            'description' => 'Récupération de toutes les permissions avec leurs groupes',
            'resource_type' => 'permission',
            'level' => 'info',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Permissions et groupes récupérés.',
            'code' => 'PERMISSIONS_GROUPS_LISTED',
            'data' => $data,
        ]);
    }

    /**
     * Récupérer les permissions d'un utilisateur
     */

    public function userPermissions(Request $request): JsonResponse
    {
        $user = $request->user();
        $permissions = $this->permissionService->getUserPermissions($user);

        ActivityLog::log([
            'user_uuid' => $request->user()->uuid_user,
            'action' => 'read',
            'action_type' => 'crud',
            'module' => 'permissions',
            'description' => 'Récupération des permissions de l\'utilisateur, ' . $user->uuid_user,
            'resource_type' => 'permission',
            'level' => 'info',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Permissions de l\'utilisateur récupérées.',
            'code' => 'USER_PERMISSIONS_LISTED',
            'data' => [
                'user_uuid' => $user->uuid_user,
                'permissions' => $permissions,
                'is_super_admin' => $user->isSuperAdmin(),
            ],
        ]);
    }
}
