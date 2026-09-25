<?php

namespace App\Http\Controllers\Api\Ynov\Prestation;

use App\Http\Controllers\Controller;
use App\Services\Api\Ynov\Prestation\PrestationDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PrestationDashboardController extends Controller
{
    public function __construct(
        private PrestationDashboardService $dashboardService
    ) {}

    /**
     * Tableau de bord complet
     */
    public function dashboard(Request $request): JsonResponse
    {
        $filters = $this->getFilters($request);

        return response()->json([
            'success' => true,
            'message' => 'Tableau de bord des prestations récupéré avec succès.',
            'code' => 'DASHBOARD_PRESTATION',
            'data' => [
                'vue_ensemble' => $this->dashboardService->getDashboardStats($filters),
                'repartition_par_statut' => $this->dashboardService->getStatsByStatus($filters),
                'repartition_par_type' => $this->dashboardService->getStatsByType($filters),
                'charge_par_gestionnaire' => $this->dashboardService->getStatsByGestionnaire($filters),
                'file_attente' => $this->dashboardService->getFileAttente($filters, 10),
                'statistiques_par_partenaire' => $this->dashboardService->getStatsByPartner($filters),
                'evolution' => $this->dashboardService->getEvolution($filters, $request->period ?? 'daily'),
            ]
        ]);
    }

    /**
     * Statistiques globales
     */
    public function stats(Request $request): JsonResponse
    {
        $filters = $this->getFilters($request);

        return response()->json([
            'success' => true,
            'message' => 'Statistiques des prestations récupérées avec succès.',
            'code' => 'PRESTATION_STATS',
            'data' => $this->dashboardService->getDashboardStats($filters)
        ]);
    }

    /**
     * Statistiques par type de prestation
     */
    public function statsByType(Request $request): JsonResponse
    {
        $filters = $this->getFilters($request);

        return response()->json([
            'success' => true,
            'message' => 'Répartition par type de prestation récupérée avec succès.',
            'code' => 'STATS_BY_TYPE',
            'data' => $this->dashboardService->getStatsByType($filters)
        ]);
    }

    /**
     * Statistiques par gestionnaire
     */
    public function statsByGestionnaire(Request $request): JsonResponse
    {
        $filters = $this->getFilters($request);

        return response()->json([
            'success' => true,
            'message' => 'Charge par gestionnaire récupérée avec succès.',
            'code' => 'STATS_BY_GESTIONNAIRE',
            'data' => $this->dashboardService->getStatsByGestionnaire($filters)
        ]);
    }

    /**
     * Statistiques par partenaire
     */
    public function statsByPartner(Request $request): JsonResponse
    {
        $filters = $this->getFilters($request);

        return response()->json([
            'success' => true,
            'message' => 'Statistiques par partenaire récupérées avec succès.',
            'code' => 'STATS_BY_PARTNER',
            'data' => $this->dashboardService->getStatsByPartner($filters)
        ]);
    }

    /**
     * File d'attente des prestations non assignées
     */
    public function fileAttente(Request $request): JsonResponse
    {
        $filters = $this->getFilters($request);
        $limit = $request->integer('limit', 10);

        return response()->json([
            'success' => true,
            'message' => 'File d\'attente des prestations récupérée avec succès.',
            'code' => 'FILE_ATTENTE',
            'data' => $this->dashboardService->getFileAttente($filters, $limit)
        ]);
    }

    /**
     * Évolution des prestations par période
     */
    public function evolution(Request $request): JsonResponse
    {
        $filters = $this->getFilters($request);
        $period = $request->input('period', 'daily');

        return response()->json([
            'success' => true,
            'message' => 'Évolution des prestations récupérée avec succès.',
            'code' => 'EVOLUTION',
            'data' => $this->dashboardService->getEvolution($filters, $period)
        ]);
    }

    /**
     * Extrait les filtres de la requête
     */
    private function getFilters(Request $request): array
    {
        return [
            'gestionnaire_uuid' => $request->gestionnaire_uuid,
            'client_uuid' => $request->client_uuid,
            'type_prestation_uuid' => $request->type_prestation_uuid,
            'partner_uuid' => $request->partner_uuid,
            'date' => $request->date,
            'date_debut' => $request->date_debut,
            'date_fin' => $request->date_fin,
            'status' => $request->status,
        ];
    }
}
