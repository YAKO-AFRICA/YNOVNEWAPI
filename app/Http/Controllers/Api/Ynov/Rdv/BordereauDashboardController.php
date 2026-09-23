<?php

namespace App\Http\Controllers\Api\Ynov\Rdv;

use App\Http\Controllers\Controller;
use App\Models\Api\Ynov\BordereauRdv;
use App\Services\Api\Ynov\Rdv\BordereauDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BordereauDashboardController extends Controller
{
    public function __construct(
        private BordereauDashboardService $service
    ) {}

    /**
     * Dashboard de bordereau RDV.
     */
    public function dashboard(Request $request): JsonResponse
    {
        $filters = $request->only([
            'status',
            'search',
            'date_debut',
            'date_fin',
            'per_page',
            'sort_by',
            'sort_order',
        ]);

        $overview = $this->service->getOverview($filters);
        $recentLots = $this->service->getLots($filters, (int) ($request->per_page ?? 10));

        return response()->json([
            'success' => true,
            'message' => 'Dashboard bordereau récupéré avec succès.',
            'code' => 'BORDEAU_DASHBOARD',
            'data' => [
                'overview' => $overview,
                'recent_lots' => $recentLots->items(),
            ],
            'meta' => [
                'current_page' => $recentLots->currentPage(),
                'per_page' => $recentLots->perPage(),
                'total' => $recentLots->total(),
                'last_page' => $recentLots->lastPage(),
                'filters' => $filters,
                'generated_at' => now()->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    /**
     * Statistiques globales du dashboard bordereau.
     */
    public function stats(Request $request): JsonResponse
    {
        $filters = $request->only([
            'status',
            'search',
            'date_debut',
            'date_fin',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Statistiques bordereau récupérées avec succès.',
            'code' => 'BORDEAU_DASHBOARD_STATS',
            'data' => $this->service->getOverview($filters),
        ]);
    }

    /**
     * Liste paginée des lots de bordereau.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'status',
            'search',
            'date_debut',
            'date_fin',
            'per_page',
            'sort_by',
            'sort_order',
        ]);

        $lots = $this->service->getLots($filters, (int) ($request->per_page ?? 15));

        return response()->json([
            'success' => true,
            'message' => 'Lots de bordereau récupérés avec succès.',
            'code' => 'BORDEAU_LOTS_LISTED',
            'data' => $lots->items(),
            'meta' => [
                'current_page' => $lots->currentPage(),
                'per_page' => $lots->perPage(),
                'total' => $lots->total(),
                'last_page' => $lots->lastPage(),
                'filters' => $filters,
            ],
        ]);
    }

    /**
     * Détail d'un lot avec ses détails associés.
     */
    public function show(string $uuid_bordereau): JsonResponse
    {
        $lot = BordereauRdv::with('details')
            ->where('uuid_bordereau_rdv', $uuid_bordereau)
            ->first();

        if (!$lot) {
            return response()->json([
                'success' => false,
                'message' => 'Bordereau introuvable.',
                'code' => 'BORDEAU_NOT_FOUND',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Détail du bordereau récupéré avec succès.',
            'code' => 'BORDEAU_DETAIL',
            'data' => [
                'uuid_bordereau_rdv' => $lot->uuid_bordereau_rdv,
                'reference' => $lot->reference,
                'periode_1' => $lot->periode_1?->format('Y-m-d'),
                'periode_2' => $lot->periode_2?->format('Y-m-d'),
                'status' => $lot->status,
                'status_label' => $this->statusLabel($lot->status),
                'observation' => $lot->observation,
                'details_count' => $lot->details()->count(),
                'details' => $lot->details->map(function ($detail) {
                    return [
                        'uuid_detail_bordereau_rdv' => $detail->uuid_detail_bordereau_rdv,
                        'rdv_uuid' => $detail->rdv_uuid,
                        'status' => $detail->status,
                        'status_label' => $this->statusLabel($detail->status),
                        'date_effet' => $detail->date_effet?->format('Y-m-d'),
                        'date_echeance' => $detail->date_echeance?->format('Y-m-d'),
                        'duree_contrat' => $detail->duree_contrat,
                        'type_operation' => $detail->type_operation,
                        'produit' => $detail->produit,
                        'cumul_rachats_partiels' => $detail->cumul_rachats_partiels,
                        'cumul_avances' => $detail->cumul_avances,
                        'provision_nette' => $detail->provision_nette,
                        'valeur_rachat' => $detail->valeur_rachat,
                        'valeur_max_rachat' => $detail->valeur_max_rachat,
                        'valeur_max_avance' => $detail->valeur_max_avance,
                        'montant_transformation' => $detail->montant_transformation,
                        'garantie_surete' => $detail->garantie_surete,
                        'conservation_capital' => $detail->conservation_capital,
                        'observation' => $detail->observation,
                        'soumis_a_gestionnaire_prestation_uuid' => $detail->soumis_a_gestionnaire_prestation_uuid,
                        'created_at' => $detail->created_at?->format('Y-m-d H:i:s'),
                        'updated_at' => $detail->updated_at?->format('Y-m-d H:i:s'),
                    ];
                })->values(),
                'created_at' => $lot->created_at?->format('Y-m-d H:i:s'),
                'updated_at' => $lot->updated_at?->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    private function statusLabel(?string $status): ?string
    {
        return match ($status) {
            'en_attente' => 'En attente',
            'soumis' => 'Soumis',
            'traite' => 'Traité',
            'transfere' => 'Transféré',
            'cloture' => 'Clôturé',
            'annule' => 'Annulé',
            'rejete' => 'Rejeté',
            'reporte' => 'Reporté',
            'expire' => 'Expiré',
            default => $status,
        };
    }
}
