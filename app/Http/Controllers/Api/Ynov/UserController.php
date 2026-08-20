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
        private UserService $userService,
    ) {}


    /**
     * @OA\Get(
     *     path="/users",
     *     operationId="usersIndex",
     *     tags={"Users"},
     *     summary="Lister les utilisateurs (portée selon le contexte de l'appelant)",
     *     description="Protégé par la permission `users.afficher`. La visibilité des résultats dépend du profil de l'appelant :
        - Super Admin : voit tous les utilisateurs sans restriction.
        - Utilisateur rattaché à un partenaire (`partner_uuid`) : voit uniquement les utilisateurs du même partenaire.
        - Utilisateur rattaché à un réseau (`reseau_uuid`) : voit uniquement les utilisateurs du même réseau.
        - Utilisateur sans portée partenaire ni réseau mais rattaché à une ou plusieurs agences : voit uniquement les utilisateurs partageant au moins une de ses agences.
        - Utilisateur sans aucune portée (ni partenaire, ni réseau, ni agence) : ne voit que lui-même.

        Chargé avec les relations role, details, partner, reseau, agences. Trié par date de création décroissante, paginé.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="integer", default=20, minimum=1),
     *         example=20
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Liste paginée des utilisateurs visibles par l'appelant.",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 description="Pagination Laravel standard enveloppant UserResource::collection().",
     *                 @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/User")),
     *                 @OA\Property(property="current_page", type="integer"),
     *                 @OA\Property(property="last_page", type="integer"),
     *                 @OA\Property(property="per_page", type="integer"),
     *                 @OA\Property(property="total", type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non authentifié.", @OA\JsonContent(ref="#/components/schemas/UnauthorizedErrorResponse")),
     *     @OA\Response(response=403, description="Permission 'users.afficher' manquante.", @OA\JsonContent(ref="#/components/schemas/PermissionDeniedResponse"))
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/users",
     *     operationId="usersStore",
     *     tags={"Users"},
     *     summary="Créer un utilisateur (interne, partenaire ou admin)",
     *     description="Protégé par la permission `users.creer`. Contrairement à /auth/register (client public), cet endpoint permet de créer tout type d'utilisateur avec assignation explicite du rôle. Crée en transaction l'utilisateur ET son UserDetails associé. Attache optionnellement une agence principale (is_primary=true). Envoie WelcomeMail. Le mot de passe expire après 90 jours et is_first_login=true (déclenchera le flux de changement de mot de passe obligatoire à la première connexion).",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","password","role_uuid","user_type","nom","prenoms"},
     *             @OA\Property(property="email", type="string", format="email", example="agent.commercial@yako-africa.ci", description="Doit être unique."),
     *             @OA\Property(property="login", type="string", maxLength=100, nullable=true, description="Doit être unique si fourni."),
     *             @OA\Property(property="password", type="string", format="password", minLength=12, example="Passw0rd#Init!", description="Complexité renforcée (Password::min(12)->mixedCase()->numbers()->symbols())."),
     *             @OA\Property(property="role_uuid", type="string", format="uuid", description="Doit correspondre à un rôle existant."),
     *             @OA\Property(property="user_type", type="string", enum={"client","user_interne","user_partner","admin"}),
     *             @OA\Property(property="partner_uuid", type="string", format="uuid", nullable=true),
     *             @OA\Property(property="reseau_uuid", type="string", format="uuid", nullable=true),
     *             @OA\Property(property="agence_uuid", type="string", format="uuid", nullable=true, description="Si fourni, l'utilisateur est attaché à cette agence en tant qu'agence principale (is_primary=true)."),
     *             @OA\Property(property="nom", type="string", maxLength=55, example="Kouassi"),
     *             @OA\Property(property="prenoms", type="string", maxLength=255, example="Awa"),
     *             @OA\Property(property="fonction", type="string", maxLength=55, nullable=true),
     *             @OA\Property(property="mobile_1", type="string", maxLength=25, nullable=true),
     *             @OA\Property(property="genre", type="string", enum={"M","F"}, nullable=true),
     *             @OA\Property(property="civilite", type="string", maxLength=20, nullable=true),
     *             @OA\Property(property="date_naissance", type="string", format="date", nullable=true),
     *             @OA\Property(property="lieu_naissance", type="string", maxLength=55, nullable=true),
     *             @OA\Property(property="lieu_residence", type="string", maxLength=255, nullable=true),
     *             @OA\Property(property="photo", type="string", format="binary", nullable=true, description="Upload d'image (max 2Mo) — contrairement à PUT /profile qui attend une string.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Utilisateur créé.",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Utilisateur créé."),
     *             @OA\Property(property="data", ref="#/components/schemas/User")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non authentifié.", @OA\JsonContent(ref="#/components/schemas/UnauthorizedErrorResponse")),
     *     @OA\Response(response=403, description="Permission 'users.creer' manquante.", @OA\JsonContent(ref="#/components/schemas/PermissionDeniedResponse")),
     *     @OA\Response(response=422, description="Email/login déjà utilisé, rôle inexistant, ou échec de validation.", @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse"))
     * )
     */
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
     * @OA\Get(
     *     path="/users/{uuid_user}",
     *     operationId="usersShow",
     *     tags={"Users"},
     *     summary="Afficher le détail d'un utilisateur",
     *     description="Protégé par la permission `users.afficher`. **Aucune vérification de portée** (contrairement à /users index) — n'importe quel utilisateur disposant de la permission peut consulter le détail de n'importe quel autre utilisateur par UUID, même en dehors de son partenaire/réseau/agence. À signaler comme incohérence potentielle avec la logique de scoping appliquée sur l'index.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="uuid_user", in="path", required=true, @OA\Schema(type="string", format="uuid")),
     *     @OA\Response(
     *         response=200,
     *         description="Détail complet de l'utilisateur.",
     *         @OA\JsonContent(@OA\Property(property="success", type="boolean", example=true), @OA\Property(property="data", ref="#/components/schemas/User"))
     *     ),
     *     @OA\Response(response=401, description="Non authentifié.", @OA\JsonContent(ref="#/components/schemas/UnauthorizedErrorResponse")),
     *     @OA\Response(response=403, description="Permission 'users.afficher' manquante.", @OA\JsonContent(ref="#/components/schemas/PermissionDeniedResponse")),
     *     @OA\Response(response=404, description="Utilisateur non trouvé (firstOrFail).", @OA\JsonContent(@OA\Property(property="message", type="string", example="Not Found")))
     * )
     */
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
     * @OA\Put(
     *     path="/users/{uuid_user}",
     *     operationId="usersUpdate",
     *     tags={"Users"},
     *     summary="Mettre à jour un utilisateur (administration)",
     *     description="Protégé par la permission `users.modifier`. Tous les champs sont 'sometimes'. Permet de modifier le rôle, le type, le statut et le rattachement organisationnel — contrairement à PUT /profile réservé à l'auto-modification limitée.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="uuid_user", in="path", required=true, @OA\Schema(type="string", format="uuid")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="login", type="string", maxLength=100, nullable=true),
     *             @OA\Property(property="role_uuid", type="string", format="uuid"),
     *             @OA\Property(property="user_type", type="string", enum={"client","user_interne","user_partner","admin"}),
     *             @OA\Property(property="partner_uuid", type="string", format="uuid", nullable=true),
     *             @OA\Property(property="reseau_uuid", type="string", format="uuid", nullable=true),
     *             @OA\Property(property="status", type="string", enum={"actif","inactif","gele","bloque"}, description="Ne permet PAS de fixer 'suspendu' via cet endpoint, bien que cette valeur existe dans l'enum de la table users."),
     *             @OA\Property(property="nom", type="string", maxLength=55),
     *             @OA\Property(property="prenoms", type="string", maxLength=255),
     *             @OA\Property(property="fonction", type="string", maxLength=55, nullable=true),
     *             @OA\Property(property="mobile_1", type="string", maxLength=25, nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Utilisateur mis à jour.",
     *         @OA\JsonContent(@OA\Property(property="success", type="boolean", example=true), @OA\Property(property="message", type="string", example="Utilisateur mis à jour."), @OA\Property(property="data", ref="#/components/schemas/User"))
     *     ),
     *     @OA\Response(response=401, description="Non authentifié.", @OA\JsonContent(ref="#/components/schemas/UnauthorizedErrorResponse")),
     *     @OA\Response(response=403, description="Permission 'users.modifier' manquante.", @OA\JsonContent(ref="#/components/schemas/PermissionDeniedResponse")),
     *     @OA\Response(response=404, description="Utilisateur non trouvé.", @OA\JsonContent(@OA\Property(property="message", type="string", example="Not Found"))),
     *     @OA\Response(response=422, description="Email/login déjà utilisé par un autre compte, ou échec de validation.", @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse"))
     * )
     */
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
     * @OA\Delete(
     *     path="/users/{uuid_user}",
     *     operationId="usersDestroy",
     *     tags={"Users"},
     *     summary="Supprimer (désactiver) un utilisateur",
     *     description="Protégé par la permission `users.supprimer`. **Ne s'agit pas d'une suppression physique** : passe le statut à 'inactif', enregistre deleted_by, applique le soft delete (deleted_at), et révoque tous les tokens Sanctum actifs de l'utilisateur.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="uuid_user", in="path", required=true, @OA\Schema(type="string", format="uuid")),
     *     @OA\Response(
     *         response=200,
     *         description="Utilisateur désactivé/supprimé logiquement.",
     *         @OA\JsonContent(@OA\Property(property="success", type="boolean", example=true), @OA\Property(property="message", type="string", example="Utilisateur supprimé."))
     *     ),
     *     @OA\Response(response=401, description="Non authentifié.", @OA\JsonContent(ref="#/components/schemas/UnauthorizedErrorResponse")),
     *     @OA\Response(response=403, description="Permission 'users.supprimer' manquante.", @OA\JsonContent(ref="#/components/schemas/PermissionDeniedResponse")),
     *     @OA\Response(response=404, description="Utilisateur non trouvé.", @OA\JsonContent(@OA\Property(property="message", type="string", example="Not Found")))
     * )
     */
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
     * @OA\Post(
     *     path="/users/{uuid_user}/block",
     *     operationId="usersBlock",
     *     tags={"Users"},
     *     summary="Bloquer un utilisateur",
     *     description="Protégé par la permission `users.bloquer`. Passe le statut à 'bloque', enregistre le motif et l'auteur, révoque tous les tokens actifs, envoie AccountBlockedMail. Différent du gel (freeze) : le blocage est permanent jusqu'à déblocage manuel explicite, sans expiration automatique.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="uuid_user", in="path", required=true, @OA\Schema(type="string", format="uuid")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(required={"reason"}, @OA\Property(property="reason", type="string", maxLength=500, example="Comportement suspect détecté par l'équipe sécurité."))
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Utilisateur bloqué.",
     *         @OA\JsonContent(@OA\Property(property="success", type="boolean", example=true), @OA\Property(property="message", type="string", example="Utilisateur bloqué."))
     *     ),
     *     @OA\Response(response=401, description="Non authentifié.", @OA\JsonContent(ref="#/components/schemas/UnauthorizedErrorResponse")),
     *     @OA\Response(response=403, description="Permission 'users.bloquer' manquante.", @OA\JsonContent(ref="#/components/schemas/PermissionDeniedResponse")),
     *     @OA\Response(response=404, description="Utilisateur non trouvé.", @OA\JsonContent(@OA\Property(property="message", type="string", example="Not Found"))),
     *     @OA\Response(response=422, description="Motif manquant ou trop long.", @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse"))
     * )
     */
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
     * @OA\Post(
     *     path="/users/{uuid_user}/unblock",
     *     operationId="usersUnblock",
     *     tags={"Users"},
     *     summary="Débloquer un utilisateur",
     *     description="Protégé par la permission `users.bloquer` (même permission que le blocage — pas de permission distincte 'users.debloquer'). Réinitialise le statut à 'actif', efface les informations de blocage et de gel, réinitialise le compteur d'échecs, envoie AccountUnblockedMail.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="uuid_user", in="path", required=true, @OA\Schema(type="string", format="uuid")),
     *     @OA\Response(
     *         response=200,
     *         description="Utilisateur débloqué.",
     *         @OA\JsonContent(@OA\Property(property="success", type="boolean", example=true), @OA\Property(property="message", type="string", example="Utilisateur débloqué."))
     *     ),
     *     @OA\Response(response=401, description="Non authentifié.", @OA\JsonContent(ref="#/components/schemas/UnauthorizedErrorResponse")),
     *     @OA\Response(response=403, description="Permission 'users.bloquer' manquante.", @OA\JsonContent(ref="#/components/schemas/PermissionDeniedResponse")),
     *     @OA\Response(response=404, description="Utilisateur non trouvé.", @OA\JsonContent(@OA\Property(property="message", type="string", example="Not Found")))
     * )
     */
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

