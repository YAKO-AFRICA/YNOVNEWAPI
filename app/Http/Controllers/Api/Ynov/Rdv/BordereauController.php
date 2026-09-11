<?php

namespace App\Http\Controllers\Api\Ynov\Rdv;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Ynov\BordereauRdvResource;
use App\Http\Resources\Api\Ynov\DetailBordereauRdvResource;
use App\Services\Api\Ynov\BordereauService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BordereauController extends Controller
{
    public function __construct(
        private BordereauService $bordereauService
    ) {}

    /**
     * Liste des lots de bordereau avec pagination et filtres.
     */
    public function indexLots(Request $request): JsonResponse
    {
        $filters = $request->only([
            'search',
            'status',
            'date',
            'date_debut',
            'date_fin',
            'reference',
            'periode_1',
            'periode_2',
            'agence_uuid',
            'gestionnaire_uuid',
            'motif_uuid',
            'sort_by',
            'sort_order',
            'per_page',
        ]);

        $lots = $this->bordereauService->listLots($filters);

        return response()->json([
            'success' => true,
            'message' => 'Lots de bordereau récupérés avec succès.',
            'code' => 'BORDEAU_LOTS_LISTED',
            'data' => BordereauRdvResource::collection($lots),
            'meta' => [
                'current_page' => $lots->currentPage(),
                'per_page' => $lots->perPage(),
                'total' => $lots->total(),
                'last_page' => $lots->lastPage(),
                'filters' => $filters,
            ],
            'filters_disponibles' => $this->getLotFiltersAvailable(),
        ]);
    }

    /**
     * Liste des lignes de détail de bordereau avec pagination et filtres.
     * En passant bordereau_rdv_uuid, on récupère le détail complet du lot.
     */
    public function indexDetails(Request $request): JsonResponse
    {
        $filters = $request->only([
            'search',
            'status',
            'date',
            'date_debut',
            'date_fin',
            'bordereau_rdv_uuid',
            'rdv_uuid',
            'agence_uuid',
            'gestionnaire_uuid',
            'motif_uuid',
            'sort_by',
            'sort_order',
            'per_page',
        ]);

        $user = $request->user();
        if ($user && method_exists($user, 'hasRole') && $user->hasRole('gestionnaire_rdv')) {
            $filters['gestionnaire_uuid'] = $user->uuid_user;
            $filters['status'] = 'transmis';
        }

        $details = $this->bordereauService->listDetails($filters);

        return response()->json([
            'success' => true,
            'message' => 'Lignes de bordereau récupérées avec succès.',
            'code' => 'BORDEAU_DETAILS_LISTED',
            'data' => DetailBordereauRdvResource::collection($details),
            'meta' => [
                'current_page' => $details->currentPage(),
                'per_page' => $details->perPage(),
                'total' => $details->total(),
                'last_page' => $details->lastPage(),
                'filters' => $filters,
            ],
            'filters_disponibles' => $this->getDetailFiltersAvailable(),
        ]);
    }

    private function getLotFiltersAvailable(): array
    {
        return [
            'status' => [
                'en_attente' => 'En attente',
                'transfere' => 'Transféré',
                'cloture' => 'Clôturé',
            ],
            'sort_by' => [
                'reference' => 'Référence',
                'periode_1' => 'Période début',
                'periode_2' => 'Période fin',
                'status' => 'Statut',
                'created_at' => 'Date de création',
            ],
            'sort_order' => [
                'asc' => 'Croissant',
                'desc' => 'Décroissant',
            ],
        ];
    }

    private function getDetailFiltersAvailable(): array
    {
        return [
            'status' => [
                'en_attente' => 'En attente',
                'transmis' => 'Transmis',
                'traite' => 'Traité',
                'annule' => 'Annulé',
                'rejete' => 'Rejeté',
                'reporte' => 'Reporté',
                'expire' => 'Expiré',
            ],
            'sort_by' => [
                'rdv.status' => 'Statut RDV',
                'created_at' => 'Date de création',
                'rdv.date_rdv_souhaitee' => 'Date RDV souhaitée',
                'rdv.date_rdv_effective' => 'Date RDV effective',
            ],
            'sort_order' => [
                'asc' => 'Croissant',
                'desc' => 'Décroissant',
            ],
        ];
    }
}
