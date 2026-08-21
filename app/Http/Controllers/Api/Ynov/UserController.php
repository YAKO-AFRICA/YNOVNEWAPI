<?php

// namespace App\Http\Controllers\Api\Ynov;

// use App\Http\Controllers\Controller;
// use App\Http\Requests\Api\Ynov\BlockUserRequest;
// use App\Http\Requests\Api\Ynov\StoreUserRequest;
// use App\Http\Requests\Api\Ynov\UpdateUserRequest;
// use App\Http\Resources\Api\Ynov\UserResource;
// use App\Models\Api\Ynov\parameter\ActivityLog;
// use App\Models\Api\Ynov\parameter\User;
// use App\Services\Api\Ynov\Auth\FreezeService;
// use App\Services\Api\Ynov\UserService;
// use Illuminate\Http\JsonResponse;
// use Illuminate\Http\Request;

// class UserController extends Controller
// {
//     public function __construct(
//         private UserService $userService,
//     ) {}

//     public function index(Request $request): JsonResponse
//     {
//         $query = User::query()->with(['role', 'details', 'partner', 'reseau', 'agences']);
//         $authUser = $request->user();

//         if (!$authUser->isSuperAdmin()) {
//             if ($authUser->partner_uuid) {
//                 $query->where('partner_uuid', $authUser->partner_uuid);
//             }
//             if ($authUser->reseau_uuid) {
//                 $query->where('reseau_uuid', $authUser->reseau_uuid);
//             }

//             // Si l'utilisateur n'a ni portée partenaire ni portée réseau,
//             // on restreint sa visibilité aux utilisateurs partageant au
//             // moins une de ses agences (ex : gestionnaire d'agence).
//             if (!$authUser->partner_uuid && !$authUser->reseau_uuid) {
//                 $agenceUuids = $authUser->agences()->pluck('agences.uuid_agence');
//                 if ($agenceUuids->isNotEmpty()) {
//                     $query->whereHas('agences', function ($q) use ($agenceUuids) {
//                         $q->whereIn('agences.uuid_agence', $agenceUuids);
//                     });
//                 } else {
//                     // Aucune agence rattachée : ne renvoyer que l'utilisateur lui-même
//                     $query->where('uuid_user', $authUser->uuid_user);
//                 }
//             }
//         }

//         $users = $query->orderByDesc('created_at')->paginate($request->integer('per_page', 20));

//         return response()->json([
//             'success' => true,
//             'data' => UserResource::collection($users),
//         ]);
//     }

//     public function store(StoreUserRequest $request): JsonResponse
//     {
//         $user = $this->userService->create(
//             $request->validated(),
//             $request->user()->details?->uuid_user_details
//         );

//         // Log l'action
//         ActivityLog::log([
//             'user_uuid' => $request->user()->uuid_user,
//             'action' => 'create',
//             'action_type' => 'crud',
//             'module' => 'users',
//             'description' => "Création de l'utilisateur : {$user->email}",
//             'resource_type' => 'user',
//             'resource_id' => $user->uuid_user,
//             'new_values' => $user->toArray(),
//             'level' => 'info',
//         ]);

//         return response()->json([
//             'success' => true,
//             'message' => 'Utilisateur créé.',
//             'data' => new UserResource($user->load('details')),
//         ], 201);
//     }

//     public function show($uuid_user): JsonResponse
//     {
//         $user = User::where('uuid_user', $uuid_user)->firstOrFail();
//         return response()->json([
//             'success' => true,
//             'data' => new UserResource($user->load([
//                 'role.permissions.group',
//                 'details',
//                 'partner',
//                 'reseau',
//                 'agences',
//                 'groupNotifs'
//             ])),
//         ]);
//     }

//     public function update(UpdateUserRequest $request, string $uuid_user): JsonResponse
//     {
//         $user = User::where('uuid_user', $uuid_user)->firstOrFail();
//         $updated = $this->userService->update(
//             $user,
//             $request->validated(),
//             $request->user()->details?->uuid_user_details
//         );

//         // Log l'action
//         ActivityLog::log([
//             'user_uuid' => $request->user()->uuid_user,
//             'action' => 'update',
//             'action_type' => 'crud',
//             'module' => 'users',
//             'description' => "Mise à jour de l'utilisateur : {$user->email}",
//             'resource_type' => 'user',
//             'resource_id' => $user->uuid_user,
//             'old_values' => $user->toArray(),
//             'new_values' => $updated->toArray(),
//             'level' => 'info',
//         ]);
//         return response()->json([
//             'success' => true,
//             'message' => 'Utilisateur mis à jour.',
//             'data' => new UserResource($updated->load('details')),
//         ]);
//     }

