<?php

namespace App\Http\Controllers\Api\Ynov\Rdv;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Ynov\Rdv\TransmettreRdvParEmailRequest;
use App\Http\Resources\Api\Ynov\BordereauRdvResource;
use App\Http\Resources\Api\Ynov\DetailBordereauRdvResource;
use App\Services\Api\Ynov\NotificationService;
use App\Services\Api\Ynov\PrestationService;
use App\Services\Api\Ynov\Rdv\BordereauRdvService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BordereauController extends Controller
{
    public function __construct(
        private BordereauRdvService $bordereauRdvService,
        private NotificationService $notificationService,
        private PrestationService $prestationService
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
            'agence_uuid',
            'gestionnaire_uuid',
            'motif_uuid',
            'sort_by',
            'sort_order',
            'per_page',
        ]);

        $lots = $this->bordereauRdvService->listLots($filters);

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
    // public function indexDetails(Request $request): JsonResponse
    // {
    //     $filters = $request->only([
    //         'search',
    //         'status',
    //         'date',
    //         'date_debut',
    //         'date_fin',
    //         'bordereau_rdv_uuid',
    //         'rdv_uuid',
    //         'agence_uuid',
    //         'gestionnaire_uuid',
    //         'motif_uuid',
    //         'sort_by',
    //         'sort_order',
    //         'per_page',
    //     ]);

    //     $user = $request->user();
    //     if ($user && method_exists($user, 'hasRole') && $user->hasRole('gestionnaire_rdv')) {
    //         // $filters['gestionnaire_uuid'] = $user->uuid_user;
    //         // $filters['status'] = 'transmis';
    //     }

    //     $details = $this->bordereauRdvService->listDetails($bordereauRdvUuid,$filters);

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Lignes de bordereau récupérées avec succès.',
    //         'code' => 'BORDEAU_DETAILS_LISTED',
    //         'data' => DetailBordereauRdvResource::collection($details),
    //         'meta' => [
    //             'current_page' => $details->currentPage(),
    //             'per_page' => $details->perPage(),
    //             'total' => $details->total(),
    //             'last_page' => $details->lastPage(),
    //             'filters' => $filters,
    //         ],
    //         'filters_disponibles' => $this->getDetailFiltersAvailable(),
    //     ]);
    // }

    public function indexDetails(Request $request): JsonResponse
    {
        $request->validate([
            'bordereau_rdv_uuid' => ['required', 'string', 'uuid'],
        ]);

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
            // $filters['gestionnaire_uuid'] = $user->uuid_user;
            // $filters['status'] = 'transmis';
        }

        $bordereauRdvUuid = $request->input('bordereau_rdv_uuid');

        $details = $this->bordereauRdvService->listDetails($bordereauRdvUuid, $filters);

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

    /**
     * Transmettre un fichier Excel de RDV par email
     */
    public function transmettreParEmail(TransmettreRdvParEmailRequest $request): JsonResponse
    {
        $data = [
            'gestionnaire_uuid' => $request->gestionnaire_uuid,
            'fichier' => $request->file('fichier'),
            'copie_cc' => $request->copie_cc ?? [],
            'envoye_par' => $request->user()->uuid_user,
        ];

        $result = $this->notificationService->sendEmailWithAttachment($data);

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'code' => $result['code'],
            'data' => $result['data'] ?? null,
        ], $result['status'] ?? 200);
    }

    /**
     * Import du détail d'un bordereau à partir d'un fichier Excel.
     */
    public function importDetails(Request $request): JsonResponse
    {
        $request->validate([
            'reference' => ['required', 'string'],
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
            'observation' => ['nullable', 'string', 'max:500'],
        ]);

        $result = $this->bordereauRdvService->importDetailExcel(
            $request->file('file'),
            $request->input('reference'),
            $request->input('observation')
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Récupérer tous les gestionnaires avec le rôle gestionnaire_prestation
     */
    public function getGestionnairesPrestation(): JsonResponse
    {
        $gestionnaires = $this->prestationService->getGestionnairesPrestation();

        return response()->json([
            'success' => true,
            'message' => 'Gestionnaires prestation récupérés avec succès.',
            'code' => 'GESTIONNAIERS_PRESTATION_LISTED',
            'data' => $gestionnaires,
        ]);
    }
}
