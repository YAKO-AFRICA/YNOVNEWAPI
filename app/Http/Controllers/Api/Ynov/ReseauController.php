<?php
// app/Http/Controllers/Api/Ynov/ReseauController.php
namespace App\Http\Controllers\Api\Ynov;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Ynov\StoreReseauRequest;
use App\Http\Requests\Api\Ynov\UpdateReseauRequest;
use App\Http\Resources\Api\Ynov\ReseauResource;
use App\Models\Api\Ynov\parameter\Reseau;
use App\Services\Api\Ynov\ReseauService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ReseauController extends Controller
{
    public function __construct(
        private ReseauService $reseauService
    ) {}

    /**
     * Liste des réseaux
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'status', 'partner_uuid', 'search'
        ]);
        
        $perPage = $request->integer('per_page', 20);
        $reseaux = $this->reseauService->getReseaux($filters, $perPage);

        return response()->json([
            'success' => true,
            'message' => 'Liste des réseaux récupérée.',
            'code' => 'RESEAUX_LISTED',
            'data' => ReseauResource::collection($reseaux),
            'meta' => [
                'current_page' => $reseaux->currentPage(),
                'per_page' => $reseaux->perPage(),
                'total' => $reseaux->total(),
                'last_page' => $reseaux->lastPage(),
            ]
        ]);
    }

    /**
     * Créer un réseau
     */
    public function store(StoreReseauRequest $request): JsonResponse
    {
        $reseau = $this->reseauService->create(
            $request->validated(),
            $request->user()->uuid_user
        );

        return response()->json([
            'success' => true,
            'message' => 'Réseau créé avec succès.',
            'code' => 'RESEAU_CREATED',
            'data' => new ReseauResource($reseau->load('partner')),
        ], 201);
    }

    /**
     * Détails d'un réseau
     */
    public function show(string $uuid_reseau): JsonResponse
    {
        $reseau = Reseau::where('uuid_reseau', $uuid_reseau)
            ->with(['partner', 'agences', 'agences.horaires', 'users'])
            ->firstOrFail();

        Log::info("Found reseau: " . json_encode($reseau->toArray()));

        return response()->json([
            'success' => true,
            'message' => 'Détails du réseau.',
            'code' => 'RESEAU_FOUND',
            'data' => new ReseauResource($reseau),
        ]);
    }

    /**
     * Mettre à jour un réseau
     */
    public function update(UpdateReseauRequest $request, string $uuid_reseau): JsonResponse
    {

        Log::info("Updating reseau with UUID: $uuid_reseau");
        $reseau = Reseau::where('uuid_reseau', $uuid_reseau)->first();

         if (!$reseau) {
            return response()->json([
                'success' => false,
                'message' => ' uuid renseigné n\'existe pas pour un reseau . Réseau non trouvé.',
                'code' => 'RESEAU_NOT_FOUND',
            ], 404);
        }
        
        $updated = $this->reseauService->update(
            $reseau,
            $request->validated(),
            $request->user()->uuid_user
        );

        return response()->json([
            'success' => true,
            'message' => 'Réseau mis à jour avec succès.',
            'code' => 'RESEAU_UPDATED',
            'data' => new ReseauResource($updated->load('partner')),
        ]);
    }

    /**
     * Supprimer un réseau
     */
    public function destroy(Request $request, string $uuid_reseau): JsonResponse
    {
        $reseau = Reseau::where('uuid_reseau', $uuid_reseau)->firstOrFail();
        
        try {
            $this->reseauService->delete($reseau, $request->user()->uuid_user);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'code' => 'RESEAU_HAS_AGENCES',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Réseau supprimé avec succès.',
            'code' => 'RESEAU_DELETED',
        ]);
    }

    /**
     * Récupérer les agences d'un réseau
     */
    public function agences(string $uuid_reseau): JsonResponse
    {
        $reseau = Reseau::where('uuid_reseau', $uuid_reseau)->firstOrFail();
        $agences = $reseau->agences()->with('horaires')->get();

        return response()->json([
            'success' => true,
            'message' => 'Agences du réseau récupérées.',
            'code' => 'RESEAU_AGENCES_LISTED',
            'data' => $agences,
        ]);
    }
}