<?php

namespace App\Http\Controllers\Api\Ynov\Rdv;

use App\Http\Controllers\Controller;
use App\Services\Api\Ynov\Rdv\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        private DashboardService $dashboardService
    ) {}

    /**
     * Tableau de bord complet
     */
    public function dashboard(Request $request): JsonResponse
    {
        $filters = $this->getFilters($request);

        return response()->json([
            'success' => true,
            'message' => 'Tableau de bord récupéré avec succès.',
            'code' => 'DASHBOARD_RDV',
            'data' => [
                'vue_ensemble' => $this->dashboardService->getDashboardStats($filters),
                'repartition_par_statut' => $this->dashboardService->getStatsByStatus($filters),
                'repartition_par_motif' => $this->dashboardService->getStatsByMotif($filters),
                'charge_par_gestionnaire' => $this->dashboardService->getStatsByGestionnaire($filters),
                'file_attente' => $this->dashboardService->getFileAttente($filters, 10),
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
            'message' => 'Statistiques récupérées avec succès.',
            'code' => 'RDV_STATS',
            'data' => $this->dashboardService->getDashboardStats($filters)
        ]);
    }

    /**
     * File d'attente des rendez-vous à traiter
     */
    public function fileAttente(Request $request): JsonResponse
    {
        $filters = $this->getFilters($request);
        $limit = $request->integer('limit', 10);

        return response()->json([
            'success' => true,
            'message' => 'File d\'attente récupérée avec succès.',
            'code' => 'FILE_ATTENTE_RDV',
            'data' => $this->dashboardService->getFileAttente($filters, $limit)
        ]);
    }

    /**
     * RDV du jour pour un gestionnaire (avec les clients arrivés en évidence)
     */
    public function rdvsDuJour(Request $request): JsonResponse
    {
        $gestionnaireUuid = $request->user()->uuid_user;
        $date = $request->date ?? now()->format('Y-m-d');

        $rdvs = $this->dashboardService->getRdvsDuJourGestionnaire($gestionnaireUuid, $date);

        // Mettre en évidence les clients arrivés
        $rdvsArranges = collect($rdvs)->map(function ($rdv) {
            // Mettre en évidence les clients qui ont signalé leur présence
            if ($rdv['is_present']) {
                $rdv['priorite'] = 'haute';
                $rdv['badge'] = 'Client arrivé';
                $rdv['badge_color'] = '#4CAF50';
            } elseif ($rdv['est_en_retard']) {
                $rdv['priorite'] = 'moyenne';
                $rdv['badge'] = 'En retard';
                $rdv['badge_color'] = '#FF9800';
            } else {
                $rdv['priorite'] = 'basse';
                $rdv['badge'] = 'À venir';
                $rdv['badge_color'] = '#2196F3';
            }
            return $rdv;
        });

        return response()->json([
            'success' => true,
            'message' => 'RDV du jour récupérés avec succès.',
            'code' => 'RDV_DU_JOUR',
            'data' => [
                'rdvs' => $rdvsArranges,
                'total' => count($rdvsArranges),
                'arrives' => collect($rdvsArranges)->where('is_present', true)->count(),
                'en_retard' => collect($rdvsArranges)->where('est_en_retard', true)->count(),
                'date' => $date,
            ],
        ]);
    }

    /**
     * RDV assignés à un gestionnaire
     */
    public function mesRdvs(Request $request): JsonResponse
    {
        $gestionnaireUuid = $request->user()->uuid_user;
        $filters = $this->getFilters($request);
        $perPage = $request->integer('per_page', 20);

        $rdvs = $this->dashboardService->getRdvsByGestionnaire($gestionnaireUuid, $filters, $perPage);

        return response()->json([
            'success' => true,
            'message' => 'Mes RDV récupérés avec succès.',
            'code' => 'MES_RDV',
            'data' => $rdvs,
        ]);
    }


    /**
     * Clients arrivés et signalés en agence (prioritaires)
     */
    public function clientsArrives(Request $request): JsonResponse
    {
        $gestionnaireUuid = $request->user()->uuid_user;
        $date = $request->date ?? now()->format('Y-m-d');

        $clients = $this->dashboardService->getClientsArrives($gestionnaireUuid, $date);

        return response()->json([
            'success' => true,
            'message' => 'Clients arrivés récupérés avec succès.',
            'code' => 'CLIENTS_ARRIVES',
            'data' => [
                'clients' => $clients,
                'total' => count($clients),
                'date' => $date,
            ],
        ]);
    }

    /**
     * Statistiques par motif
     */
    public function statsByMotif(Request $request): JsonResponse
    {
        $filters = $this->getFilters($request);

        return response()->json([
            'success' => true,
            'message' => 'Répartition par motif récupérée avec succès.',
            'code' => 'STATS_BY_MOTIF',
            'data' => $this->dashboardService->getStatsByMotif($filters)
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
     * Statistiques par agence
     */
    public function statsByAgence(Request $request): JsonResponse
    {
        $filters = $this->getFilters($request);

        return response()->json([
            'success' => true,
            'message' => 'Statistiques par agence récupérées avec succès.',
            'code' => 'STATS_BY_AGENCE',
            'data' => $this->dashboardService->getStatsByAgence($filters)
        ]);
    }

    /**
     * Extrait les filtres de la requête
     */
    private function getFilters(Request $request): array
    {
        return [
            'agence_uuid' => $request->agence_uuid,
            'date_debut' => $request->date_debut,
            'date_fin' => $request->date_fin,
            'gestionnaire_uuid' => $request->gestionnaire_uuid,
            'status' => $request->status,
        ];
    }
}
