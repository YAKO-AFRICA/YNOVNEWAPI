<?php
// app/Http/Controllers/Api/Ynov/RdvController.php

namespace App\Http\Controllers\Api\Ynov;

use App\Http\Controllers\Controller;
use App\Models\Api\Ynov\Rdv;
use App\Services\Api\Ynov\RdvService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

// class RdvController extends Controller
// {
//     public function __construct(
//         private RdvService $rdvService,
//         private EncaissementBisService $encaissementBisService
//     ) {}

//     /**
//      * Récupérer les motifs disponibles pour un produit
//      */
//     public function motifs(Request $request): JsonResponse
//     {
//         $request->validate([
//             'code_produit' => ['required', 'string', 'exists:produits,code'],
//         ]);

//         $motifs = $this->rdvService->getMotifsForContrat(
//             $request->code_produit,
//             $request->user()->uuid_user
//         );

//         return response()->json([
//             'success' => true,
//             'message' => 'Motifs disponibles.',
//             'code' => 'MOTIFS_LISTED',
//             'data' => $motifs,
//         ]);
//     }

//     /**
//      * Récupérer les agences disponibles
//      */
//     public function agences(Request $request): JsonResponse
//     {
//         $filters = $request->only(['ville', 'search']);
//         $agences = $this->rdvService->getAgencesDisponibles($filters);

//         return response()->json([
//             'success' => true,
//             'message' => 'Agences disponibles.',
//             'code' => 'AGENCES_RDV_LISTED',
//             'data' => $agences,
//         ]);
//     }

//     /**
//      * Récupérer les dates disponibles
//      */
//     public function datesDisponibles(Request $request): JsonResponse
//     {
//         $request->validate([
//             'agence_uuid' => ['required', 'exists:agences,uuid_agence'],
//             'mois' => ['required', 'integer', 'min:1', 'max:12'],
//             'annee' => ['required', 'integer', 'min:2020', 'max:2100'],
//         ]);

//         $dates = $this->rdvService->getDatesDisponibles(
//             $request->agence_uuid,
//             $request->mois,
//             $request->annee
//         );

//         return response()->json([
//             'success' => true,
//             'message' => 'Dates disponibles.',
//             'code' => 'DATES_DISPONIBLES',
//             'data' => $dates,
//         ]);
//     }

//     /**
//      * Vérifier une date spécifique
//      */
//     public function verifierDate(Request $request): JsonResponse
//     {
//         $request->validate([
//             'agence_uuid' => ['required', 'exists:agences,uuid_agence'],
//             'date_rdv' => ['required', 'date', 'after_or_equal:today'],
//         ]);

//         $resultat = $this->rdvService->verifierDateDisponible(
//             $request->agence_uuid,
//             $request->date_rdv
//         );

//         return response()->json([
//             'success' => $resultat['disponible'],
//             'message' => $resultat['disponible'] ? 'Date disponible.' : 'Date non disponible.',
//             'code' => $resultat['disponible'] ? 'DATE_DISPONIBLE' : 'DATE_NON_DISPONIBLE',
//             'data' => $resultat,
//         ]);
//     }

//     /**
//      * Créer un rendez-vous
//      */
//     public function store(Request $request): JsonResponse
//     {
//         try {
//             $validated = $request->validate([
//                 'id_contrat' => ['required', 'integer'],
//                 'motif_rdv' => ['required', 'exists:type_prestations,uuid_type_prestation'],
//                 'demandeur' => ['nullable', 'string', 'max:50'],
//                 'agence_souhaiter_uuid' => ['required', 'exists:agences,uuid_agence'],
//                 'date_rdv' => ['required', 'date', 'after_or_equal:today'],
//             ]);

//             $rdv = $this->rdvService->create(
//                 $validated,
//                 $request->user(),
//                 $request->user()->uuid_user
//             );

//             return response()->json([
//                 'success' => true,
//                 'message' => 'Rendez-vous créé avec succès. Code : ' . $rdv->code,
//                 'code' => 'RDV_CREATED',
//                 'data' => $rdv,
//             ], 201);
//         } catch (ValidationException $e) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'Erreur de validation.',
//                 'errors' => $e->errors(),
//                 'code' => 'VALIDATION_ERROR',
//             ], 422);
//         }
//     }