//     public function destroy(Request $request, string $uuid_user): JsonResponse
//     {
//         $user = User::where('uuid_user', $uuid_user)->firstOrFail();
//         $this->userService->delete($user, $request->user()->details?->uuid_user_details);
//         ActivityLog::log([
//             'user_uuid' => $request->user()->uuid_user,
//             'action' => 'delete',
//             'action_type' => 'crud',
//             'module' => 'users',
//             'description' => "Suppression de l'utilisateur : {$user->email}",
//             'resource_type' => 'user',
//             'resource_id' => $user->uuid_user,
//             'level' => 'info',
//         ]);
//         return response()->json(['success' => true, 'message' => 'Utilisateur supprimé.']);
//     }

//     public function block(BlockUserRequest $request, string $uuid_user): JsonResponse
//     {
//         $user = User::where('uuid_user', $uuid_user)->firstOrFail();
//         $this->userService->block($user, $request->reason, $request->user()->uuid_user);
//         ActivityLog::log([
//             'user_uuid' => $request->user()->uuid_user,
//             'action' => 'block',
//             'action_type' => 'crud',
//             'module' => 'users',
//             'description' => "Bloquage de l'utilisateur : {$user->email}",
//             'resource_type' => 'user',
//             'resource_id' => $user->uuid_user,
//             'level' => 'info',
//         ]);
//         return response()->json(['success' => true, 'message' => 'Utilisateur bloqué.']);
//     }

//     public function unblock(Request $request, string $uuid_user): JsonResponse
//     {
//         $user = User::where('uuid_user', $uuid_user)->firstOrFail();
//         $this->userService->unblock($user, $request->user()->uuid_user);
//         ActivityLog::log([
//             'user_uuid' => $request->user()->uuid_user,
//             'action' => 'unblock',
//             'action_type' => 'crud',
//             'module' => 'users',
//             'description' => "Débloquage de l'utilisateur : {$user->email}",
//             'resource_type' => 'user',
//             'resource_id' => $user->uuid_user,
//             'level' => 'info',
//         ]);
//         return response()->json(['success' => true, 'message' => 'Utilisateur débloqué.']);
//     }
// }

