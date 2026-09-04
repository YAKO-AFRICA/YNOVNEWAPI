<?php
// app/Services/Api/Ynov/RdvService.php

namespace App\Services\Api\Ynov;

use App\Models\Api\Ynov\parameter\ActivityLog;
use App\Models\Api\Ynov\parameter\Agence;
use App\Models\Api\Ynov\BordereauRdv;
use App\Models\Api\Ynov\parameter\JourFerie;
use App\Models\Api\Ynov\parameter\Produit;
use App\Models\Api\Ynov\Rdv;
use App\Models\Api\Ynov\parameter\TypePrestation;
use App\Models\Api\Ynov\parameter\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
// use Illuminate\Validation\ValidationException;

class RdvService
{
    /**
     * Récupérer les motifs disponibles pour un contrat
     */
    // public function getMotifsForContrat(string $codeProduit, string $clientUuid): array
    // {
    //     $produit = Produit::where('code', $codeProduit)->first();
    //     if (!$produit) {
    //         return [];
    //     }

    //     $prestations = $produit->typePrestations()
    //         ->wherePivot('status', 'actif')
    //         ->where('status', 'actif')
    //         ->with('category')
    //         ->orderBy('libelle')
    //         ->get();

    //     return $prestations->map(function ($prestation) {
    //         return [
    //             'uuid_type_prestation' => $prestation->uuid_type_prestation,
    //             'code' => $prestation->code,
    //             'libelle' => $prestation->libelle,
    //             'description' => $prestation->description,
    //             'impact' => $prestation->impact,
    //             'impact_label' => $prestation->getImpactLabel(),
    //             'category' => $prestation->category ? [
    //                 'uuid' => $prestation->category->uuid_category_type_prestations,
    //                 'libelle' => $prestation->category->libelle,
    //             ] : null,
    //         ];
    //     })->toArray();
    // }

   /**
     * Récupérer les motifs disponibles pour un contrat
     * 
     * @param string $codeProduit Code du produit
     * @param string|null $impact Filtrer par impact (1: sortie portefeuille, 0: non sortie, null: tous)
     * @return array
     */
    public function getMotifsForContrat(string $codeProduit, ?string $impact = null): array
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