//     /**
//      * Liste des rendez-vous du client
//      */
//     public function index(Request $request): JsonResponse
//     {
//         $filters = $request->only(['status', 'id_contrat', 'search']);
//         $perPage = $request->integer('per_page', 20);

//         $rdvs = $this->rdvService->getRdvClient(
//             $request->user()->uuid_user,
//             $filters,
//             $perPage
//         );

//         return response()->json([
//             'success' => true,
//             'message' => 'Liste des rendez-vous.',
//             'code' => 'RDVS_LISTED',
//             'data' => $rdvs,
//             'meta' => [
//                 'current_page' => $rdvs->currentPage(),
//                 'per_page' => $rdvs->perPage(),
//                 'total' => $rdvs->total(),
//                 'last_page' => $rdvs->lastPage(),
//             ]
//         ]);
//     }

//     /**
//      * Détails d'un rendez-vous
//      */
//     public function show(string $uuid_rdvs): JsonResponse
//     {
//         $rdv = Rdv::where('uuid_rdvs', $uuid_rdvs)
//             ->with(['client', 'motif', 'agenceSouhaitee', 'agenceEffective', 'gestionnaire'])
//             ->firstOrFail();

//         // Vérifier que le client est le propriétaire ou un admin
//         if ($rdv->client_uuid !== request()->user()->uuid_user) {
//             $user = request()->user();
//             if (!$user->hasPermission('rdvs.afficher')) {
//                 return response()->json([
//                     'success' => false,
//                     'message' => 'Accès non autorisé.',
//                     'code' => 'FORBIDDEN',
//                 ], 403);
//             }
//         }

//         return response()->json([
//             'success' => true,
//             'message' => 'Détails du rendez-vous.',
//             'code' => 'RDV_FOUND',
//             'data' => $rdv,
//         ]);
//     }

//     /**
//      * Annuler un rendez-vous
//      */
//     public function cancel(Request $request, string $uuid_rdvs): JsonResponse
//     {
//         $rdv = Rdv::where('uuid_rdvs', $uuid_rdvs)->firstOrFail();

//         // Vérifier que le client est le propriétaire ou un admin
//         if ($rdv->client_uuid !== $request->user()->uuid_user) {
//             $user = $request->user();
//             if (!$user->hasPermission('rdvs.annuler')) {
//                 return response()->json([
//                     'success' => false,
//                     'message' => 'Accès non autorisé.',
//                     'code' => 'FORBIDDEN',
//                 ], 403);
//             }
//         }

//         if ($rdv->status === 'confirmer' || $rdv->status === 'traite') {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'Ce rendez-vous ne peut plus être annulé car il est déjà confirmé ou traité.',
//                 'code' => 'RDV_DEJA_TRAITE',
//             ], 422);
//         }

//         $rdv = $this->rdvService->updateStatus(
//             $rdv,
//             'annule',
//             ['annulation' => $request->motif ?? 'Annulé par le client'],
//             $request->user()->uuid_user
//         );

//         return response()->json([
//             'success' => true,
//             'message' => 'Rendez-vous annulé avec succès.',
//             'code' => 'RDV_CANCELLED',
//             'data' => $rdv,
//         ]);
//     }

//     /**
//      * Signaler sa présence
//      */
//     public function signalerPresence(Request $request, string $uuid_rdvs): JsonResponse
//     {
//         try {
//             $request->validate([
//                 'latitude' => ['nullable', 'numeric', 'between:-90,90'],
//                 'longitude' => ['nullable', 'numeric', 'between:-180,180'],
//             ]);

//             $rdv = Rdv::where('uuid_rdvs', $uuid_rdvs)->firstOrFail();

//             $rdv = $this->rdvService->signalerPresence(
//                 $rdv,
//                 $request->user()->uuid_user,
//                 $request->only(['latitude', 'longitude'])
//             );

//             return response()->json([
//                 'success' => true,
//                 'message' => 'Présence signalée avec succès.',
//                 'code' => 'PRESENCE_SIGNALEE',
//                 'data' => $rdv,
//             ]);
//         } catch (ValidationException $e) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'Erreur de validation.',
//                 'errors' => $e->errors(),
//                 'code' => 'VALIDATION_ERROR',
//             ], 422);
//         }
//     }

