<?php
// app/Http/Controllers/Api/Ynov/PartnerController.php
namespace App\Http\Controllers\Api\Ynov;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Ynov\StorePartnerRequest;
use App\Http\Requests\Api\Ynov\UpdatePartnerRequest;
use App\Http\Resources\Api\Ynov\PartnerResource;
use App\Models\Api\Ynov\parameter\Partner;
use App\Services\Api\Ynov\PartnerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PartnerController extends Controller
{
    public function __construct(
        private PartnerService $partnerService
    ) {}

    /**
     * Liste des partenaires
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'status', 'is_active', 'type', 'categorie', 'code_branche', 'search', 'not_expired'
        ]);
        
        $perPage = $request->integer('per_page', 20);
        $partners = $this->partnerService->getPartners($filters, $perPage);

        return response()->json([
            'success' => true,
            'message' => 'Liste des partenaires récupérée.',
            'code' => 'PARTNERS_LISTED',
            'data' => PartnerResource::collection($partners),
            'meta' => [
                'current_page' => $partners->currentPage(),
                'per_page' => $partners->perPage(),
                'total' => $partners->total(),
                'last_page' => $partners->lastPage(),
            ]
        ]);
    }

    /**
     * Créer un partenaire
     */
    public function store(StorePartnerRequest $request): JsonResponse
    {
        $partner = $this->partnerService->create(
            $request->validated(),
            $request->user()->uuid_user
        );

        return response()->json([
            'success' => true,
            'message' => 'Partenaire créé avec succès.',
            'code' => 'PARTNER_CREATED',
            'data' => new PartnerResource($partner),
        ], 201);
    }

    /**
     * Détails d'un partenaire
     */
    public function show(string $uuid_partner): JsonResponse
    {
        $partner = Partner::where('uuid_partner', $uuid_partner)
            ->with(['reseaux', 'reseaux.agences', 'reseaux.agences.horaires', 'users'])
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'message' => 'Détails du partenaire.',
            'code' => 'PARTNER_FOUND',
            'data' => new PartnerResource($partner),
        ]);
    }

    /**
     * Mettre à jour un partenaire
     */
    public function update(UpdatePartnerRequest $request, string $uuid_partner): JsonResponse
    {
        $partner = Partner::where('uuid_partner', $uuid_partner)->firstOrFail();
        
        $updated = $this->partnerService->update(
            $partner,
            $request->validated(),
            $request->user()->uuid_user
        );

        return response()->json([
            'success' => true,
            'message' => 'Partenaire mis à jour avec succès.',
            'code' => 'PARTNER_UPDATED',
            'data' => new PartnerResource($updated),
        ]);
    }

    /**
     * Supprimer un partenaire
     */
    public function destroy(Request $request, string $uuid_partner): JsonResponse
    {
        $partner = Partner::where('uuid_partner', $uuid_partner)->firstOrFail();
        
        try {
            $this->partnerService->delete($partner, $request->user()->uuid_user);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'code' => 'PARTNER_HAS_RESEAVX',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Partenaire supprimé avec succès.',
            'code' => 'PARTNER_DELETED',
        ]);
    }

    /**
     * Récupérer les réseaux d'un partenaire
     */
    public function reseaux(string $uuid_partner): JsonResponse
    {
        $partner = Partner::where('uuid_partner', $uuid_partner)->firstOrFail();
        $reseaux = $partner->reseaux()->with('agences')->get();

        return response()->json([
            'success' => true,
            'message' => 'Réseaux du partenaire récupérés.',
            'code' => 'PARTNER_RESEAUX_LISTED',
            'data' => $reseaux,
        ]);
    }
}