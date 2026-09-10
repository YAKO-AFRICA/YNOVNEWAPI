<?php
// app/Http/Controllers/Api/Ynov/RdvController.php

namespace App\Http\Controllers\Api\Ynov;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Ynov\Rdv\DatesDisponiblesRequest;
use App\Http\Requests\Api\Ynov\Rdv\MotifsRequest;
use App\Http\Requests\Api\Ynov\Rdv\RdvListRequest;
use App\Http\Requests\Api\Ynov\Rdv\SignalerPresenceRequest;
use App\Http\Requests\Api\Ynov\Rdv\StoreRdvRequest;
use App\Http\Requests\Api\Ynov\Rdv\UpdateRdvStatusRequest;
use App\Http\Requests\Api\Ynov\Rdv\VerifierDateRequest;
use App\Models\Api\Ynov\Rdv;
use App\Services\Api\Ynov\Rdv\RdvService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class RdvController extends Controller
{
    public function __construct(
        private RdvService $rdvService
    ) {}

    /**
     * Récupérer les motifs disponibles pour un produit
     */

    public function motifs(MotifsRequest $request): JsonResponse
    {
        $impact = $request->getImpact();

        $motifs = $this->rdvService->getMotifsForContrat(
            $request->code_produit,
            $impact
        );

        if (empty($motifs)) {
            return response()->json([
                'success' => true,
                'message' => 'Aucun motif disponible.',
                'code' => 'NO_MOTIFS_AVAILABLE',
                'data' => [],
                'meta' => [
                    'total' => 0,
                    'filter_impact' => $impact,
                    'filter_impact_label' => $impact !== null 
                        ? ($impact === '1' ? 'Sortie portefeuille' : 'Non sortie portefeuille')
                        : 'Tous',
                ],
            ]);
        }

        $message = 'Motifs disponibles.';
        if ($impact !== null) {
            $impactLabel = $impact === '1' ? 'sortie portefeuille' : 'non sortie portefeuille';
            $message = "Motifs disponibles avec impact {$impactLabel}.";
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'code' => 'MOTIFS_LISTED',
            'data' => $motifs,
            'meta' => [
                'total' => count($motifs),
                'filter_impact' => $impact,
                'filter_impact_label' => $impact !== null 
                    ? ($impact === '1' ? 'Sortie portefeuille' : 'Non sortie portefeuille')
                    : 'Tous',
            ],
        ]);
    }


    /**
     * Récupérer les agences disponibles
     */
    public function agences(Request $request): JsonResponse
    {
        $filters = $request->only(['ville', 'search']);
        $agences = $this->rdvService->getAgencesDisponibles($filters);
        if (empty($agences)) {
            return response()->json([
                'success' => true,
                'message' => 'Aucune agence disponible.',
                'code' => 'NO_AGENCES_AVAILABLE',
                'data' => [],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Agences disponibles.',
            'code' => 'AGENCES_RDV_LISTED',
            'data' => $agences,
        ]);
    }

    public function datesDisponibles(DatesDisponiblesRequest $request): JsonResponse
    {
        $params = $request->getDateParams();

        $dates = $this->rdvService->getDatesDisponibles(
            $params['agence_uuid'],
            $params['mois'],
            $params['annee']
        );

        if (empty($dates)) {
            return response()->json([
                'success' => true,
                'message' => $dates['message'],
                'code' => $dates['code'],
                'data' => [],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Dates disponibles.',
            'code' => 'DATES_DISPONIBLES',
            'data' => $dates,
            'meta' => [
                'total' => count($dates),
                'mois' => $params['mois'],
                'annee' => $params['annee'],
            ],
        ]);
    }


    /**
     * Vérifier une date spécifique
     */

    public function verifierDate(VerifierDateRequest $request): JsonResponse
    {
        $resultat = $this->rdvService->verifierDateDisponible(
            $request->agence_uuid,
            $request->date_rdv
        );

        if (!$resultat['disponible']) {
            return response()->json([
                'success' => $resultat['disponible'],
                'message' => $resultat['message'],
                'code' => $resultat['code'],
                'data' => [],
            ]);
        }

        return response()->json([
            'success' => $resultat['disponible'],
            'message' => $resultat['disponible'] ? 'Date disponible.' : 'Date non disponible.',
            'code' => $resultat['disponible'] ? 'DATE_DISPONIBLE' : 'DATE_NON_DISPONIBLE',
            'data' => $resultat,
        ]);
    }

    /**
     * Créer un rendez-vous
     */

     public function store(StoreRdvRequest $request): JsonResponse
    {
        try {
            $data = $request->getRdvData();

            $rdv = $this->rdvService->create(
                $data,
                $request->user(),
                $request->user()->uuid_user
            );

            if (!$rdv['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $rdv['message'],
                    'code' => $rdv['code'],
                    'data' => [],
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => $rdv['message'],
                'code' => $rdv['code'],
                'data' => $rdv['data'],
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation.',
                'errors' => $e->errors(),
                'code' => 'VALIDATION_ERROR',
            ], 422);
        }
    }

    /**
     * Liste des rendez-vous du client
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['status', 'id_contrat', 'search']);
        $perPage = $request->integer('per_page', 20);

        $rdvs = $this->rdvService->getRdvClient(
            $request->user()->uuid_user,
            $filters,
            $perPage
        );

        return response()->json([
            'success' => true,
            'message' => 'Liste des rendez-vous.',
            'code' => 'RDVS_LISTED',
            'data' => $rdvs,
            'meta' => [
                'current_page' => $rdvs->currentPage(),
                'per_page' => $rdvs->perPage(),
                'total' => $rdvs->total(),
                'last_page' => $rdvs->lastPage(),
            ]
        ]);
    }

    /**
     * Détails d'un rendez-vous
     */
    public function show(string $uuid_rdvs): JsonResponse
    {
        $rdv = Rdv::where('uuid_rdvs', $uuid_rdvs)
            ->with(['client', 'motif', 'agenceSouhaitee', 'agenceEffective', 'gestionnaire'])
            ->firstOrFail();

        if ($rdv->client_uuid !== request()->user()->uuid_user) {
            $user = request()->user();
            if (!$user->hasPermission('rdvs.afficher')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé.',
                    'code' => 'FORBIDDEN',
                ], 403);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Détails du rendez-vous.',
            'code' => 'RDV_FOUND',
            'data' => $rdv,
        ]);
    }

    /**
     * Annuler un rendez-vous
     */
    public function cancel(Request $request, string $uuid_rdvs): JsonResponse
    {
        $rdv = Rdv::where('uuid_rdvs', $uuid_rdvs)->firstOrFail();

        if (in_array($rdv->status, ['confirme', 'termine', 'traite'])) {
            return response()->json([
                'success' => false,
                'message' => 'Ce rendez-vous ne peut plus être annulé car il est déjà confirmé ou traité.',
                'code' => 'RDV_DEJA_TRAITE',
            ], 422);
        }

        $rdv = $this->rdvService->updateStatus(
            $rdv,
            'annule',
            $request->user()->uuid_user,
            ['annulation' => $request->motif ?? 'Annulé par le client']
        );

        return response()->json([
            'success' => true,
            'message' => 'Rendez-vous annulé avec succès.',
            'code' => 'RDV_CANCELLED',
            'data' => $rdv,
        ]);
    }

    /**
     * Signaler sa présence
     */

    public function signalerPresence(SignalerPresenceRequest $request, string $uuid_rdvs): JsonResponse
    {
        try {
            $rdv = Rdv::where('uuid_rdvs', $uuid_rdvs)->firstOrFail();

            $rdv = $this->rdvService->signalerPresence(
                $rdv,
                $request->user()->uuid_user,
                $request->getCoordinates()
            );
            if (!$rdv['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $rdv['message'],
                    'code' => $rdv['code'],
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => 'Présence signalée avec succès.',
                'code' => 'PRESENCE_SIGNALEE',
                'data' => [
                    'uuid_rdvs' => $rdv['data']->uuid_rdvs,
                    'code' => $rdv['data']->code,
                    'status' => $rdv['data']->status,
                    'status_label' => Rdv::STATUS[$rdv['data']->status] ?? $rdv['data']->status,
                    'is_present' => $rdv['data']->is_present,
                    'date_rdv_effective' => $rdv['data']->date_rdv_effective,
                ],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation.',
                'errors' => $e->errors(),
                'code' => 'VALIDATION_ERROR',
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors du signalement de présence.',
                'code' => 'PRESENCE_ERROR',
            ], 500);
        }
    }


    /**
     * Statistiques des rendez-vous du client
     */
    public function stats(Request $request): JsonResponse
    {
        $stats = $this->rdvService->getStats($request->user()->uuid_user);

        return response()->json([
            'success' => true,
            'message' => 'Statistiques des rendez-vous.',
            'code' => 'RDV_STATS',
            'data' => $stats,
        ]);
    }

    // ============================================================
    // ADMIN - Gestion des rendez-vous
    // ============================================================

    /**
     * [Admin] Liste des rendez-vous d'une agence
     */
    public function agenceRdvs(Request $request, string $uuid_agence): JsonResponse
    {
        $filters = $request->only(['status', 'date', 'gestionnaire_uuid', 'is_present']);
        $perPage = $request->integer('per_page', 20);

        $rdvs = $this->rdvService->getRdvAgence($uuid_agence, $filters, $perPage);

        return response()->json([
            'success' => true,
            'message' => 'Liste des rendez-vous de l\'agence.',
            'code' => 'AGENCE_RDVS_LISTED',
            'data' => $rdvs,
            'meta' => [
                'current_page' => $rdvs->currentPage(),
                'per_page' => $rdvs->perPage(),
                'total' => $rdvs->total(),
                'last_page' => $rdvs->lastPage(),
            ]
        ]);
    }

    /**
     * [Admin] Mettre à jour le statut d'un rendez-vous
     */

    public function updateStatus(UpdateRdvStatusRequest $request, string $uuid_rdvs): JsonResponse
    {
        $rdv = Rdv::where('uuid_rdvs', $uuid_rdvs)->firstOrFail();

        $rdv = $this->rdvService->updateStatus(
            $rdv,
            $request->getStatus(),
            $request->user()->uuid_user,
            $request->getUpdateData()
        );

        return response()->json([
            'success' => true,
            'message' => 'Statut du rendez-vous mis à jour.',
            'code' => 'RDV_STATUS_UPDATED',
            'data' => $rdv,
        ]);
    }

    // /**
    //  * [Admin] Assigner un gestionnaire à un rendez-vous
    //  */
    // public function assignGestionnaire(Request $request, string $uuid_rdvs): JsonResponse
    // {
    //     $request->validate([
    //         'gestionnaire_uuid' => ['required', 'exists:users,uuid_user'],
    //     ]);

    //     $rdv = Rdv::where('uuid_rdvs', $uuid_rdvs)->firstOrFail();

    //     $rdv->update([
    //         'gestionnaire_uuid' => $request->gestionnaire_uuid,
    //         'updated_by' => $request->user()->uuid_user,
    //     ]);

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Gestionnaire assigné avec succès.',
    //         'code' => 'GESTIONNAIRE_ASSIGNED',
    //         'data' => $rdv->load('gestionnaire'),
    //     ]);
    // }



    /**
     * RDV du jour pour un gestionnaire (avec les clients arrivés en évidence)
     */
    public function rdvsDuJour(Request $request): JsonResponse
    {
        $gestionnaireUuid = $request->user()->uuid_user;
        $date = $request->date ?? now()->format('Y-m-d');

        $filters = $request->only(['status', 'agence_uuid', 'is_present', 'date_debut', 'date_fin']);
        $filters['date'] = $date;
        $filters['gestionnaire_uuid'] = $gestionnaireUuid;

        $rdvs = $this->rdvService->getList($filters, 20, true);

        $rdvsArranges = collect($rdvs)->map(function ($rdv) {
            if (!empty($rdv['is_present'])) {
                $rdv['priorite'] = 'haute';
                $rdv['badge'] = 'Client arrivé';
                $rdv['badge_color'] = '#4CAF50';
            } elseif (!empty($rdv['est_en_retard'])) {
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
        $filters = $request->only(['status', 'agence_uuid', 'date_debut', 'date_fin', 'is_present']);
        $filters['gestionnaire_uuid'] = $gestionnaireUuid;
        $perPage = $request->integer('per_page', 20);

        $rdvs = $this->rdvService->getList($filters, $perPage);

        return response()->json([
            'success' => true,
            'message' => 'Mes RDV récupérés avec succès.',
            'code' => 'MES_RDV',
            'data' => $rdvs,
        ]);
    }

    /**
     * Liste des rendez-vous avec filtres
     */
    public function getList(RdvListRequest $request): JsonResponse
    {
        $filters = $request->getFilters();
        $perPage = $request->getPerPage();

        $user = $request->user();
        if ($user && method_exists($user, 'hasRole') && $user->hasRole('gestionnaire_rdv')) {
            Log::debug('Utilisateur gestionnaire : ' . $user->uuid_user);
            $filters['gestionnaire_uuid'] = $user->uuid_user;
        }

        $rdvs = $this->rdvService->getList($filters, $perPage);

        if ($rdvs->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun rendez-vous trouvé.',
                'code' => 'RDVS_NOT_FOUND',
            ], 404);
        }

        $formattedData = $this->rdvService->formatForList($rdvs);

        return response()->json([
            'success' => true,
            'message' => 'Liste des rendez-vous récupérée avec succès.',
            'code' => 'RDVS_LISTED',
            'data' => $formattedData,
            'meta' => [
                'current_page' => $rdvs->currentPage(),
                'per_page' => $rdvs->perPage(),
                'total' => $rdvs->total(),
                'last_page' => $rdvs->lastPage(),
                'filters' => $filters,
            ],
            'filters_disponibles' => $this->getAvailableFilters(),
        ]);
    }

    /**
     * Export des rendez-vous (CSV/Excel)
     */
    public function export(RdvListRequest $request): JsonResponse
    {
        $filters = $request->getFilters();

        $user = $request->user();
        if ($user && method_exists($user, 'hasRole') && $user->hasRole('gestionnaire_rdv')) {
            $filters['gestionnaire_uuid'] = $user->uuid_user;
        }

        $rdvs = $this->rdvService->getList($filters, 99999);
        $formattedData = $this->rdvService->formatForList($rdvs);

        return response()->json([
            'success' => true,
            'message' => 'Export des rendez-vous préparé.',
            'code' => 'RDVS_EXPORT',
            'data' => $formattedData,
            'meta' => [
                'total' => count($formattedData),
                'exported_at' => now()->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    /**
     * Obtenir les filtres disponibles
     */
    private function getAvailableFilters(): array
    {
        return [
            'status' => Rdv::STATUS,
            'sort_by' => [
                'created_at' => 'Date de création',
                'date_rdv_souhaiter' => 'Date du rendez-vous',
                'status' => 'Statut',
                'code' => 'Code',
            ],
            'sort_order' => [
                'asc' => 'Croissant',
                'desc' => 'Décroissant',
            ],
        ];
    }
}