//     /**
//      * Statistiques des rendez-vous du client
//      */
//     public function stats(Request $request): JsonResponse
//     {
//         $stats = $this->rdvService->getStats($request->user()->uuid_user);

//         return response()->json([
//             'success' => true,
//             'message' => 'Statistiques des rendez-vous.',
//             'code' => 'RDV_STATS',
//             'data' => $stats,
//         ]);
//     }

//     // ============================================================
//     // ADMIN - Gestion des rendez-vous
//     // ============================================================

//     /**
//      * [Admin] Liste des rendez-vous d'une agence
//      */
//     public function agenceRdvs(Request $request, string $uuid_agence): JsonResponse
//     {
//         $filters = $request->only(['status', 'date']);
//         $perPage = $request->integer('per_page', 20);

//         $rdvs = $this->rdvService->getRdvAgence($uuid_agence, $filters, $perPage);

//         return response()->json([
//             'success' => true,
//             'message' => 'Liste des rendez-vous de l\'agence.',
//             'code' => 'AGENCE_RDVS_LISTED',
//             'data' => $rdvs,
//             'meta' => [
//                 'current_page' => $rdvs->currentPage(),
//                 'per_page' => $rdvs->perPage(),
//                 'total' => $rdvs->total(),
//                 'last_page' => $rdvs->lastPage(),
//             ]
//         ]);
//     }

//     /**
//      * [Admin] Mettre à jour le statut d'un rendez-vous
//      */
//     public function updateStatus(Request $request, string $uuid_rdvs): JsonResponse
//     {
//         $request->validate([
//             'status' => ['required', 'in:en_attente,confirme,rejete,traite,present,absent,reporte'],
//             'observation' => ['nullable', 'string'],
//         ]);

//         $rdv = Rdv::where('uuid_rdvs', $uuid_rdvs)->firstOrFail();

//         $rdv = $this->rdvService->updateStatus(
//             $rdv,
//             $request->status,
//             [
//                 'observation' => $request->observation,
//                 'admin_action' => true,
//             ],
//             $request->user()->uuid_user
//         );

//         return response()->json([
//             'success' => true,
//             'message' => 'Statut du rendez-vous mis à jour.',
//             'code' => 'RDV_STATUS_UPDATED',
//             'data' => $rdv,
//         ]);
//     }

//     /**
//      * [Admin] Assigner un gestionnaire à un rendez-vous
//      */
//     public function assignGestionnaire(Request $request, string $uuid_rdvs): JsonResponse
//     {
//         $request->validate([
//             'gestionnaire_uuid' => ['required', 'exists:users,uuid_user'],
//         ]);

//         $rdv = Rdv::where('uuid_rdvs', $uuid_rdvs)->firstOrFail();

//         $rdv->update([
//             'gestionnaire_uuid' => $request->gestionnaire_uuid,
//             'updated_by' => $request->user()->uuid_user,
//         ]);

//         return response()->json([
//             'success' => true,
//             'message' => 'Gestionnaire assigné avec succès.',
//             'code' => 'GESTIONNAIRE_ASSIGNED',
//             'data' => $rdv->load('gestionnaire'),
//         ]);
//     }
// }

class RdvController extends Controller
{
    public function __construct(
        private RdvService $rdvService
    ) {}

