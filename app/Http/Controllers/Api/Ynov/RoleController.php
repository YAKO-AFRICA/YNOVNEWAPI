<?php
// namespace App\Http\Controllers\Api\Ynov;

// use App\Http\Controllers\Controller;
// use App\Http\Requests\Api\Ynov\AssignPermissionsRequest;
// use App\Http\Requests\Api\Ynov\StoreRoleRequest;
// use App\Http\Requests\Api\Ynov\UpdateRoleRequest;
// use App\Http\Resources\Api\Ynov\UserResource;
// use App\Models\Api\Ynov\parameter\ActivityLog;
// use App\Models\Api\Ynov\parameter\Role;
// use App\Services\Api\Ynov\RoleService;
// use Illuminate\Http\JsonResponse;
// use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Log;

// class RoleController extends Controller
// {
//     public function __construct(private RoleService $roleService) {}

//     public function index(Request $request): JsonResponse
//     {
//         $roles = Role::query()
//             ->when($request->status, fn ($q) => $q->where('status', $request->status))
//             ->when($request->libelle, fn ($q) => $q->where('libelle', 'like', "%{$request->libelle}%"))
//             ->when($request->description, fn ($q) => $q->where('description', 'like', "%{$request->description}%"))
//             ->when($request->created_by, fn ($q) => $q->where('created_by', $request->created_by))
//             ->when($request->updated_by, fn ($q) => $q->where('updated_by', $request->updated_by))
//             ->when($request->deleted_by, fn ($q) => $q->where('deleted_by', $request->deleted_by))
//             ->with('users')
//             ->with('permissions')
//             ->orderBy('libelle')
//             ->paginate($request->integer('per_page', 20));

//         return response()->json([
//             'success' => true,
//             'message' => 'Liste des rôles récupérée.',
//             'code' => 'ROLES_LISTED',
//             'data' => $roles,
//         ]);
//     }

//     public function store(StoreRoleRequest $request): JsonResponse
//     {
//         $role = $this->roleService->create($request->validated(), $request->user()->details?->uuid_user_details);

//         // Log l'action
//         ActivityLog::log([
//             'user_uuid' => $request->user()->uuid_user,
//             'action' => 'create',
//             'action_type' => 'crud',
//             'module' => 'roles',
//             'description' => "Création du rôle : {$role->libelle}",
//             'resource_type' => 'role',
//             'resource_id' => $role->uuid_role,
//             'new_values' => $role->toArray(),
//             'level' => 'info',
//         ]);

//         return response()->json([
//             'success' => true,
//             'message' => 'Rôle créé avec success.',
//             'code' => 'ROLE_CREATED',
//             'data' => $role,
//         ], 201);
//     }

//     public function show($uuid_role): JsonResponse
//     {
//         $role = Role::where('uuid_role', $uuid_role)->firstOrFail();
//         ActivityLog::log([
//             'user_uuid' => request()->user()->uuid_user,
//             'action' => 'read',
//             'action_type' => 'crud',
//             'module' => 'roles',
//             'description' => "Affichage des details du rôle : {$role->libelle}",
//             'resource_type' => 'role',
//             'resource_id' => $role->uuid_role,
//             'level' => 'info',
//         ]);
//         return response()->json([
//             'success' => true,
//             'message' => 'Rôle récupéré.',
//             'code' => 'ROLE_FOUND',
//             'data' => $role->load('permissions.group'),
//         ]);
//     }

//     public function update(UpdateRoleRequest $request, $uuid_role): JsonResponse
//     {
//         $role = Role::where('uuid_role', $uuid_role)
//         ->when($request->status, fn ($q) => $q->where('status', $request->status))
//         ->firstOrFail();
//         if ($role->is_system) {
//             ActivityLog::log([
//                 'user_uuid' => $request->user()->uuid_user,
//                 'action' => 'update',
//                 'action_type' => 'crud',
//                 'module' => 'roles',
//                 'description' => "Tentative de modification du rôle systeme : {$role->libelle} mais ce rôle est protégé et ne peut donc pas etre modifiée.",
//                 'resource_type' => 'role',
//                 'resource_id' => $role->uuid_role,
//                 'level' => 'warning',
//             ]);
//             return response()->json(['success' => false, 'message' => 'Rôle système protégé.', 'code' => 'ROLE_PROTECTED'], 403);
//         }

//         $updated = $this->roleService->update($role, $request->validated(), $request->user()->details?->uuid_user_details);

