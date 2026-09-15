<?php
// app/Http/Controllers/Api/Ynov/Rdv/RdvController.php

namespace App\Http\Controllers\Api\Ynov\Rdv;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Ynov\Rdv\DatesDisponiblesRequest;
use App\Http\Requests\Api\Ynov\Rdv\MotifsRequest;
use App\Http\Requests\Api\Ynov\Rdv\RdvListRequest;
use App\Http\Requests\Api\Ynov\Rdv\SignalerPresenceRequest;
use App\Http\Requests\Api\Ynov\Rdv\StoreRdvRequest;
use App\Http\Requests\Api\Ynov\Rdv\UpdateRdvStatusRequest;
use App\Http\Requests\Api\Ynov\Rdv\VerifierDateRequest;
use App\Http\Resources\Api\Ynov\RdvResource;
use App\Models\Api\Ynov\Rdv;
use App\Services\Api\Ynov\Rdv\RdvService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
        $categoryUuid = $request->input('category_uuid');

        $motifs = $this->rdvService->getMotifsForContrat(
            $request->code_produit,
            $impact,
            $categoryUuid
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
                    'filter_category_uuid' => $categoryUuid,
                ],
            ]);
        }

        $message = 'Motifs disponibles.';
        if ($impact !== null) {
            $impactLabel = $impact === '1' ? 'sortie portefeuille' : 'non sortie portefeuille';
            $message = "Motifs disponibles avec impact {$impactLabel}.";
        }
        if ($categoryUuid !== null) {
            $message .= " Filtrés par catégorie.";
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
                'filter_category_uuid' => $categoryUuid,
            ],
        ]);
    }


    /**
     * Récupérer les agences disponibles
     */
    public function agences(Request $request): JsonResponse
    {
        $filters = $request->only(['ville', 'search', 'date']);
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
        $filters = $request->only(['status', 'id_contrat', 'search', 'motif_type', 'has_motifs', 'automatic_only', 'manual_only']);
        $perPage = $request->integer('per_page', 20);

        $rdvs = $this->rdvService->getRdvClient(
            $request->user()->uuid_user,
            $filters,
            $perPage
        );

        // Transformer les RDV pour inclure les motifs de traitement et limiter les relations
        $rdvs->getCollection()->transform(function ($rdv) {
            $rdvArray = $rdv->toArray();
            
            // Client - champs limités
            $rdvArray['client'] = [
                'uuid_user' => $rdv->client->uuid_user,
                'numero_client' => $rdv->client->details?->numero_client,
                'login' => $rdv->client->login,
                'email' => $rdv->client->email,
                'nom' => $rdv->client->details?->nom,
                'prenoms' => $rdv->client->details?->prenoms,
                'date_naissance' => $rdv->client->details?->date_naissance?->format('Y-m-d'),
                'lieu_naissance' => $rdv->client->details?->lieu_naissance,
                'full_name' => trim(($rdv->client->details?->nom ?? '') . ' ' . ($rdv->client->details?->prenoms ?? '')),
                'phone' => $rdv->client->details?->mobile_1 ?? $rdv->client->details?->mobile_2 ?? null,
                'lieu_residence' => $rdv->client->details?->lieu_residence,
                'adresse' => $rdv->client->details?->adresse_complete,
                'genre' => $rdv->client->details?->genre,
                'civilite' => $rdv->client->details?->civilite,
                'nationalite' => $rdv->client->details?->nationalite,
                'status' => $rdv->client->status,
                'user_type' => $rdv->client->user_type,
            ];

            // Contrat - champs limités
            if ($rdv->contrat) {
                $rdvArray['contrat'] = [
                    'contrat_id' => $rdv->contrat->contrat_id,
                    'client_number' => $rdv->contrat->client_number,
                    'code_produit' => $rdv->contrat->code_produit,
                    'libelle_produit' => $rdv->contrat->libelle_produit,
                    'code_produit_formule' => $rdv->contrat->code_produit_formule,
                    'libelle_produit_formule' => $rdv->contrat->libelle_produit_formule,
                ];
            }

            // Gestionnaire - champs limités
            if ($rdv->gestionnaire) {
                $rdvArray['gestionnaire'] = [
                    'uuid_user' => $rdv->gestionnaire->uuid_user,
                    'login' => $rdv->gestionnaire->login,
                    'email' => $rdv->gestionnaire->email,
                    'nom' => $rdv->gestionnaire->details?->nom,
                    'prenoms' => $rdv->gestionnaire->details?->prenoms,
                    'full_name' => trim(($rdv->gestionnaire->details?->nom ?? '') . ' ' . ($rdv->gestionnaire->details?->prenoms ?? '')),
                ];
            }

            // Agence souhaitée - champs limités
            if ($rdv->agenceSouhaitee) {
                $rdvArray['agence_souhaitee'] = [
                    'uuid_agence' => $rdv->agenceSouhaitee->uuid_agence,
                    'libelle' => $rdv->agenceSouhaitee->libelle,
                    'code' => $rdv->agenceSouhaitee->code,
                    'ville' => $rdv->agenceSouhaitee->ville,
                    'adresse' => $rdv->agenceSouhaitee->adresse,
                ];
            }

            // Agence effective - champs limités
            if ($rdv->agenceEffective) {
                $rdvArray['agence_effective'] = [
                    'uuid_agence' => $rdv->agenceEffective->uuid_agence,
                    'libelle' => $rdv->agenceEffective->libelle,
                    'code' => $rdv->agenceEffective->code,
                    'ville' => $rdv->agenceEffective->ville,
                    'adresse' => $rdv->agenceEffective->adresse,
                ];
            }

            // Motif - champs limités
            if ($rdv->motif) {
                $rdvArray['motif'] = [
                    'uuid_type_prestation' => $rdv->motif->uuid_type_prestation,
                    'code' => $rdv->motif->code,
                    'libelle' => $rdv->motif->libelle,
                    'description' => $rdv->motif->description,
                    'category_uuid' => $rdv->motif->category_uuid,
                    'delai_traitement' => $rdv->motif->delai_traitement,
                ];
            }

            // Motifs de traitement
            $rdvArray['motif_traitement'] = $rdv->getMotifsTraitement();
            $rdvArray['motifs_par_type'] = [
                'traitement' => $rdv->getMotifsByType('traitement'),
                'report' => $rdv->getMotifsByType('report'),
                'rejet' => $rdv->getMotifsByType('rejet'),
                'annulation' => $rdv->getMotifsByType('annulation'),
                'expiration' => $rdv->getMotifsByType('expiration'),
                'reassignation' => $rdv->getMotifsByType('reassignation'),
            ];
            $rdvArray['motifs_traitement_details'] = !empty($rdv->motif_traitement) ? $rdv->getMotifsTraitementDetails() : [];
            $rdvArray['has_motifs'] = [
                'traitement' => $rdv->hasMotifsType('traitement'),
                'report' => $rdv->hasMotifsType('report'),
                'rejet' => $rdv->hasMotifsType('rejet'),
                'annulation' => $rdv->hasMotifsType('annulation'),
                'expiration' => $rdv->hasMotifsType('expiration'),
                'reassignation' => $rdv->hasMotifsType('reassignation'),
            ];
            return $rdvArray;
        });

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
     * Détails d'un rendez-vous du client
     */
    public function show(string $uuid_rdvs): JsonResponse
    {
        $rdv = Rdv::where('uuid_rdvs', $uuid_rdvs)
            ->with(['client', 'motif', 'agenceSouhaitee', 'agenceEffective', 'gestionnaire', 'contrat'])
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

        // Transformer le RDV pour inclure les motifs de traitement et limiter les relations
        $rdvArray = $rdv->toArray();
        
        // Client - champs limités
        $rdvArray['client'] = [
            'uuid_user' => $rdv->client->uuid_user,
            'numero_client' => $rdv->client->details?->numero_client,
            'login' => $rdv->client->login,
            'email' => $rdv->client->email,
            'nom' => $rdv->client->details?->nom,
            'prenoms' => $rdv->client->details?->prenoms,
            'date_naissance' => $rdv->client->details?->date_naissance?->format('Y-m-d'),
            'lieu_naissance' => $rdv->client->details?->lieu_naissance,
            'full_name' => trim(($rdv->client->details?->nom ?? '') . ' ' . ($rdv->client->details?->prenoms ?? '')),
            'phone' => $rdv->client->details?->mobile_1 ?? $rdv->client->details?->mobile_2 ?? null,
            'lieu_residence' => $rdv->client->details?->lieu_residence,
            'adresse' => $rdv->client->details?->adresse_complete,
            'genre' => $rdv->client->details?->genre,
            'civilite' => $rdv->client->details?->civilite,
            'nationalite' => $rdv->client->details?->nationalite,
            'status' => $rdv->client->status,
            'user_type' => $rdv->client->user_type,
        ];

        // Contrat - champs limités
        if ($rdv->contrat) {
            $rdvArray['contrat'] = [
                'contrat_id' => $rdv->contrat->contrat_id,
                'client_number' => $rdv->contrat->client_number,
                'code_produit' => $rdv->contrat->code_produit,
                'libelle_produit' => $rdv->contrat->libelle_produit,
                'code_produit_formule' => $rdv->contrat->code_produit_formule,
                'libelle_produit_formule' => $rdv->contrat->libelle_produit_formule,
            ];
        }

        // Gestionnaire - champs limités
        $rdvArray['gestionnaire'] = [
            'uuid_user' => $rdv->gestionnaire->uuid_user,
            'login' => $rdv->gestionnaire->login,
            'email' => $rdv->gestionnaire->email,
            'nom' => $rdv->gestionnaire->details?->nom,
            'prenoms' => $rdv->gestionnaire->details?->prenoms,
            'full_name' => trim(($rdv->gestionnaire->details?->nom ?? '') . ' ' . ($rdv->gestionnaire->details?->prenoms ?? '')),
        ];

        // Agence souhaitée - champs limités
        $rdvArray['agence_souhaitee'] = [
            'uuid_agence' => $rdv->agenceSouhaitee->uuid_agence,
            'libelle' => $rdv->agenceSouhaitee->libelle,
            'code' => $rdv->agenceSouhaitee->code,
            'ville' => $rdv->agenceSouhaitee->ville,
            'adresse' => $rdv->agenceSouhaitee->adresse,
        ];

        // Agence effective - champs limités
        $rdvArray['agence_effective'] = [
            'uuid_agence' => $rdv->agenceEffective->uuid_agence,
            'libelle' => $rdv->agenceEffective->libelle,
            'code' => $rdv->agenceEffective->code,
            'ville' => $rdv->agenceEffective->ville,
            'adresse' => $rdv->agenceEffective->adresse,
        ];

        // Motif - champs limités
        $rdvArray['motif'] = [
            'uuid_type_prestation' => $rdv->motif->uuid_type_prestation,
            'code' => $rdv->motif->code,
            'libelle' => $rdv->motif->libelle,
            'description' => $rdv->motif->description,
            'category_uuid' => $rdv->motif->category_uuid,
            'delai_traitement' => $rdv->motif->delai_traitement,
        ];

        // Motifs de traitement
        $rdvArray['motif_traitement'] = $rdv->getMotifsTraitement();
        $rdvArray['motifs_par_type'] = [
            'traitement' => $rdv->getMotifsByType('traitement'),
            'report' => $rdv->getMotifsByType('report'),
            'rejet' => $rdv->getMotifsByType('rejet'),
            'annulation' => $rdv->getMotifsByType('annulation'),
            'expiration' => $rdv->getMotifsByType('expiration'),
            'reassignation' => $rdv->getMotifsByType('reassignation'),
        ];
        $rdvArray['motifs_traitement_details'] = !empty($rdv->motif_traitement) ? $rdv->getMotifsTraitementDetails() : [];
        $rdvArray['has_motifs'] = [
            'traitement' => $rdv->hasMotifsType('traitement'),
            'report' => $rdv->hasMotifsType('report'),
            'rejet' => $rdv->hasMotifsType('rejet'),
            'annulation' => $rdv->hasMotifsType('annulation'),
            'expiration' => $rdv->hasMotifsType('expiration'),
            'reassignation' => $rdv->hasMotifsType('reassignation'),
        ];

        return response()->json([
            'success' => true,
            'message' => 'Détails du rendez-vous.',
            'code' => 'RDV_FOUND',
            'data' => $rdvArray,
        ]);
    }

    /**
     * Annuler un rendez-vous pour le client
     */
    public function cancel(Request $request, string $uuid_rdvs): JsonResponse
    {
        $rdv = Rdv::where('uuid_rdvs', $uuid_rdvs)->firstOrFail();

        if (in_array($rdv->status, ['transmis', 'traite'])) {
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
     * Client signale sa présence
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
     * [Admin] Détail complet d'un rendez-vous
     */
    public function showDetailAdmin(string $uuid_rdvs): JsonResponse
    {
        $rdv = Rdv::with([
            'client.details',
            'gestionnaire.details',
            'motif',
            'transmisParUser',
            'agenceSouhaitee',
            'agenceEffective',
            'detailBordereau',
            'detailBordereau.bordereauRdv',
            'contrat',
        ])->where('uuid_rdvs', $uuid_rdvs)->firstOrFail();

        return response()->json([
            'success' => true,
            'message' => 'Détail complet du rendez-vous récupéré.',
            'code' => 'RDV_DETAIL_ADMIN',
            'data' => new RdvResource($rdv),
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
     * Clients arrivés et signalés en agence (prioritaires)
     */
    public function clientsArrives(RdvListRequest $request): JsonResponse
    {
        $filters = $request->getFilters();
        $perPage = $request->getPerPage();

        $date = $request->date ?? now()->format('Y-m-d');
        $user = $request->user();
        if ($user && method_exists($user, 'hasRole') && $user->hasRole('gestionnaire_rdv')) {
            $filters['gestionnaire_uuid'] = $user->uuid_user;
        }

        $clients = $this->rdvService->getClientsArrives($filters, $perPage);

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
     * Liste des rendez-vous avec filtres pour l'administration
     * (avec pagination et tri)
     */
    public function getList(RdvListRequest $request): JsonResponse
    {
        $filters = $request->getFilters();
        $perPage = $request->getPerPage();

        $user = $request->user();
        // if ($user && method_exists($user, 'hasRole') && $user->hasRole('gestionnaire_rdv')) {
        if ($user && $user->hasRole('gestionnaire_rdv')) {
            $filters['gestionnaire_uuid'] = $user->uuid_user;
            $filters['status'] = 'transmis';
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