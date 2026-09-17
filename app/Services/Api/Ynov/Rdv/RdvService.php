<?php
// app/Services/Api/Ynov/RdvService.php

namespace App\Services\Api\Ynov\Rdv;

use App\Models\Api\Ynov\BordereauRdv;
use App\Models\Api\Ynov\DetailBordereauRdv;
use App\Models\Api\Ynov\parameter\ActivityLog;
use App\Models\Api\Ynov\parameter\Agence;
use App\Models\Api\Ynov\parameter\GroupNotif;
use App\Models\Api\Ynov\parameter\JourFerie;
use App\Models\Api\Ynov\parameter\Produit;
use App\Models\Api\Ynov\parameter\ProduitFormule;
use App\Models\Api\Ynov\parameter\TypePrestation;
use App\Models\Api\Ynov\parameter\User;
use App\Models\Api\Ynov\Rdv;
use App\Services\Api\Ynov\NotificationService;
use App\Services\Api\Ynov\Rdv\BordereauRdvService;
use App\Services\Api\Ynov\Rdv\RoutingService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
// use Illuminate\Validation\ValidationException;

class RdvService
{
    private const CODES_FORMULES_TRANSFORMATION = [
        'INV_2020_V2',
        'YKP_2024_v1',
        'LFFUN_V44',
    ];

    public function __construct(
        private NotificationService $notificationService,
        private RoutingService $routingService,
        private BordereauRdvService $bordereauRdvService
    ) {}

   /**
     * Récupérer les motifs disponibles pour un contrat
     * 
     * @param string $codeProduit Code du produit
     * @param string|null $impact Filtrer par impact (1: sortie portefeuille, 0: non sortie, null: tous)
     * @return array
     */
    public function getMotifsForContrat(string $codeProduit, ?string $impact = null, ?string $categoryUuid = null): array
    {
        $produit = Produit::where('code', $codeProduit)->first();
        if (!$produit) {
            return [];
        }

        // Construire la requête
        $query = $produit->typePrestations()
            ->wherePivot('status', 'actif') // status dans produit_prestations
            ->where('type_prestations.status', 'actif') // spécifier la table pour status
            ->with('category')
            ->orderBy('type_prestations.libelle'); // spécifier la table pour libelle

        // Filtrer par impact si spécifié
        if ($impact !== null && in_array($impact, ['0', '1'])) {
            $query->where('type_prestations.impact', $impact);
        }

        // Filtrer par catégorie si spécifié
        if ($categoryUuid !== null) {
            $query->where('type_prestations.category_uuid', $categoryUuid);
        }

        $prestations = $query->get();

        return $prestations->map(function ($prestation) {
            return [
                'uuid_type_prestation' => $prestation->uuid_type_prestation,
                'code' => $prestation->code,
                'libelle' => $prestation->libelle,
                'description' => $prestation->description,
                'impact' => $prestation->impact,
                'impact_label' => $prestation->getImpactLabel(),
                'category' => $prestation->category ? [
                    'uuid' => $prestation->category->uuid_category_type_prestations,
                    'libelle' => $prestation->category->libelle,
                ] : null,
            ];
        })->toArray();
    }

    /**
     * Récupérer les produits, formules et garanties disponibles pour une
     * transformation liée à un rendez-vous.
     */
    public function getProduitsTransformation(Rdv $rdv): array
    {
        return ProduitFormule::query()
            ->whereIn('code_produit_formule', self::CODES_FORMULES_TRANSFORMATION)
            ->where('est_actif', true)
            ->whereHas('produit', function ($query) {
                $query->where('statut', 'actif');
            })
            ->with([
                'produit.typeProduit',
                'produit.garanties',
            ])
            ->orderByRaw(
                'FIELD(code_produit_formule, ' . implode(', ', array_fill(0, count(self::CODES_FORMULES_TRANSFORMATION), '?')) . ')',
                self::CODES_FORMULES_TRANSFORMATION
            )
            ->get()
            ->map(function (ProduitFormule $formule) use ($rdv): array {
                $produit = $formule->produit;

                return [
                    'rdv_uuid' => $rdv->uuid_rdvs,
                    'rdv_code' => $rdv->code,
                    'produit' => $produit ? [
                        'uuid_produit' => $produit->uuid_produit,
                        'code' => $produit->code,
                        'libelle' => $produit->libelle,
                        'description' => $produit->description,
                        'statut' => $produit->statut,
                        'type_produit' => $produit->typeProduit ? [
                            'uuid_type_produit' => $produit->typeProduit->uuid_type_produit,
                            'code' => $produit->typeProduit->code,
                            'libelle' => $produit->typeProduit->libelle,
                        ] : null,
                    ] : null,
                    'formule' => [
                        'uuid_produit_formule' => $formule->uuid_produit_formule,
                        'code_produit_formule' => $formule->code_produit_formule,
                        'code_produit' => $formule->code_produit,
                        'libelle' => $formule->libelle,
                        'est_actif' => (bool) $formule->est_actif,
                        'date_debut' => $formule->date_debut?->format('Y-m-d'),
                        'date_fin' => $formule->date_fin?->format('Y-m-d'),
                    ],
                    'garanties' => $produit?->garanties->map(fn ($garantie) => [
                        'uuid_produit_garantie' => $garantie->uuid_produit_garantie,
                        'code_produit_garantie' => $garantie->code_produit_garantie,
                        'libelle' => $garantie->libelle,
                        'est_obligatoire' => (bool) $garantie->est_obligatoire,
                        'nature_garantie' => $garantie->nature_garantie,
                        'type' => $garantie->type,
                        'age_min' => $garantie->age_min,
                        'age_max' => $garantie->age_max,
                        'duree_cotisation_min' => $garantie->duree_cotisation_min,
                        'duree_cotisation_max' => $garantie->duree_cotisation_max,
                        'duree_contrat_min' => $garantie->duree_contrat_min,
                        'duree_contrat_max' => $garantie->duree_contrat_max,
                        'branche' => $garantie->branche,
                        'description' => $garantie->description,
                    ])->values()->all() ?? [],
                ];
            })
            ->values()
            ->all();
    }
    