namespace App\Http\Controllers\Api\Ynov;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Ynov\BlockUserRequest;
use App\Http\Requests\Api\Ynov\StoreUserRequest;
use App\Http\Requests\Api\Ynov\UpdateUserRequest;
use App\Http\Resources\Api\Ynov\UserResource;
use App\Models\Api\Ynov\parameter\ActivityLog;
use App\Models\Api\Ynov\parameter\User;
use App\Services\Api\Ynov\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(
        private readonly UserService $userService
    ) {}


    
    /**
     * ================================================================
     * CORRECTION #18 : Ajout d'une Policy pour centraliser les contrôles d'accès
     * ================================================================
     * Note : La Policy UserPolicy doit être créée pour que ces vérifications
     * fonctionnent. Voir le fichier app/Policies/UserPolicy.php plus bas.
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::query()->with(['role', 'details', 'partner', 'reseau', 'agences']);
        $authUser = $request->user();

        // Scope multi-tenant (inchangé)
        if (!$authUser->isSuperAdmin()) {
            if ($authUser->partner_uuid) {
                $query->where('partner_uuid', $authUser->partner_uuid);
            }
            if ($authUser->reseau_uuid) {
                $query->where('reseau_uuid', $authUser->reseau_uuid);
            }

            if (!$authUser->partner_uuid && !$authUser->reseau_uuid) {
                $agenceUuids = $authUser->agences()->pluck('agences.uuid_agence');
                if ($agenceUuids->isNotEmpty()) {
                    $query->whereHas('agences', function ($q) use ($agenceUuids) {
                        $q->whereIn('agences.uuid_agence', $agenceUuids);
                    });
                } else {
                    $query->where('uuid_user', $authUser->uuid_user);
                }
            }
        }

        $users = $query->orderByDesc('created_at')->paginate($request->integer('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => UserResource::collection($users),
        ]);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = $this->userService->create(
            $request->validated(),
            $request->user()->details?->uuid_user_details
        );

        ActivityLog::log([
            'user_uuid' => $request->user()->uuid_user,
            'action' => 'create',
            'action_type' => 'crud',
            'module' => 'users',
            'description' => "Création de l'utilisateur : {$user->email}",
            'resource_type' => 'user',
            'resource_id' => $user->uuid_user,
            'new_values' => $user->toArray(),
            'level' => 'info',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Utilisateur créé.',
            'data' => new UserResource($user->load('details')),
        ], 201);
    }

    /**
     * ================================================================
     * CORRECTION #18 : Vérification Policy sur show
     * ================================================================
     */
    public function show(Request $request, string $uuid_user): JsonResponse
    {
        $user = User::where('uuid_user', $uuid_user)->firstOrFail();

        // ================================================================
        // VÉRIFICATION D'ACCÈS AVEC LA POLICY
        // ================================================================
        if (!$request->user()->can('view', $user)) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'avez pas accès à cet utilisateur.',
                'code' => 'ACCESS_DENIED'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => new UserResource($user->load([
                'role.permissions.group',
                'details',
                'partner',
                'reseau',
                'agences',
                'groupNotifs'
            ])),
        ]);
    }

    /**
     * ================================================================
     * CORRECTION #18 : Vérification Policy sur update
     * ================================================================
     */
    public function update(UpdateUserRequest $request, string $uuid_user): JsonResponse
    {
        $user = User::where('uuid_user', $uuid_user)->firstOrFail();

        // ================================================================
        // VÉRIFICATION D'ACCÈS AVEC LA POLICY
        // ================================================================
        if (!$request->user()->can('update', $user)) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'avez pas le droit de modifier cet utilisateur.',
                'code' => 'ACCESS_DENIED'
            ], 403);
        }

        $updated = $this->userService->update(
            $user,
            $request->validated(),
            $request->user()->details?->uuid_user_details
        );

        ActivityLog::log([
            'user_uuid' => $request->user()->uuid_user,
            'action' => 'update',
            'action_type' => 'crud',
            'module' => 'users',
            'description' => "Mise à jour de l'utilisateur : {$user->email}",
            'resource_type' => 'user',
            'resource_id' => $user->uuid_user,
            'old_values' => $user->toArray(),
            'new_values' => $updated->toArray(),
            'level' => 'info',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Utilisateur mis à jour.',
            'data' => new UserResource($updated->load('details')),
        ]);
    }

    /**
     * ================================================================
     * CORRECTION #18 : Vérification Policy sur destroy
     * ================================================================
     */
    public function destroy(Request $request, string $uuid_user): JsonResponse
    {
        $user = User::where('uuid_user', $uuid_user)->firstOrFail();

        // ================================================================
        // VÉRIFICATION D'ACCÈS AVEC LA POLICY
        // ================================================================
        if (!$request->user()->can('delete', $user)) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'avez pas le droit de supprimer cet utilisateur.',
                'code' => 'ACCESS_DENIED'
            ], 403);
        }

        $this->userService->delete($user, $request->user()->details?->uuid_user_details);

        ActivityLog::log([
            'user_uuid' => $request->user()->uuid_user,
            'action' => 'delete',
            'action_type' => 'crud',
            'module' => 'users',
            'description' => "Suppression de l'utilisateur : {$user->email}",
            'resource_type' => 'user',
            'resource_id' => $user->uuid_user,
            'level' => 'info',
        ]);

        return response()->json(['success' => true, 'message' => 'Utilisateur supprimé.']);
    }

    /**
     * ================================================================
     * CORRECTION #18 : Vérification Policy sur block
     * ================================================================
     */
    public function block(BlockUserRequest $request, string $uuid_user): JsonResponse
    {
        $user = User::where('uuid_user', $uuid_user)->firstOrFail();

        // ================================================================
        // VÉRIFICATION D'ACCÈS AVEC LA POLICY
        // ================================================================
        if (!$request->user()->can('block', $user)) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'avez pas le droit de bloquer cet utilisateur.',
                'code' => 'ACCESS_DENIED'
            ], 403);
        }

        $this->userService->block($user, $request->reason, $request->user()->uuid_user);

        ActivityLog::log([
            'user_uuid' => $request->user()->uuid_user,
            'action' => 'block',
            'action_type' => 'crud',
            'module' => 'users',
            'description' => "Bloquage de l'utilisateur : {$user->email}",
            'resource_type' => 'user',
            'resource_id' => $user->uuid_user,
            'level' => 'info',
        ]);

        return response()->json(['success' => true, 'message' => 'Utilisateur bloqué.']);
    }


    /**
     * ================================================================
     * CORRECTION #18 : Vérification Policy sur unblock
     * ================================================================
     */
    public function unblock(Request $request, string $uuid_user): JsonResponse
    {
        $user = User::where('uuid_user', $uuid_user)->firstOrFail();

        // ================================================================
        // VÉRIFICATION D'ACCÈS AVEC LA POLICY
        // ================================================================
        if (!$request->user()->can('unblock', $user)) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'avez pas le droit de débloquer cet utilisateur.',
                'code' => 'ACCESS_DENIED'
            ], 403);
        }

        $this->userService->unblock($user, $request->user()->uuid_user);

        ActivityLog::log([
            'user_uuid' => $request->user()->uuid_user,
            'action' => 'unblock',
            'action_type' => 'crud',
            'module' => 'users',
            'description' => "Débloquage de l'utilisateur : {$user->email}",
            'resource_type' => 'user',
            'resource_id' => $user->uuid_user,
            'level' => 'info',
        ]);

        return response()->json(['success' => true, 'message' => 'Utilisateur débloqué.']);
    }
}