        return $query->orderBy('libelle')->get()->map(function ($agence) {
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
        
            // throw ValidationException::withMessages([
            //     'agence' => ['']
            // ]);
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

        $periodesCloturees = BordereauRdv::where(function ($query) use ($dateDebut, $dateFin) {
                $query->whereBetween('periode_1', [$dateDebut, $dateFin])
                      ->orWhereBetween('periode_2', [$dateDebut, $dateFin])
                      ->orWhere(function ($q) use ($dateDebut, $dateFin) {
                          $q->where('periode_1', '<=', $dateDebut)
                            ->where('periode_2', '>=', $dateFin);
                      });
            })
            ->get();

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

            $estCloturee = $periodesCloturees->contains(function ($bordereau) use ($date) {
                return $date->between($bordereau->periode_1, $bordereau->periode_2);
            });

            if ($estCloturee) {
                continue;
            }

            $nbRdv = Rdv::where('agence_souhaiter_uuid', $agenceUuid)
                ->whereDate('date_rdv_souhaiter', $dateStr)
                ->whereNotIn('status', ['annule', 'rejete', 'termine'])
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
            ->whereNotIn('status', ['rejete', 'annule', 'termine'])
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

        return [
            'success' => true,
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
        return DB::transaction(function () use ($data, $client, $creatorUuid) {
            $eligibilite = $this->verifierEligibiliteClient(
                $client,
                $data['id_contrat'],
                $data['agence_uuid'],
                $data['date_rdv']
            );

            if (!$eligibilite['eligible']) {
                return [
                    'success' => false,
                    'code' => $eligibilite['code'],
                    'message' => $eligibilite['message'],
                    'eligibilite' => $eligibilite['errors'],
                ];
                // throw ValidationException::withMessages([
                //     'eligibilite' => $eligibilite['errors'],
                // ]);
            }

            $dateDispo = $this->verifierDateDisponible(
                $data['agence_uuid'],
                $data['date_rdv']
            );

            if (!$dateDispo['disponible']) {
                return [
                    'success' => false,
                    'code' => $dateDispo['code'],
                    'message' => $dateDispo['message'],
                ];
                // throw ValidationException::withMessages([
                //     'date_rdv' => [$dateDispo['message']],
                // ]);
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
                // throw ValidationException::withMessages([
                //     'motif_rdv' => ['Ce motif n\'est pas valide.'],
                // ]);
            }

            $contrat = Produit::where('code', $data['code_produit'])->first();
            if ($contrat) {
                $association = $contrat->typePrestations()
                    ->where('uuid_type_prestation', $data['motif_rdv'])
                    ->wherePivot('status', 'actif')
                    ->exists();

                if (!$association) {
                        // throw ValidationException::withMessages([
                        //     'motif_rdv' => ['Ce motif n\'est pas disponible pour ce contrat.'],
                        // ]);
                    return [
                        'success' => false,
                        'code' => 'MOTIF_NON_DISPONIBLE',
                        'message' => 'Ce motif n\'est pas disponible pour ce contrat.', 
                    ];
                }
            }

            $rdv = Rdv::create([
                'uuid_rdvs' => (string) Str::uuid(),
                'code' => RefgenerateCode(Rdv::class, 'RDV-', 'code'), // Générer un code de rendez-vous via une fonction dans le helpers.php
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

            return [
                'success' => true,
                'code' => 'RDV_CREATED',
                'message' => 'Rendez-vous cree avec succes. Code : ' . $rdv->code,
                'data' => $rdv->load(['client', 'motif', 'agenceSouhaitee']),
            ];
        });
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
            ->whereDate('date_rdv_souhaiter', $dateStr)
            ->whereNotIn('status', ['annule', 'rejete', 'termine'])
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
    public function getRdvClient(string $clientUuid, array $filters = [], int $perPage = 20)
    {
        $query = Rdv::forClient($clientUuid)
            ->with(['motif', 'agenceSouhaitee'])
            ->orderBy('created_at', 'desc');
            // ->orderBy('date_rdv_souhaiter', 'desc');

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['id_contrat'])) {
            $query->where('id_contrat', $filters['id_contrat']);
        }

        if (isset($filters['search'])) {
            $query->search($filters['search']);
        }

        return $query->paginate($perPage);
    }

    /**
     * Récupérer les rendez-vous d'une agence
     */
    public function getRdvAgence(string $agenceUuid, array $filters = [], int $perPage = 20)
    {
        $query = Rdv::where('agence_souhaiter_uuid', $agenceUuid)
            ->orWhere('agence_effective_uuid', $agenceUuid)
            ->with(['client', 'motif'])
            ->orderBy('date_rdv_souhaiter', 'asc');

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['date'])) {
            $query->whereDate('date_rdv_souhaiter', $filters['date']);
        }

        return $query->paginate($perPage);
    }

    /**
     * Mettre à jour le statut d'un rendez-vous
     */
    public function updateStatus(Rdv $rdv, string $status, array $data = [], string $updaterUuid): Rdv
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
            // throw ValidationException::withMessages([
            //     'client' => ['Ce rendez-vous ne vous appartient pas.'],
            // ]);
        }

        if ($rdv->status !== 'confirme') {
            return [
                'success' => false,
                'code' => 'RDV_NON_CONFIRME',
                'message' => 'Ce rendez-vous n\'est pas confirmé.',
            ];
            // throw ValidationException::withMessages([
            //     'status' => ['Ce rendez-vous n\'est pas confirmé.'],
            // ]);
        }

        if ($rdv->date_rdv_souhaiter->format('Y-m-d') !== now()->format('Y-m-d')) {
            return [
                'success' => false,
                'code' => 'RDV_NON_PREVU',
                'message' => 'Le rendez-vous n\'est pas prévu aujourd\'hui.',
            ];
            // throw ValidationException::withMessages([
            //     'date_rdv' => ['Le rendez-vous n\'est pas prévu aujourd\'hui.'],
            // ]);
        }

        if (isset($data['latitude']) && isset($data['longitude'])) {
            $agence = $rdv->agenceSouhaitee;
            if ($agence && $agence->latitude && $agence->longitude) {
                $distance = $this->calculerDistance(
                    $data['latitude'],
                    $data['longitude'],
                    $agence->latitude,
                    $agence->longitude
                );

                if ($distance > 0.02) {
                    return [
                        'success' => false,
                        'code' => 'RDV_DISTANCE',
                        'message' => "Vous n'êtes pas à proximité de l'agence. Veuillez vous rapprocher d'au moins 20 mètres pour valider votre présence.",
                    ];
                }
            }
        }

        $rdv->update([
            'is_present' => true,
            'updated_by' => $clientUuid,
        ]);

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
        $earthRadius = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
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
            'confirme' => (clone $query)->where('status', 'confirme')->count(),
            'traite' => (clone $query)->where('status', 'traite')->count(),
            'termine' => (clone $query)->where('status', 'termine')->count(),
            'annule' => (clone $query)->where('status', 'annule')->count(),
            'rejete' => (clone $query)->where('status', 'rejete')->count(),
            'reporte' => (clone $query)->where('status', 'reporte')->count(),
        ];
    }
}