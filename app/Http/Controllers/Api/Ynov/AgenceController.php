<?php
// app/Http/Controllers/Api/Ynov/AgenceController.php
namespace App\Http\Controllers\Api\Ynov;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Ynov\StoreAgenceRequest;
use App\Http\Requests\Api\Ynov\UpdateAgenceRequest;
use App\Http\Resources\Api\Ynov\AgenceResource;
use App\Models\Api\Ynov\parameter\ActivityLog;
use App\Models\Api\Ynov\parameter\Agence;
use App\Models\Api\Ynov\parameter\User;
use App\Services\Api\Ynov\AgenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AgenceController extends Controller
{
    public function __construct(
        private AgenceService $agenceService
    ) {}

    /**
     * Liste des agences
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'status', 'reseau_uuid', 'ville', 'quartier', 'search', 'open_now',
            'latitude', 'longitude', 'radius'
        ]);
        
        $perPage = $request->integer('per_page', 20);
        $agences = $this->agenceService->getAgences($filters, $perPage);

        return response()->json([
            'success' => true,
            'message' => 'Liste des agences récupérée.',
            'code' => 'AGENCES_LISTED',
            'data' => AgenceResource::collection($agences),
            'meta' => [
                'current_page' => $agences->currentPage(),
                'per_page' => $agences->perPage(),
                'total' => $agences->total(),
                'last_page' => $agences->lastPage(),
            ]
        ]);
    }

    /**
     * Créer une agence
     */
    public function store(StoreAgenceRequest $request): JsonResponse
    {
        $agence = $this->agenceService->create(
            $request->validated(),
            $request->user()->uuid_user
        );

        return response()->json([
            'success' => true,
            'message' => 'Agence créée avec succès.',
            'code' => 'AGENCE_CREATED',
            'data' => new AgenceResource($agence->load(['reseau', 'horaires'])),
        ], 201);
    }

    /**
     * Détails d'une agence
     */
    public function show(string $uuid_agence): JsonResponse
    {
        $agence = Agence::where('uuid_agence', $uuid_agence)
            ->with(['reseau', 'horaires', 'users'])
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'message' => 'Détails de l\'agence.',
            'code' => 'AGENCE_FOUND',
            'data' => new AgenceResource($agence),
        ]);
    }

    /**
     * Mettre à jour une agence
     */
    public function update(UpdateAgenceRequest $request, string $uuid_agence): JsonResponse
    {
        $agence = Agence::where('uuid_agence', $uuid_agence)->firstOrFail();
        
        $updated = $this->agenceService->update(
            $agence,
            $request->validated(),
            $request->user()->uuid_user
        );

        return response()->json([
            'success' => true,
            'message' => 'Agence mise à jour avec succès.',
            'code' => 'AGENCE_UPDATED',
            'data' => new AgenceResource($updated->load(['reseau', 'horaires'])),
        ]);
    }

    /**
     * Supprimer une agence
     */
    public function destroy(Request $request, string $uuid_agence): JsonResponse
    {
        $agence = Agence::where('uuid_agence', $uuid_agence)->firstOrFail();
        
        // Vérifier si l'agence a des utilisateurs
        if ($agence->users()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cette agence est associée à des utilisateurs et ne peut pas être supprimée.',
                'code' => 'AGENCE_HAS_USERS',
            ], 422);
        }

        $this->agenceService->delete($agence, $request->user()->uuid_user);

        return response()->json([
            'success' => true,
            'message' => 'Agence supprimée avec succès.',
            'code' => 'AGENCE_DELETED',
        ]);
    }

    /**
     * Assigner des utilisateurs à une agence
     */
    public function assignUsers(Request $request, string $uuid_agence): JsonResponse
    {
        $request->validate([
            'user_uuids' => ['required', 'array', 'min:1'],
            'user_uuids.*' => ['exists:users,uuid_user'],
            'is_primary' => ['boolean'],
        ]);

        $agence = Agence::where('uuid_agence', $uuid_agence)->firstOrFail();
        
        $syncData = [];
        foreach ($request->user_uuids as $userUuid) {
            $syncData[$userUuid] = [
                'uuid_user_agence' => (string) Str::uuid(),
                'is_primary' => $request->boolean('is_primary', false),
                'is_active' => true,
                'assigned_at' => now(),
                'assigned_by' => $request->user()->uuid_user,
            ];
        }

        $agence->users()->syncWithoutDetaching($syncData);

        return response()->json([
            'success' => true,
            'message' => 'Utilisateurs assignés avec succès.',
            'code' => 'USERS_ASSIGNED',
            'data' => $agence->load('users'),
        ]);
    }

    /**
     * Désassigner un utilisateur d'une agence
     */
    public function removeUser(Request $request, string $uuid_agence, string $uuid_user): JsonResponse
    {
        $agence = Agence::where('uuid_agence', $uuid_agence)->firstOrFail();
        $user = User::where('uuid_user', $uuid_user)->firstOrFail();

        $agence->users()->detach($uuid_user);

        return response()->json([
            'success' => true,
            'message' => 'Utilisateur retiré de l\'agence avec succès.',
            'code' => 'USER_REMOVED',
        ]);
    }

    /**
     * Récupérer les agences proches (géolocalisation)
     */
    public function nearby(Request $request): JsonResponse
    {
        $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'radius' => ['nullable', 'numeric', 'min:1', 'max:100'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $filters = [
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'radius' => $request->radius ?? 10,
            'status' => 'actif',
        ];
        
        $limit = $request->integer('limit', 20);
        $agences = $this->agenceService->getAgences($filters, $limit);

        return response()->json([
            'success' => true,
            'message' => 'Agences à proximité récupérées.',
            'code' => 'NEARBY_AGENCES',
            'data' => AgenceResource::collection($agences),
        ]);
    }

    /**
     * Récupérer les horaires d'une agence
     */
    public function horaires(string $uuid_agence): JsonResponse
    {
        $agence = Agence::where('uuid_agence', $uuid_agence)->firstOrFail();
        $horaires = $agence->horaires()->get();

        return response()->json([
            'success' => true,
            'message' => 'Horaires de l\'agence récupérés.',
            'code' => 'AGENCE_HORAIRES_LISTED',
            'data' => $horaires,
        ]);
    }
}