    /**
     * Récupérer les agences disponibles pour rendez-vous
     */
    public function getAgencesDisponibles(array $filters = []): array
    {
        $query = Agence::where('status', 'actif')
            ->with(['horaires' => function ($q) {
                $q->orderByRaw("FIELD(jour, 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche')");
            }])
            ->whereHas('horaires', function ($q) {
                $q->where('rendez_vous_actif', true)
                  ->where('ferme', false);
            });

        if (isset($filters['ville'])) {
            $query->where('ville', 'LIKE', "%{$filters['ville']}%");
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $search = $filters['search'];
                $q->where('libelle', 'LIKE', "%{$search}%")
                  ->orWhere('adresse', 'LIKE', "%{$search}%")
                  ->orWhere('ville', 'LIKE', "%{$search}%")
                  ->orWhere('quartier', 'LIKE', "%{$search}%");
            });
        }

        $dateFiltre = $filters['date'] ?? null;

        return $query->orderBy('libelle')->get()->map(function ($agence) use ($dateFiltre) {
            $horairesRdv = $agence->horaires->filter(function ($horaire) {
                return $horaire->rendez_vous_actif && !$horaire->ferme;
            });

            return [
                'uuid_agence' => $agence->uuid_agence,
                'code' => $agence->code,
                'libelle' => $agence->libelle,
                'adresse' => $agence->adresse,
                'ville' => $agence->ville,
                'quartier' => $agence->quartier,
                'telephone' => $agence->telephone,
                'email' => $agence->email,
                'latitude' => $agence->latitude,
                'longitude' => $agence->longitude,
                'jours_rdv' => $horairesRdv->map(function ($horaire) {
                    return [
                        'jour' => $horaire->jour,
                        'jour_label' => $horaire->jour_label,
                        'capacite_rendez_vous' => $horaire->capacite_rendez_vous,
                    ];
                })->values()->toArray(),
                'horaires' => $agence->horaires->map(function ($horaire) {
                    return [
                        'jour' => $horaire->jour,
                        'jour_label' => $horaire->jour_label,
                        'heure_ouverture' => $horaire->heure_ouverture,
                        'heure_fermeture' => $horaire->heure_fermeture,
                        'ferme' => $horaire->ferme,
                        'rendez_vous_actif' => $horaire->rendez_vous_actif,
                    ];
                }),
                // les gestionnaires qui reçoivent les rendez-vous associés à l'agence (uniquement rôle gestionnaire_rdv)
                'gestionnaires' => $agence->users->filter(function ($gestionnaire) {
                    return $gestionnaire && method_exists($gestionnaire, 'hasRole') && $gestionnaire->hasRole('gestionnaire_rdv');
                })->map(function ($gestionnaire) use ($dateFiltre, $agence) {
                    // Compter les RDV transmis pour ce gestionnaire à la date spécifiée
                    $rdvCount = 0;
                    if ($dateFiltre) {
                        $rdvCount = Rdv::where('gestionnaire_uuid', $gestionnaire->uuid_user)
                            ->where(function ($q) use ($agence) {
                                $q->where('agence_effective_uuid', $agence->uuid_agence);
                            })
                            ->where('status', 'transmis')
                            ->where(function ($q) use ($dateFiltre) {
                                $q->whereDate('date_rdv_effective', $dateFiltre);
                            })
                            ->count();
                    }

                    return [
                        'uuid_user' => $gestionnaire->uuid_user,
                        'login' => $gestionnaire->login,
                        'email' => $gestionnaire->email,
                        'nom' => $gestionnaire->details?->nom,
                        'prenoms' => $gestionnaire->details?->prenoms,
                        'full_name' => trim(($gestionnaire->details?->nom ?? '') . ' ' . ($gestionnaire->details?->prenoms ?? '')),
                        'rdv_count' => $rdvCount,
                    ];
                }),
            ];
        })->toArray();
    }

    /**
     * Récupérer les dates disponibles pour une agence
     */
    public function getDatesDisponibles(string $agenceUuid, int $mois, int $annee): array
    {
        $agence = Agence::where('uuid_agence', $agenceUuid)
            ->where('status', 'actif')
            ->with(['horaires' => function ($q) {
                $q->where('rendez_vous_actif', true)
                  ->where('ferme', false);
            }])
            ->first();

        if (!$agence) {
            return [
                'success' => false,
                'code' => 'AGENCE_NOT_FOUND',
                'message' => 'Cette agence ne reçoit pas sur rendez-vous.',
            ];
        }

        $horairesRdv = $agence->horaires->keyBy('jour');

        $dateDebut = Carbon::create($annee, $mois, 1)->startOfDay();
        $dateFin = Carbon::create($annee, $mois, 1)->endOfMonth()->endOfDay();

        $joursFeries = JourFerie::whereBetween('date', [$dateDebut, $dateFin])
            ->orWhere(function ($query) use ($dateDebut, $dateFin) {
                $query->where('est_recurrent', true)
                    ->whereRaw('DAYOFYEAR(date) BETWEEN ? AND ?', [
                        $dateDebut->dayOfYear,
                        $dateFin->dayOfYear
                    ]);
            })
            ->pluck('date')
            ->map(function ($date) {
                return $date instanceof Carbon ? $date->format('Y-m-d') : $date;
            })
            ->toArray();

        $period = CarbonPeriod::create($dateDebut, $dateFin);
        $datesDisponibles = [];

        foreach ($period as $date) {
            $dateStr = $date->format('Y-m-d');
            $jourSemaine = strtolower($date->locale('fr')->dayName);

            if (!$horairesRdv->has($jourSemaine)) {
                continue;
            }

            $horaire = $horairesRdv->get($jourSemaine);

            if (in_array($date->dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY])) {
                continue;
            }

            if (in_array($dateStr, $joursFeries)) {
                continue;
            }

            if (BordereauRdv::isDateCloturee($date)) {
                continue;
            }

            $nbRdv = Rdv::where('agence_souhaiter_uuid', $agenceUuid)
                ->where(function ($query) use ($dateStr) {
                    $query->whereDate('date_rdv_effective', $dateStr)
                        ->orWhere(function ($query) use ($dateStr) {
                            $query->whereNull('date_rdv_effective')
                                ->whereDate('date_rdv_souhaiter', $dateStr);
                        });
                })
                // ->whereDate('date_rdv_souhaiter', $dateStr)
                ->whereNotIn('status', ['annule', 'rejete', 'traite', 'expire'])
                ->count();

            $capaciteMax = $horaire->capacite_rendez_vous ?? 0;
            $placesRestantes = $capaciteMax - $nbRdv;

            $datesDisponibles[] = [
                'date' => $dateStr,
                'date_formatee' => $date->locale('fr')->translatedFormat('l d F Y'),
                'jour_semaine' => $jourSemaine,
                'places_restantes' => max(0, $placesRestantes),
                'disponible' => $placesRestantes > 0,
                'capacite_max' => $capaciteMax,
            ];
        }

        return $datesDisponibles;
    }

    /**
     * Vérifier si un client peut prendre un rendez-vous
     */
    public function verifierEligibiliteClient(User $client, int $contratId, string $agenceUuid, string $dateRdv): array
    {
        $errors = [];

        $rdvRecent = Rdv::forClient($client->uuid_user)
            ->forContrat($contratId)
            ->whereDate('created_at', '>=', now()->subDays(30))
            ->whereNotIn('status', ['rejete', 'annule'])
            ->first();

        if ($rdvRecent) {
            $errors[] = [
                'success' => false,
                'code' => 'RDV_RECENT',
                'message' => 'Vous avez déjà un rendez-vous sur ce contrat datant de moins de 30 jours.',
                'rdv_code' => $rdvRecent->code,
                'rdv_date' => $rdvRecent->date_rdv_souhaiter,
                'rdv_status' => $rdvRecent->status,
            ];
        }

        $jourSemaine = strtolower(Carbon::parse($dateRdv)->locale('fr')->dayName);
        $horaire = Agence::where('uuid_agence', $agenceUuid)
            ->first()
            ?->horaires()
            ->where('jour', $jourSemaine)
            ->where('rendez_vous_actif', true)
            ->where('ferme', false)
            ->first();

        if (!$horaire) {
            $errors[] = [
                'success' => false,
                'code' => 'AGENCE_NON_DISPONIBLE',
                'message' => 'Cette agence ne reçoit pas sur rendez-vous ce jour.',
            ];
        }

        $date = Carbon::parse($dateRdv)->startOfDay();
        if ($date->isPast() && !$date->isToday()) {
            $errors[] = [
                'success' => false,
                'code' => 'DATE_DANS_LE_PASSE',
                'message' => 'La date du rendez-vous est déjà passée.',
            ];
        }

        if ($date->isWeekend()) {
            $errors[] = [
                'success' => false,
                'code' => 'DATE_WEEKEND',
                'message' => 'Les rendez-vous ne sont pas disponibles le week-end.',
            ];
        }

        if (JourFerie::isFerie($date)) {
            $errors[] = [
                'success' => false,
                'code' => 'DATE_FERIE',
                'message' => 'Cette date est un jour férié.',
            ];
        }

        if (BordereauRdv::isDateCloturee($date)) {
            $errors[] = [
                'success' => false,
                'code' => 'DATE_CLOTUREE',
                'message' => 'La date du rendez-vous est déjà transférée et n’est plus disponible.',
            ];
        }

        return [
            'success' => true,
            'code' => 'ELIGIBLE',
            'eligible' => empty($errors),
            'message' => 'Eligible',
            'errors' => $errors,
        ];
    }

    /**
     * Créer un rendez-vous
     */
    public function create(array $data, User $client, string $creatorUuid): array
    {
        $assignation = null;
        $rdvData = null;

        DB::transaction(function () use ($data, $client, $creatorUuid, &$assignation, &$rdvData) {
            $eligibilite = $this->verifierEligibiliteClient(
                $client,
                $data['id_contrat'],
                $data['agence_uuid'],
                $data['date_rdv']
            );

            if (!$eligibilite['success']) {
                return [
                    'success' => false,
                    'code' => $eligibilite['code'] ?? 'CLIENT_NON_ELIGIBLE',
                    'message' => $eligibilite['message'] ?? 'Le client n\'est pas éligible pour un rendez-vous.',
                    'eligibilite' => $eligibilite['errors'] ?? [],
                ];
            }

            $dateDispo = $this->verifierDateDisponible(
                $data['agence_uuid'],
                $data['date_rdv']
            );

            if (!$dateDispo['disponible']) {
                return [
                    'success' => false,
                    'code' => $dateDispo['code'] ?? 'DATE_NON_DISPONIBLE',
                    'message' => $dateDispo['message'] ?? 'La date sélectionnée n\'est pas disponible.',
                ];
            }

            $motif = TypePrestation::where('uuid_type_prestation', $data['motif_rdv'])
                ->where('status', 'actif')
                ->first();

            if (!$motif) {
                return [
                    'success' => false,
                    'code' => 'MOTIF_NON_DISPONIBLE',
                    'message' => 'Ce motif n\'est pas disponible.',
                ];
            }

            $contrat = Produit::where('code', $data['code_produit'])->first();
            if ($contrat) {
                $association = $contrat->typePrestations()
                    ->where('uuid_type_prestation', $data['motif_rdv'])
                    ->wherePivot('status', 'actif')
                    ->exists();

                if (!$association) {
                    return [
                        'success' => false,
                        'code' => 'MOTIF_NON_DISPONIBLE',
                        'message' => 'Ce motif n\'est pas disponible pour ce contrat.', 
                    ];
                }
            }

            $rdv = Rdv::create([
                'uuid_rdvs' => (string) Str::uuid(),
                'code' => RefgenerateCode(Rdv::class, 'RDV-', 'code'),
                'client_uuid' => $client->uuid_user,
                'id_contrat' => $data['id_contrat'],
                'motif_rdv' => $data['motif_rdv'],
                'demandeur' => $data['demandeur'] ?? 'Souscripteur',
                'date_rdv_souhaiter' => $data['date_rdv'],
                'agence_souhaiter_uuid' => $data['agence_uuid'],
                'status' => 'en_attente',
                'created_by' => $creatorUuid,
            ]);

            ActivityLog::log([
                'user_uuid' => $creatorUuid,
                'action' => 'create',
                'action_type' => 'crud',
                'module' => 'rdvs',
                'description' => "Création du rendez-vous pour le client {$client->email}",
                'resource_type' => 'rdv',
                'resource_id' => $rdv->uuid_rdvs,
                'new_values' => $rdv->toArray(),
                'level' => 'info',
            ]);

            $this->notificationService->create([
                'user_uuid' => $creatorUuid,
                'group_notif_uuid' => $this->getRdvGroupUuid(),
                'title' => '⚠️ Prise de rendez-vous. Code : '. $rdv->code,
                'body' => 'Votre rendez-vous N°' . $rdv->code . ' est en attente de validation. Vous allez recevoir un message de confirmation après validation.',
                'type' => 'RENDEZ-VOUS',
                'metadata' => [
                    'rdv' => $rdv->toArray(),
                    'client' => $client->toArray(),
                    'agence' => Agence::where('uuid_agence', $data['agence_uuid'])->first()->toArray(),
                    'motif' => $motif->toArray(),
                ],
                'channel' => 'database',
                'created_by' => null,
            ]);

            DB::afterCommit(function () use ($rdv, &$assignation) {
                // Log::info("Tentative d'assignation automatique du rendez-vous {$rdv->code} après création.");
                $assignation = $this->routingService->assignerAutomatiquement($rdv);
                // Log::info("Assignation automatique du rendez-vous {$rdv->code} : {$assignation['success']}");
            });

            $rdvData = $rdv->load(['client', 'motif', 'agenceSouhaitee']);

            return [
                'success' => true,
                'code' => 'RDV_CREATED',
                'message' => 'Rendez-vous créé avec succès. Code : ' . $rdv->code,
                'data' => $rdvData,
                'assignation_automatique' => null,
            ];
        });

        return [
            'success' => true,
            'code' => 'RDV_CREATED',
            'message' => 'Rendez-vous créé avec succès.',
            'data' => $rdvData ?? null,
            'assignation_automatique' => $assignation,
        ];
    }

    /**
     * Vérifier si une date est disponible pour une agence
     */
    public function verifierDateDisponible(string $agenceUuid, string $dateRdv): array
    {
        $date = Carbon::parse($dateRdv);
        $dateStr = $date->format('Y-m-d');
        $jourSemaine = strtolower($date->locale('fr')->dayName);

        $agence = Agence::where('uuid_agence', $agenceUuid)
            ->where('status', 'actif')
            ->with(['horaires' => function ($q) use ($jourSemaine) {
                $q->where('jour', $jourSemaine)
                  ->where('rendez_vous_actif', true)
                  ->where('ferme', false);
            }])
            ->first();

        if (!$agence || $agence->horaires->isEmpty()) {
            return [
                'success' => false,
                'disponible' => false,
                'code' => 'AGENCE_NON_DISPONIBLE',
                'message' => 'Cette agence ne reçoit pas sur rendez-vous ce jour.',
            ];
        }

        $horaire = $agence->horaires->first();

        if (in_array($date->dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY])) {
            return [
                'success' => false,
                'disponible' => false,
                'code' => 'DATE_WEEKEND',
                'message' => 'Les rendez-vous ne sont pas disponibles le week-end.',
            ];
        }

        if (JourFerie::isFerie($date)) {
            return [
                'success' => false,
                'disponible' => false,
                'code' => 'DATE_FERIE',
                'message' => 'Cette date est un jour férié.',
            ];
        }

        if (BordereauRdv::isDateCloturee($date)) {
            return [
                'success' => false,
                'disponible' => false,
                'code' => 'DATE_CLOTUREE',
                'message' => 'Cette date appartient à une période clôturée.',
            ];
        }

        $capaciteMax = $horaire->capacite_rendez_vous ?? 0;
        $nbRdv = Rdv::where('agence_souhaiter_uuid', $agenceUuid)
            ->where(function ($query) use ($dateStr) {
                $query->whereDate('date_rdv_effective', $dateStr)
                    ->orWhere(function ($query) use ($dateStr) {
                        $query->whereNull('date_rdv_effective')
                            ->whereDate('date_rdv_souhaiter', $dateStr);
                    });
            })
            ->whereNotIn('status', ['annule', 'rejete', 'traite', 'expire'])
            ->count();

        $placesRestantes = $capaciteMax - $nbRdv;

        if ($placesRestantes <= 0) {
            return [
                'success' => false,
                'disponible' => false,
                'code' => 'DATE_NON_DISPONIBLE',
                'message' => 'Plus de places disponibles pour cette date.',
                'places_restantes' => 0,
            ];
        }

        return [
            'success' => true,
            'disponible' => true,
            'code' => 'DATE_DISPONIBLE',
            'places_restantes' => $placesRestantes,
            'capacite_max' => $capaciteMax,
            'message' => 'Date disponible',
        ];
    }

    /**
     * Récupérer les rendez-vous d'un client
     */
    public function getRdvClient(string $clientUuid, array $filters = [], int $perPage = 10)
    {
        $query = Rdv::forClient($clientUuid)
            ->with(['motif', 'agenceSouhaitee', 'agenceEffective', 'gestionnaire', 'contrat'])
            ->orderBy('created_at', 'desc');

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['id_contrat'])) {
            $query->where('id_contrat', $filters['id_contrat']);
        }

        if (isset($filters['search'])) {
            $query->search($filters['search']);
        }

        // Filtrer par type de motif si spécifié
        if (isset($filters['motif_type'])) {
            $motifType = $filters['motif_type'];
            $query->whereJsonContains('motif_traitement', [$motifType]);
        }

        // Filtrer par présence de motifs (non vide)
        if (isset($filters['has_motifs']) && $filters['has_motifs']) {
            $query->whereNotNull('motif_traitement')->where('motif_traitement', '!=', '[]');
        }

        // Filtrer par absence de motifs
        if (isset($filters['has_motifs']) && !$filters['has_motifs']) {
            $query->whereNull('motif_traitement')->orWhere('motif_traitement', '[]');
        }

        // Filtrer par motifs automatiques uniquement
        if (isset($filters['automatic_only']) && $filters['automatic_only']) {
            $query->whereJsonContains('motif_traitement', 'automatique');
        }

        // Filtrer par motifs manuels uniquement (sans automatique)
        if (isset($filters['manual_only']) && $filters['manual_only']) {
            $query->whereJsonLength('motif_traitement', '>', 0)
                  ->whereJsonDoesntContain('motif_traitement', 'automatique');
        }

        return $query->paginate($perPage);
    }

    /**
     * Récupérer les rendez-vous d'une agence
     */
    public function getRdvAgence(string $agenceUuid, array $filters = [], int $perPage = 20)
    {
        $query = Rdv::where('agence_effective_uuid', $agenceUuid)
            ->with(['client', 'motif', 'gestionnaire', 'agenceEffective'])
            ->orderBy('created_at', 'desc');

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['date'])) {
            $query->whereDate('date_rdv_effective', $filters['date']);
        }

        if (isset($filters['gestionnaire_uuid'])) {
            $query->where('gestionnaire_uuid', $filters['gestionnaire_uuid']);
        }

        if (isset($filters['is_present'])) {
            $query->where('is_present', $filters['is_present']);
        }

        // Filtrer par type de motif si spécifié
        if (isset($filters['motif_type'])) {
            $motifType = $filters['motif_type'];
            $query->whereJsonContains('motif_traitement', [$motifType]);
        }

        // Filtrer par présence de motifs
        if (isset($filters['has_motifs']) && $filters['has_motifs']) {
            $query->whereNotNull('motif_traitement')->where('motif_traitement', '!=', '[]');
        }

        return $query->paginate($perPage);
    }

    /**
     * Mettre à jour le statut d'un rendez-vous
     */
    public function updateStatus(Rdv $rdv, string $status, string $updaterUuid, array $data = []): Rdv
    {
        return DB::transaction(function () use ($rdv, $status, $data, $updaterUuid) {
            $oldValues = $rdv->toArray();

            $rdv->update([
                'status' => $status,
                'date_traitement' => now(),
                'motif_traitement' => array_merge(
                    $rdv->motif_traitement ?? [],
                    $data
                ),
                'observation' => $data['observation'] ?? $rdv->observation,
                'updated_by' => $updaterUuid,
            ]);

            $this->notificationService->create([
                'user_uuid' => $updaterUuid,
                'group_notif_uuid' => $this->getRdvGroupUuid(),
                'title' => 'Mise à jour du statut du Rendez-vous '. $rdv->code,
                'body' => "Le statut du rendez-vous {$rdv->code} a changé de {$oldValues['status']} vers {$status}",
                'type' => 'RENDEZ-VOUS',
                'metadata' => [
                    'rdv' => $rdv->toArray(),
                    'status' => $status,
                    'old_status' => $oldValues['status'],
                    'updater_uuid' => $updaterUuid,
                ],
                'channel' => 'database',
                'created_by' => null,

            ]);

            ActivityLog::log([
                'user_uuid' => $updaterUuid,
                'action' => 'update_status',
                'action_type' => 'crud',
                'module' => 'rdvs',
                'description' => "Mise à jour du statut du rendez-vous {$rdv->code} vers {$status}",
                'resource_type' => 'rdv',
                'resource_id' => $rdv->uuid_rdvs,
                'old_values' => $oldValues,
                'new_values' => $rdv->toArray(),
                'level' => 'info',
            ]);

            return $rdv->fresh();
        });
    }

    /**
     * Signaler la présence d'un client
     */
    public function signalerPresence(Rdv $rdv, string $clientUuid, array $data = []): array
    {
        if ($rdv->client_uuid !== $clientUuid) {

            return [
                'success' => false,
                'code' => 'RDV_CLIENT_DIFFERENT',
                'message' => 'Ce rendez-vous ne vous appartient pas.',
            ];
        }

        if (!in_array($rdv->status, ['transmis'])) {
            return [
                'success' => false,
                'code' => 'RDV_NON_CONFIRME',
                'message' => 'Ce rendez-vous n\'est pas dans un état permettant de signaler la présence.',
            ];
        }

        if ($rdv->date_rdv_souhaiter->format('Y-m-d') !== now()->format('Y-m-d')) {
            return [
                'success' => false,
                'code' => 'RDV_NON_PREVU',
                'message' => 'Le rendez-vous n\'est pas prévu aujourd\'hui.',
            ];
        }

        // Les coordonnées GPS sont obligatoires pour valider la présence
        if (!isset($data['latitude']) || !isset($data['longitude'])) {
            return [
                'success' => false,
                'code' => 'RDV_NO_COORDINATES',
                'message' => 'Coordonnées GPS requises pour signaler la présence.',
            ];
        }

        $agence = $rdv->agenceSouhaitee;
        if ($agence && $agence->latitude && $agence->longitude) {
            $distanceMeters = $this->calculerDistance(
                $data['latitude'],
                $data['longitude'],
                $agence->latitude,
                $agence->longitude
            );

            // Seuil en mètres (20m)
            if ($distanceMeters > 20) {
                return [
                    'success' => false,
                    'code' => 'RDV_DISTANCE',
                    'message' => "Vous n'êtes pas à proximité de l'agence. Veuillez vous rapprocher d'au moins 20 mètres pour valider votre présence.",
                ];
            }
        }

        $this->notificationService->create([
            'user_uuid' => $clientUuid,
            'group_notif_uuid' => $this->getRdvGroupUuid(),
            'title' => '✅ Présence signalée',
            'body' => "Vous avez signalé votre présence pour le rendez-vous {$rdv->code}",
            'type' => 'RENDEZ-VOUS',
            'metadata' => [
                'rdv' => $rdv->toArray(),
                'client_uuid' => $clientUuid,
            ],
            'channel' => 'database',
            'created_by' => null,
        ]);

        $rdv->update([
            'is_present' => true,
            'present_at' => now(),
            'updated_by' => $clientUuid,
        ]);

        // Notifier le gestionnaire si présent
        if ($rdv->gestionnaire_uuid) {
            $clientNom = $rdv->client?->details?->nom ?? '';
            $clientPrenoms = $rdv->client?->details?->prenoms ?? '';
            $clientLabel = trim($clientPrenoms . ' ' . $clientNom) ?: ($rdv->client?->email ?? 'Client');

            $this->notificationService->create([
                'user_uuid' => $rdv->gestionnaire_uuid,
                'group_notif_uuid' => $this->getRdvGroupUuid(),
                'title' => '👥 Client arrivé en agence',
                'body' => "Le client {$clientLabel} a signalé sa présence pour le RDV N° {$rdv->code}.",
                'type' => 'RENDEZ-VOUS',
                'metadata' => [
                    'rdv_uuid' => $rdv->uuid_rdvs,
                    'rdv_code' => $rdv->code,
                    'client_uuid' => $clientUuid,
                    'action' => 'presence_signalée',
                ],
                'channel' => 'database',
                'created_by' => null,
            ]);
        }

        return [
            'success' => true,
            'code' => 'RDV_PRESENCE',
            'message' => 'Vous avez signalé votre présence.',
            'data' => $rdv->fresh(),
        ];
    }

    /**
     * Calculer la distance entre deux points GPS (en km)
     */
    private function calculerDistance($lat1, $lon1, $lat2, $lon2): float
    {
           // Retourne la distance en mètres entre deux coordonnées (Haversine)
           $earthRadiusKm = 6371;
           $dLat = deg2rad($lat2 - $lat1);
           $dLon = deg2rad($lon2 - $lon1);
           $a = sin($dLat / 2) * sin($dLat / 2) +
               cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
               sin($dLon / 2) * sin($dLon / 2);
           $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
           $distanceKm = $earthRadiusKm * $c;
           return $distanceKm * 1000; // mètres
    }


    /**
     * Clients arrivés et signalés en agence
     */

    // public function getClientsArrives(array $filters = [], int $perPage = 15, bool $asArray = false): array|\Illuminate\Contracts\Pagination\LengthAwarePaginator

    public function getClientsArrives(array $filters = [], int $perPage = 15, bool $asArray = false)
    {
        $query = Rdv::query()
        ->select([
            'uuid_rdvs',
            'client_uuid',
            'code',
            'motif_rdv',
            'status',
            'date_rdv_effective',
            'present_at',
            'created_at',
            'agence_souhaiter_uuid',
            'agence_effective_uuid',
            'gestionnaire_uuid',
            'is_present',
        ])
        ->where('is_present', true)
        ->with([
            'client' => function ($query) {
                $query->select('uuid_user', 'email')
                    ->with([
                        'details' => function ($q) {
                            $q->select('user_uuid', 'nom', 'prenoms', 'mobile_1');
                        },
                    ]);
            },
            'motif' => function ($query) {
                $query->select('uuid_type_prestation', 'libelle', 'code', 'impact');
            },
            'agenceSouhaitee' => function ($query) {
                $query->select('uuid_agence', 'libelle', 'code', 'ville', 'adresse');
            },
            'agenceEffective' => function ($query) {
                $query->select('uuid_agence', 'libelle', 'code', 'ville', 'adresse');
            },
            'gestionnaire' => function ($query) {
                $query->select('uuid_user', 'email')
                    ->with([
                        'details' => function ($q) {
                            $q->select('user_uuid', 'nom', 'prenoms');
                        },
                    ]);
            },
        ]);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('code', 'LIKE', "%{$search}%")
                  ->orWhereHas('client', function ($sub) use ($search) {
                      $sub->where('email', 'LIKE', "%{$search}%")
                          ->orWhere('login', 'LIKE', "%{$search}%");
                  })
                  ->orWhereHas('client.details', function ($sub) use ($search) {
                      $sub->where('nom', 'LIKE', "%{$search}%")
                          ->orWhere('prenoms', 'LIKE', "%{$search}%")
                          ->orWhere('mobile_1', 'LIKE', "%{$search}%");
                  })
                  ->orWhereHas('motif', function ($sub) use ($search) {
                      $sub->where('libelle', 'LIKE', "%{$search}%")
                          ->orWhere('code', 'LIKE', "%{$search}%");
                  });
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        } 
        // else {
        //     $query->whereIn('status', ['transmis']);
        // }

        if (isset($filters['is_present'])) {
            $query->where('is_present', (bool) $filters['is_present']);
        }

        if (!empty($filters['agence_uuid'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('agence_effective_uuid', $filters['agence_uuid']);
            });
        }

        if (!empty($filters['gestionnaire_uuid'])) {
            $query->where('gestionnaire_uuid', $filters['gestionnaire_uuid']);
        }

        if (!empty($filters['motif_uuid'])) {
            $query->where('motif_rdv', $filters['motif_uuid']);
        }

        if (!empty($filters['date'])) {
            $query->whereDate('date_rdv_effective', $filters['date']);
            // if (!isset($filters['status'])) {
            //     $query->whereIn('status', ['transmis']);
            // }
        }

        if (!empty($filters['date_debut'])) {
            $query->whereDate('date_rdv_effective', '>=', $filters['date_debut']);
        }
        if (!empty($filters['date_fin'])) {
            $query->whereDate('date_rdv_effective', '<=', $filters['date_fin']);
        }

        $sortBy = $filters['sort_by'] ?? 'present_at';
        $sortOrder = $filters['sort_order'] ?? 'asc';
        $query->orderBy($sortBy, $sortOrder);

        if ($asArray) {

            return $query->get()->map(function ($rdv) {
                $estEnRetard = $rdv->date_rdv_effective && $rdv->date_rdv_effective->isPast();

                return [
                    'uuid_rdvs' => $rdv->uuid_rdvs,
                    'code' => $rdv->code,
                    'client' => [
                        'uuid_user' => $rdv->client?->uuid_user,
                        'nom_complet' => $rdv->client?->details ?
                            trim(($rdv->client->details->nom ?? '') . ' ' . ($rdv->client->details->prenoms ?? ''))
                            : ($rdv->client?->email ?? ''),
                        'email' => $rdv->client?->email,
                        'mobile' => $rdv->client?->details?->mobile_1,
                    ],
                    'motif' => $rdv->motif ? [
                        'uuid_type_prestation' => $rdv->motif->uuid_type_prestation,
                        'libelle' => $rdv->motif->libelle,
                        'code' => $rdv->motif->code,
                        'impact' => $rdv->motif->impact,
                        'impact_label' => $rdv->motif->getImpactLabel(),
                    ] : null,
                    'agence' => $rdv->agenceEffective ? [
                        'uuid_agence' => $rdv->agenceEffective->uuid_agence,
                        'libelle' => $rdv->agenceEffective->libelle,
                        'ville' => $rdv->agenceEffective->ville,
                        'adresse' => $rdv->agenceEffective->adresse,
                    ] : null,
                    'gestionnaire' => $rdv->gestionnaire ? [
                        'uuid_user' => $rdv->gestionnaire->uuid_user,
                        'nom_complet' => trim(($rdv->gestionnaire?->details?->nom ?? '') . ' ' . ($rdv->gestionnaire?->details?->prenoms ?? '')),
                        'email' => $rdv->gestionnaire?->email,
                    ] : null,
                    'date_rdv_effective' => $rdv->date_rdv_effective?->format('d/m/Y'),
                    'date_effective_original' => $rdv->date_rdv_effective?->format('Y-m-d'),
                    'date_arrivee' => $rdv->present_at?->format('d/m/Y H:i'),
                    'status' => $rdv->status,
                    'status_label' => Rdv::STATUS[$rdv->status] ?? $rdv->status,
                    'date_creation' => $rdv->created_at?->format('Y-m-d H:i:s'),
                    'is_present' => (bool) $rdv->is_present,
                    'est_en_retard' => $estEnRetard,
                    'est_urgent' => $rdv->date_rdv_effective && $rdv->date_rdv_effective->diffInDays(now()) <= 3,
                    'heure_arrivee' => $rdv->present_at?->format('H:i'),
                ];
            })->values()->all();
        }

        return $query->paginate($perPage);
    }



    /**
     * Récupérer la liste des rendez-vous avec filtres
     */
    public function getList(array $filters, int $perPage = 15, bool $asArray = false)
    {
        $query = Rdv::query()
            ->with([
                'client.details',
                'motif',
                'agenceSouhaitee',
                'agenceEffective',
                'gestionnaire.details',
                'contrat',
            ]);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('code', 'LIKE', "%{$search}%")
                  ->orWhereHas('client', function ($sub) use ($search) {
                      $sub->where('email', 'LIKE', "%{$search}%")
                          ->orWhere('login', 'LIKE', "%{$search}%");
                  })
                  ->orWhereHas('client.details', function ($sub) use ($search) {
                      $sub->where('nom', 'LIKE', "%{$search}%")
                          ->orWhere('prenoms', 'LIKE', "%{$search}%")
                          ->orWhere('mobile_1', 'LIKE', "%{$search}%");
                  })
                  ->orWhereHas('motif', function ($sub) use ($search) {
                      $sub->where('libelle', 'LIKE', "%{$search}%")
                          ->orWhere('code', 'LIKE', "%{$search}%");
                  });
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['is_present'])) {
            $query->where('is_present', $filters['is_present']);
        }

        if (!empty($filters['agence_uuid'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('agence_souhaiter_uuid', $filters['agence_uuid'])
                  ->orWhere('agence_effective_uuid', $filters['agence_uuid']);
            });
        }

        if (!empty($filters['gestionnaire_uuid'])) {
            $query->where('gestionnaire_uuid', $filters['gestionnaire_uuid']);
        }

        if (!empty($filters['motif_uuid'])) {
            $query->where('motif_rdv', $filters['motif_uuid']);
        }

        if (!empty($filters['date'])) {
            $query->whereDate('date_rdv_effective', $filters['date']);
            // if (!isset($filters['status'])) {
            //     $query->whereIn('status', ['transmis']);
            // }
        }

        if (!empty($filters['date_debut'])) {
            $query->whereDate('date_rdv_effective', '>=', $filters['date_debut']);
            // $query->whereDate('date_rdv_souhaiter', '>=', $filters['date_debut']);
        }
        if (!empty($filters['date_fin'])) {
            $query->whereDate('date_rdv_effective', '<=', $filters['date_fin']);
            // $query->whereDate('date_rdv_souhaiter', '<=', $filters['date_fin']);
        }

        // Filtrer par type de motif si spécifié
        if (!empty($filters['motif_type'])) {
            $motifType = $filters['motif_type'];
            $query->whereJsonContains('motif_traitement', [$motifType]);
        }

        // Filtrer par présence de motifs
        if (isset($filters['has_motifs']) && $filters['has_motifs']) {
            $query->whereNotNull('motif_traitement')->where('motif_traitement', '!=', '[]');
        }

        // Filtrer par motifs automatiques uniquement
        if (isset($filters['automatic_only']) && $filters['automatic_only']) {
            $query->whereJsonContains('motif_traitement', 'automatique');
        }

        // Filtrer par motifs manuels uniquement (sans automatique)
        if (isset($filters['manual_only']) && $filters['manual_only']) {
            $query->whereJsonLength('motif_traitement', '>', 0)
                  ->whereJsonDoesntContain('motif_traitement', 'automatique');
        }

        $sortBy = $filters['sort_by'] ?? 'date_rdv_effective' ?? 'date_rdv_souhaiter';
        $sortOrder = $filters['sort_order'] ?? 'asc';
        $query->orderBy($sortBy, $sortOrder);


        if ($asArray) {
            return $query->get()->map(function ($rdv) {
                $estEnRetard = $rdv->date_rdv_effective && $rdv->date_rdv_effective->isPast();

                return [
                    'uuid_rdvs' => $rdv->uuid_rdvs,
                    'code' => $rdv->code,
                    'client' => [
                        'uuid_user' => $rdv->client?->uuid_user,
                        'nom_complet' => $rdv->client?->details ?
                            trim(($rdv->client->details->nom ?? '') . ' ' . ($rdv->client->details->prenoms ?? ''))
                            : ($rdv->client?->email ?? ''),
                        'email' => $rdv->client?->email,
                        'mobile' => $rdv->client?->details?->mobile_1,
                    ],
                    'motif' => $rdv->motif ? [
                        'uuid_type_prestation' => $rdv->motif->uuid_type_prestation,
                        'libelle' => $rdv->motif->libelle,
                        'code' => $rdv->motif->code,
                        'impact' => $rdv->motif->impact,
                        'impact_label' => $rdv->motif->getImpactLabel(),
                    ] : null,
                    'agence' => $rdv->agenceEffective ? [
                        'uuid_agence' => $rdv->agenceEffective->uuid_agence,
                        'libelle' => $rdv->agenceEffective->libelle,
                        'ville' => $rdv->agenceEffective->ville,
                        'adresse' => $rdv->agenceEffective->adresse,
                    ] : null,
                    'date_rdv_effective' => $rdv->date_rdv_effective?->format('d/m/Y'),
                    'status' => $rdv->status,
                    'status_label' => Rdv::STATUS[$rdv->status] ?? $rdv->status,
                    'is_present' => (bool) $rdv->is_present,
                    'est_en_retard' => $estEnRetard,
                    'heure_arrivee' => $rdv->present_at?->format('H:i'),
                    'temps_attente' => $estEnRetard && $rdv->date_rdv_effective ?
                        $rdv->date_rdv_effective->diffInMinutes(now()) . ' min' :
                        null,
                    'est_prioritaire' => (bool) $rdv->is_present,
                ];
            })->values()->all();
        }

        return $query->paginate($perPage);
    }

    /**
     * Formater les données pour l'affichage
     */
    public function formatForList($rdvs): array
    {
        $data = [];

        foreach ($rdvs as $rdv) {
            $data[] = [
                'uuid_rdvs' => $rdv->uuid_rdvs,
                'code' => $rdv->code,
                'date_creation' => $rdv->created_at?->format('Y-m-d'),
                'date_rdv' => $rdv->date_rdv_effective?->format('Y-m-d') ?? $rdv->date_rdv_souhaiter?->format('Y-m-d'),
                'heure_rdv' => $rdv->date_rdv_effective?->format('H:i') ?? $rdv->date_rdv_souhaiter?->format('H:i'),
                
                // Client
                'client' => [
                    'uuid_user' => $rdv->client?->uuid_user,
                    'nom' => $rdv->client?->details?->nom ?? '',
                    'prenoms' => $rdv->client?->details?->prenoms ?? '',
                    'email' => $rdv->client?->email ?? '',
                    'mobile' => $rdv->client?->details?->mobile_1 ?? '',
                    'nom_complet' => $this->formatNomComplet($rdv->client?->details?->nom, $rdv->client?->details?->prenoms),
                ],
                
                // Motif
                'motif' => [
                    'uuid' => $rdv->motif?->uuid_type_prestation,
                    'libelle' => $rdv->motif?->libelle,
                    'code' => $rdv->motif?->code,
                    'impact' => $rdv->motif?->impact,
                    'impact_label' => $rdv->motif?->getImpactLabel(),
                ],
                
                // Agence
                'agence' => [
                    'souhaitee' => $rdv->agenceSouhaitee ? [
                        'uuid' => $rdv->agenceSouhaitee->uuid_agence,
                        'libelle' => $rdv->agenceSouhaitee->libelle,
                        'code' => $rdv->agenceSouhaitee->code,
                        'ville' => $rdv->agenceSouhaitee->ville,
                    ] : null,
                    'effective' => $rdv->agenceEffective ? [
                        'uuid' => $rdv->agenceEffective->uuid_agence,
                        'libelle' => $rdv->agenceEffective->libelle,
                        'code' => $rdv->agenceEffective->code,
                        'ville' => $rdv->agenceEffective->ville,
                    ] : null,
                ],
                
                // Contrat
                'contrat' => $rdv->contrat ? [
                    'contrat_id' => $rdv->contrat->contrat_id,
                    'client_number' => $rdv->contrat->client_number,
                    'code_produit' => $rdv->contrat->code_produit,
                    'libelle_produit' => $rdv->contrat->libelle_produit,
                    'code_produit_formule' => $rdv->contrat->code_produit_formule,
                    'libelle_produit_formule' => $rdv->contrat->libelle_produit_formule,
                ] : null,
                
                // Gestionnaire
                'gestionnaire' => $rdv->gestionnaire ? [
                    'uuid_user' => $rdv->gestionnaire->uuid_user,
                    'nom_complet' => $this->formatNomComplet(
                        $rdv->gestionnaire?->details?->nom,
                        $rdv->gestionnaire?->details?->prenoms
                    ),
                    'email' => $rdv->gestionnaire?->email,
                ] : null,
                
                // Statut
                'status' => $rdv->status,
                'status_label' => Rdv::STATUS[$rdv->status] ?? $rdv->status,
                'status_color' => $this->getStatusColor($rdv->status),
                'status_badge' => $this->getStatusBadge($rdv->status),
                
                // Délais
                'delais' => $this->calculateDelais($rdv),
                'est_retard' => $this->isInRetard($rdv),
                'bordereau_disponible' => $this->isBordereauDisponible($rdv),
                'nb_rdv_client_30j' => $this->getNbRdvClient30j($rdv),
                
                // Dates
                'date_rdv_formatee' => $rdv->date_rdv_effective?->format('d/m/Y') ?? $rdv->date_rdv_effective?->format('d/m/Y'),
                'heure_rdv_formatee' => $rdv->date_rdv_effective?->format('H:i') ?? $rdv->date_rdv_effective?->format('H:i'),
                'date_creation_formatee' => $rdv->created_at?->format('d/m/Y'),
                
                // Métadonnées
                'is_permitted' => $rdv->is_permitted,
                'is_present' => $rdv->is_present,
                'observation' => $rdv->observation,
            ];
        }

        return $data;
    }

    /**
     * Formater le nom complet
     */
    private function formatNomComplet(?string $nom, ?string $prenoms): string
    {
        if (empty($nom) && empty($prenoms)) {
            return '';
        }
        return trim(($nom ?? '') . ' ' . ($prenoms ?? ''));
    }

    /**
     * Calculer les délais du rendez-vous
     */
    private function calculateDelais(Rdv $rdv): array
    {
        $dateRdv = $rdv->date_rdv_effective ?? $rdv->date_rdv_souhaiter;

        if (!$dateRdv) {
            return [
                'jours' => 0,
                'label' => 'Non défini',
                'classe' => 'text-muted',
                'est_retard' => false,
            ];
        }

        $dateRdv = $dateRdv instanceof \Carbon\Carbon ? $dateRdv : \Illuminate\Support\Carbon::parse($dateRdv);

        $today = now()->startOfDay();
        $dateRdvStart = $dateRdv->copy()->startOfDay();
        $diffDays = $today->diffInDays($dateRdvStart, false);

        if ($diffDays > 0) {
            return [
                'jours' => $diffDays,
                'label' => $diffDays . ' jour' . ($diffDays > 1 ? 's' : '') . ' restant' . ($diffDays > 1 ? 's' : ''),
                'classe' => 'text-success',
                'est_retard' => false,
            ];
        }

        if ($diffDays === 0) {
            return [
                'jours' => 0,
                'label' => 'Aujourd\'hui',
                'classe' => 'text-warning',
                'est_retard' => false,
            ];
        }

        $retard = abs($diffDays);

        return [
            'jours' => -$retard,
            'label' => $retard . ' jour' . ($retard > 1 ? 's' : '') . ' de retard',
            'classe' => 'text-danger',
            'est_retard' => true,
        ];
    }

    /**
     * Vérifier si le rendez-vous est en retard
     */
    private function isInRetard(Rdv $rdv): bool
    {
        if (!$rdv->date_rdv_effective) {
            return false;
        }
        
        // Si le RDV est dans le futur et n'est pas traité
        if ($rdv->date_rdv_effective->isFuture() && !in_array($rdv->status, ['traite', 'annule', 'rejete'])) {
            return false;
        }
        
        // Si le RDV est dans le passé et n'est pas traité
        if ($rdv->date_rdv_effective->isPast() && !in_array($rdv->status, ['traite', 'annule', 'rejete'])) {
            return true;
        }
        
        return false;
    }

    /**
     * Vérifier si le bordereau est disponible pour ce rendez-vous.
     *
     * Logique métier: le bordereau est disponible si une ligne DetailBordereauRdv
     * existe pour ce RDV et que le BordereauRdv associé est en statut 'cloture'.
     */
    private function isBordereauDisponible(Rdv $rdv): bool
    {
        if (!$rdv->exists) {
            Log::error('Le rendez-vous n\'existe pas');
            return false;
        }

        $isExists = DetailBordereauRdv::query()
            ->where('rdv_uuid', $rdv->uuid_rdvs)
            ->where('status', 'traite')
            ->whereHas('bordereauRdv', function ($query) {
                $query->where('status', 'cloture');
            })
            ->exists();
        return $isExists;
    }

    /**
     * Obtenir le nombre de RDV du client dans les 30 derniers jours
     */
    private function getNbRdvClient30j(Rdv $rdv): int
    {
        if (!$rdv->client_uuid) {
            return 0;
        }

        return Rdv::where('client_uuid', $rdv->client_uuid)
            ->whereDate('created_at', '>=', now()->subDays(30))
            ->count();
    }

    /**
     * Obtenir la couleur du statut
     */
    private function getStatusColor(string $status): string
    {
        $colors = [
            'en_attente' => '#FFA726',
            'transmis' => '#7E57C2',
            'traite' => '#66BB6A',
            'annule' => '#EF5350',
            'rejete' => '#EF5350',
            'reporte' => '#FFA726',
            'expire' => '#78909C',
        ];
        return $colors[$status] ?? '#9E9E9E';
    }

    /**
     * Obtenir le badge du statut
     */
    private function getStatusBadge(string $status): string
    {
        $badges = [
            'en_attente' => 'badge-info',
            'transmis' => 'badge-primary',
            'traite' => 'badge-success',
            'annule' => 'badge-danger',
            'rejete' => 'badge-danger',
            'reporte' => 'badge-warning',
            'expire' => 'badge-secondary',
        ];
        return $badges[$status] ?? 'badge-secondary';
    }

    /**
     * Statistiques des rendez-vous
     */
    public function getStats(string $clientUuid = null): array
    {
        $query = Rdv::query();

        if ($clientUuid) {
            $query->forClient($clientUuid);
        }

        return [
            'total' => $query->count(),
            'en_attente' => (clone $query)->where('status', 'en_attente')->count(),
            'transmis' => (clone $query)->where('status', 'transmis')->count(),
            'traite' => (clone $query)->where('status', 'traite')->count(),
            'annule' => (clone $query)->where('status', 'annule')->count(),
            'rejete' => (clone $query)->where('status', 'rejete')->count(),
            'reporte' => (clone $query)->where('status', 'reporte')->count(),
            'expire' => (clone $query)->where('status', 'expire')->count(),
        ];
    }



    private function getRdvGroupUuid(): ?string
    {
        $group = GroupNotif::where('code', 'rendezvous')->first();
        return $group?->uuid_group_notif;
    }
}