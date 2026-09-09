<?php

namespace App\Http\Controllers\Api\Ynov\Rdv;

use App\Http\Controllers\Controller;
use App\Services\Api\Ynov\Rdv\CalendrierService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CalendrierController extends Controller
{
    public function __construct(
        private CalendrierService $calendrierService
    ) {}

    /**
     * Obtenir le calendrier des rendez-vous pour un mois donné
     */
    public function calendrier(Request $request): JsonResponse
    {
        $request->validate([
            'mois' => ['nullable', 'integer', 'min:1', 'max:12'],
            'annee' => ['nullable', 'integer', 'min:2020', 'max:2100'],
            'agence_uuid' => ['nullable', 'exists:agences,uuid_agence'],
            'gestionnaire_uuid' => ['nullable', 'exists:users,uuid_user'],
        ]);

        $mois = $request->mois ?? now()->month;
        $annee = $request->annee ?? now()->year;
        $agenceUuid = $request->agence_uuid;
        $gestionnaireUuid = $request->gestionnaire_uuid;

        $calendrier = $this->calendrierService->getCalendrierMois($mois, $annee, $agenceUuid, $gestionnaireUuid);

        if (!$calendrier['success']) {
            return response()->json([
                'success' => false,
                'code' => $calendrier['code'],
                'message' => $calendrier['message'],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Calendrier des rendez-vous récupéré avec succès.',
            'code' => 'CALENDRIER_RDV',
            'data' => $calendrier,
        ], 200);
    }

    // /**
    //  * Obtenir les détails d'un jour spécifique
    //  */
    // public function jourDetails(Request $request): JsonResponse
    // {
    //     $request->validate([
    //         'date' => ['required', 'date'],
    //         'agence_uuid' => ['nullable', 'exists:agences,uuid_agence'],
    //         'gestionnaire_uuid' => ['nullable', 'exists:users,uuid_user'],
    //     ]);

    //     $date = $request->date;
    //     $agenceUuid = $request->agence_uuid;
    //     $gestionnaireUuid = $request->gestionnaire_uuid;

    //     $details = $this->calendrierService->getDetailsJour($date, $agenceUuid, $gestionnaireUuid);

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Détails du jour récupérés avec succès.',
    //         'code' => 'JOUR_DETAILS',
    //         'data' => $details,
    //     ]);
    // }

    /**
     * Obtenir les statistiques globales du calendrier
     */
    public function stats(Request $request): JsonResponse
    {
        $request->validate([
            'mois' => ['nullable', 'integer', 'min:1', 'max:12'],
            'annee' => ['nullable', 'integer', 'min:2020', 'max:2100'],
            'agence_uuid' => ['nullable', 'exists:agences,uuid_agence'],
        ]);

        $mois = $request->mois ?? now()->month;
        $annee = $request->annee ?? now()->year;
        $agenceUuid = $request->agence_uuid;

        $stats = $this->calendrierService->getStatsCalendrier($mois, $annee, $agenceUuid);

        return response()->json([
            'success' => true,
            'message' => 'Statistiques du calendrier récupérées avec succès.',
            'code' => 'CALENDRIER_STATS',
            'data' => $stats,
        ]);
    }
}