//         // Log l'action
//         ActivityLog::log([
//             'user_uuid' => $request->user()->uuid_user,
//             'action' => 'update',
//             'action_type' => 'crud',
//             'module' => 'roles',
//             'description' => "Mise à jour du rôle : {$role->libelle}",
//             'resource_type' => 'role',
//             'resource_id' => $role->uuid_role,
//             'old_values' => $role->toArray(),
//             'new_values' => $updated->toArray(),
//             'level' => 'info',
//         ]);

//         return response()->json([
//             'success' => true,
//             'message' => 'Rôle mis à jour.',
//             'code' => 'ROLE_UPDATED',
//             'data' => $updated,
//         ]);
//     }

//     public function destroy(Request $request, $uuid_role): JsonResponse
//     {
//         $role = Role::where('uuid_role', $uuid_role)
//         ->when($request->status, fn ($q) => $q->where('status', $request->status))
//         ->firstOrFail();
//         if ($role->is_system) {
//             ActivityLog::log([
//                 'user_uuid' => $request->user()->uuid_user,
//                 'action' => 'delete',
//                 'action_type' => 'crud',
//                 'module' => 'roles',
//                 'description' => "Tentative de suppression du rôle systeme : {$role->libelle} mais ce rôle est protégé et ne peut donc pas etre supprimé.",
//                 'resource_type' => 'role',
//                 'resource_id' => $role->uuid_role,
//                 'level' => 'warning',
//             ]);
//             return response()->json(['success' => false, 'message' => 'Rôle système protégé.', 'code' => 'ROLE_PROTECTED'], 403);
//         }

//         $role->update([
//             'status' => 'inactif',
//             'deleted_by' => $request->user()->details?->uuid_user_details
//             ]);
//         $role->delete();

//         // Log l'action
//         ActivityLog::log([
//             'user_uuid' => $request->user()->uuid_user,
//             'action' => 'delete',
//             'action_type' => 'crud',
//             'module' => 'roles',
//             'description' => "Suppression du rôle : {$role->libelle}",
//             'resource_type' => 'role',
//             'resource_id' => $role->uuid_role,
//             'level' => 'info',
//         ]);

//         return response()->json(['success' => true, 'message' => 'Rôle supprimé.', 'code' => 'ROLE_DELETED']);
//     }

//     public function assignPermissions(AssignPermissionsRequest $request, $uuid_role): JsonResponse
//     {
//         Log::info("AssignPermissionsRequest", $request->all());
//         $role = Role::where('uuid_role', $uuid_role)->firstOrFail();
//         if ($role->is_super_admin) {
//             return response()->json(['success' => false, 'message' => 'Le Super Admin dispose déjà de tous les droits.', 'code' => 'ROLE_SUPER_ADMIN_NOT_ASSIGNABLE'], 422);
//         }

//         $this->roleService->assignPermissions($role, $request->permission_uuids, $request->user()->details?->uuid_user_details);

//         // Log l'action
//         ActivityLog::log([
//             'user_uuid' => $request->user()->uuid_user,
//             'action' => 'update',
//             'action_type' => 'crud',
//             'module' => 'roles',
//             'description' => "Attribution des permissions au rôle : {$role->libelle}",
//             'resource_type' => 'role',
//             'resource_id' => $role->uuid_role,
//             'level' => 'info',
//         ]);

//         return response()->json([
//             'success' => true,
//             'message' => 'Permissions attribuées.',
//             'code' => 'ROLE_PERMISSIONS_ASSIGNED',
//             'data' => $role->load('permissions.group'),
//         ]);
//     }

//     public function users(Request $request,  $uuid_role): JsonResponse
//     {
//         $role = Role::where('uuid_role', $uuid_role)
//         ->when($request->status, fn ($q) => $q->where('status', $request->status))
//         ->firstOrFail();
//         $users = $role->users()->with(['details', 'partner'])->paginate($request->integer('per_page', 20));

//         // Log l'action
//         ActivityLog::log([
//             'user_uuid' => $request->user()->uuid_user,
//             'action' => 'read',
//             'action_type' => 'crud',
//             'module' => 'roles',
//             'description' => "Récupération des utilisateurs du rôle : {$role->libelle}",
//             'resource_type' => 'role',
//             'resource_id' => $role->uuid_role,
//             'level' => 'info',
//         ]);
    
//         return response()->json([
//             'success' => true,
//             'message' => 'Utilisateurs du rôle récupérés.',
//             'code' => 'ROLE_USERS_LISTED',
//             'data' => UserResource::collection($users),
//         ]);
//     }
// }