    /**
     * Récupérer les motifs disponibles pour un produit
     */
     public function motifs(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code_produit' => ['required', 'string', 'exists:produits,code'],
            'impact' => ['nullable', 'string', 'in:0,1'],
        ]);

        $impact = $request->has('impact') && $request->impact !== '' ? $request->impact : null;

        $motifs = $this->rdvService->getMotifsForContrat(
            $validated['code_produit'],
            $impact
        );

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

        return response()->json([
            'success' => true,
            'message' => 'Agences disponibles.',
            'code' => 'AGENCES_RDV_LISTED',
            'data' => $agences,
        ]);
    }

    /**
     * Récupérer les dates disponibles
     */
    public function datesDisponibles(Request $request): JsonResponse
    {
        $request->validate([
            'agence_uuid' => ['required', 'exists:agences,uuid_agence'],
            'mois' => ['required', 'integer', 'min:1', 'max:12'],
            'annee' => ['required', 'integer', 'min:2020', 'max:2100'],
            // 'id_contrat' => ['nullable', 'integer', 'exists:produits,id'],
        ]);

        $dates = $this->rdvService->getDatesDisponibles(
            $request->agence_uuid,
            $request->mois,
            $request->annee,
        );

        return response()->json([
            'success' => true,
            'message' => 'Dates disponibles.',
            'code' => 'DATES_DISPONIBLES',
            'data' => $dates,
        ]);
    }

    /**
     * Vérifier une date spécifique
     */
    public function verifierDate(Request $request): JsonResponse
    {
        $request->validate([
            'agence_uuid' => ['required', 'exists:agences,uuid_agence'],
            'date_rdv' => ['required', 'date', 'after_or_equal:today'],
        ]);

        $resultat = $this->rdvService->verifierDateDisponible(
            $request->agence_uuid,
            $request->date_rdv
        );

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
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'id_contrat' => ['required', 'string'],
                // 'code_produit' => ['required', 'string', 'exists:produits,code'],
                'motif_rdv' => ['required', 'exists:type_prestations,uuid_type_prestation'],
                'demandeur' => ['nullable', 'string', 'max:50'],
                'agence_uuid' => ['required', 'exists:agences,uuid_agence'],
                'date_rdv' => ['required', 'date', 'after_or_equal:today'],
            ]);

            $rdv = $this->rdvService->create(
                $validated,
                $request->user(),
                $request->user()->uuid_user
            );

            return response()->json([
                'success' => true,
                'message' => 'Rendez-vous créé avec succès. Code : ' . $rdv->code,
                'code' => 'RDV_CREATED',
                'data' => $rdv,
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

        // if ($rdv->client_uuid !== $request->user()->uuid_user) {
        //     $user = $request->user();
        //     if (!$user->hasPermission('rdvs.annuler')) {
        //         return response()->json([
        //             'success' => false,
        //             'message' => 'Accès non autorisé.',
        //             'code' => 'FORBIDDEN',
        //         ], 403);
        //     }
        // }

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
            ['annulation' => $request->motif ?? 'Annulé par le client'],
            $request->user()->uuid_user
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
    public function signalerPresence(Request $request, string $uuid_rdvs): JsonResponse
    {
        try {
            $request->validate([
                'latitude' => ['nullable', 'numeric', 'between:-90,90'],
                'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            ]);

            $rdv = Rdv::where('uuid_rdvs', $uuid_rdvs)->firstOrFail();

            $rdv = $this->rdvService->signalerPresence(
                $rdv,
                $request->user()->uuid_user,
                $request->only(['latitude', 'longitude'])
            );

            return response()->json([
                'success' => true,
                'message' => 'Présence signalée avec succès.',
                'code' => 'PRESENCE_SIGNALEE',
                'data' => $rdv,
            ]);
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
        $filters = $request->only(['status', 'date']);
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
    public function updateStatus(Request $request, string $uuid_rdvs): JsonResponse
    {
        $request->validate([
            'status' => ['required', 'in:en_attente,confirme,rejete,traite,termine,annule,reporte'],
            'observation' => ['nullable', 'string'],
        ]);

        $rdv = Rdv::where('uuid_rdvs', $uuid_rdvs)->firstOrFail();

        $rdv = $this->rdvService->updateStatus(
            $rdv,
            $request->status,
            [
                'observation' => $request->observation,
                'admin_action' => true,
            ],
            $request->user()->uuid_user
        );

        return response()->json([
            'success' => true,
            'message' => 'Statut du rendez-vous mis à jour.',
            'code' => 'RDV_STATUS_UPDATED',
            'data' => $rdv,
        ]);
    }

    /**
     * [Admin] Assigner un gestionnaire à un rendez-vous
     */
    public function assignGestionnaire(Request $request, string $uuid_rdvs): JsonResponse
    {
        $request->validate([
            'gestionnaire_uuid' => ['required', 'exists:users,uuid_user'],
        ]);

        $rdv = Rdv::where('uuid_rdvs', $uuid_rdvs)->firstOrFail();

        $rdv->update([
            'gestionnaire_uuid' => $request->gestionnaire_uuid,
            'updated_by' => $request->user()->uuid_user,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Gestionnaire assigné avec succès.',
            'code' => 'GESTIONNAIRE_ASSIGNED',
            'data' => $rdv->load('gestionnaire'),
        ]);
    }
}