namespace App\Http\Controllers\Api\Ynov;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Ynov\AssignPermissionsRequest;
use App\Http\Requests\Api\Ynov\StoreRoleRequest;
use App\Http\Requests\Api\Ynov\UpdateRoleRequest;
use App\Http\Resources\Api\Ynov\UserResource;
use App\Models\Api\Ynov\parameter\ActivityLog;
use App\Models\Api\Ynov\parameter\Permission;
use App\Models\Api\Ynov\parameter\Role;
use App\Services\Api\Ynov\RoleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RoleController extends Controller
{
    public function __construct(private RoleService $roleService) {}
   
    public function index(Request $request): JsonResponse
    {
        $roles = Role::query()
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->libelle, fn ($q) => $q->where('libelle', 'like', "%{$request->libelle}%"))
            ->when($request->description, fn ($q) => $q->where('description', 'like', "%{$request->description}%"))
            ->when($request->created_by, fn ($q) => $q->where('created_by', $request->created_by))
            ->when($request->updated_by, fn ($q) => $q->where('updated_by', $request->updated_by))
            ->when($request->deleted_by, fn ($q) => $q->where('deleted_by', $request->deleted_by))
            ->with('users')
            ->with('permissions')
            ->orderBy('libelle')
            ->paginate($request->integer('per_page', 20));

        return response()->json([
            'success' => true,
            'message' => 'Liste des rôles récupérée.',
            'code' => 'ROLES_LISTED',
            'data' => $roles,
        ]);
    }
    public function store(StoreRoleRequest $request): JsonResponse
    {
        $role = $this->roleService->create($request->validated(), $request->user()->details?->uuid_user_details);

        ActivityLog::log([
            'user_uuid' => $request->user()->uuid_user,
            'action' => 'create',
            'action_type' => 'crud',
            'module' => 'roles',
            'description' => "Création du rôle : {$role->libelle}",
            'resource_type' => 'role',
            'resource_id' => $role->uuid_role,
            'new_values' => $role->toArray(),
            'level' => 'info',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Rôle créé avec succès.',
            'code' => 'ROLE_CREATED',
            'data' => $role,
        ], 201);
    }

    public function show($uuid_role): JsonResponse
    {
        $role = Role::where('uuid_role', $uuid_role)->firstOrFail();

        ActivityLog::log([
            'user_uuid' => request()->user()->uuid_user,
            'action' => 'read',
            'action_type' => 'crud',
            'module' => 'roles',
            'description' => "Affichage des détails du rôle : {$role->libelle}",
            'resource_type' => 'role',
            'resource_id' => $role->uuid_role,
            'level' => 'info',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Rôle récupéré.',
            'code' => 'ROLE_FOUND',
            'data' => $role->load('permissions.group'),
        ]);
    }

    public function update(UpdateRoleRequest $request, $uuid_role): JsonResponse
    {
        $role = Role::where('uuid_role', $uuid_role)
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->firstOrFail();

        if ($role->is_system) {
            ActivityLog::log([
                'user_uuid' => $request->user()->uuid_user,
                'action' => 'update',
                'action_type' => 'crud',
                'module' => 'roles',
                'description' => "Tentative de modification du rôle système : {$role->libelle} mais ce rôle est protégé.",
                'resource_type' => 'role',
                'resource_id' => $role->uuid_role,
                'level' => 'warning',
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Rôle système protégé.',
                'code' => 'ROLE_PROTECTED'
            ], 403);
        }

        $updated = $this->roleService->update($role, $request->validated(), $request->user()->details?->uuid_user_details);

        ActivityLog::log([
            'user_uuid' => $request->user()->uuid_user,
            'action' => 'update',
            'action_type' => 'crud',
            'module' => 'roles',
            'description' => "Mise à jour du rôle : {$role->libelle}",
            'resource_type' => 'role',
            'resource_id' => $role->uuid_role,
            'old_values' => $role->toArray(),
            'new_values' => $updated->toArray(),
            'level' => 'info',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Rôle mis à jour.',
            'code' => 'ROLE_UPDATED',
            'data' => $updated,
        ]);
    }

    public function destroy(Request $request, $uuid_role): JsonResponse
    {
        $role = Role::where('uuid_role', $uuid_role)
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->firstOrFail();

        if ($role->is_system) {
            ActivityLog::log([
                'user_uuid' => $request->user()->uuid_user,
                'action' => 'delete',
                'action_type' => 'crud',
                'module' => 'roles',
                'description' => "Tentative de suppression du rôle système : {$role->libelle} mais ce rôle est protégé.",
                'resource_type' => 'role',
                'resource_id' => $role->uuid_role,
                'level' => 'warning',
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Rôle système protégé.',
                'code' => 'ROLE_PROTECTED'
            ], 403);
        }

        $role->update([
            'status' => 'inactif',
            'deleted_by' => $request->user()->details?->uuid_user_details
        ]);
        $role->delete();

        ActivityLog::log([
            'user_uuid' => $request->user()->uuid_user,
            'action' => 'delete',
            'action_type' => 'crud',
            'module' => 'roles',
            'description' => "Suppression du rôle : {$role->libelle}",
            'resource_type' => 'role',
            'resource_id' => $role->uuid_role,
            'level' => 'info',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Rôle supprimé.',
            'code' => 'ROLE_DELETED'
        ]);
    }

    /**
     * ================================================================
     * CORRECTION #16 : Attribution des permissions avec contrôle hiérarchique
     * ================================================================
     */
    public function assignPermissions(AssignPermissionsRequest $request, $uuid_role): JsonResponse
    {
        $role = Role::where('uuid_role', $uuid_role)->firstOrFail();
        $currentUser = $request->user();

        // ================================================================
        // VÉRIFICATION : Le Super Admin a tous les droits
        // ================================================================
        if ($role->is_super_admin) {
            return response()->json([
                'success' => false,
                'message' => 'Le Super Admin dispose déjà de tous les droits.',
                'code' => 'ROLE_SUPER_ADMIN_NOT_ASSIGNABLE'
            ], 422);
        }

        // ================================================================
        // CORRECTION #16 : Contrôle hiérarchique
        // Un utilisateur ne peut assigner que des rôles de niveau inférieur ou égal
        // ================================================================
        if (!$currentUser->isSuperAdmin()) {
            $currentUserRole = $currentUser->role;
            
            if (!$currentUserRole) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous n\'avez pas de rôle associé.',
                    'code' => 'ROLE_NOT_FOUND'
                ], 403);
            }

            if ($role->level > $currentUserRole->level) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous ne pouvez pas attribuer un rôle de niveau supérieur au vôtre.',
                    'code' => 'ROLE_HIERARCHY_VIOLATION'
                ], 403);
            }
        }

        // ================================================================
        // CORRECTION #17 : Vérification des permissions sensibles
        // ================================================================
        if (!$currentUser->isSuperAdmin()) {
            // Récupérer les permissions à attribuer
            $permissions = Permission::whereIn('uuid_permission', $request->permission_uuids)->get();
            
            foreach ($permissions as $permission) {
                if ($permission->is_guard && !$currentUser->hasPermission('permissions.gérer_sensibles')) {
                    return response()->json([
                        'success' => false,
                        'message' => "Vous ne pouvez pas attribuer la permission sensible : {$permission->libelle}.",
                        'code' => 'GUARD_PERMISSION_DENIED'
                    ], 403);
                }
            }
        }

        // Attribution des permissions
        $this->roleService->assignPermissions(
            $role,
            $request->permission_uuids,
            $request->user()->details?->uuid_user_details
        );

        ActivityLog::log([
            'user_uuid' => $request->user()->uuid_user,
            'action' => 'update',
            'action_type' => 'crud',
            'module' => 'roles',
            'description' => "Attribution des permissions au rôle : {$role->libelle}",
            'resource_type' => 'role',
            'resource_id' => $role->uuid_role,
            'level' => 'info',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Permissions attribuées.',
            'code' => 'ROLE_PERMISSIONS_ASSIGNED',
            'data' => $role->load('permissions.group'),
        ]);
    }

    /**
     * ================================================================
     * CORRECTION #16 : Attribution des permissions avec contrôle hiérarchique
     * ================================================================
     */
    public function users(Request $request, $uuid_role): JsonResponse
    {
        $role = Role::where('uuid_role', $uuid_role)
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->firstOrFail();

        $users = $role->users()->with(['details', 'partner'])->paginate($request->integer('per_page', 20));

        ActivityLog::log([
            'user_uuid' => $request->user()->uuid_user,
            'action' => 'read',
            'action_type' => 'crud',
            'module' => 'roles',
            'description' => "Récupération des utilisateurs du rôle : {$role->libelle}",
            'resource_type' => 'role',
            'resource_id' => $role->uuid_role,
            'level' => 'info',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Utilisateurs du rôle récupérés.',
            'code' => 'ROLE_USERS_LISTED',
            'data' => UserResource::collection($users),
        ]);
    }
}