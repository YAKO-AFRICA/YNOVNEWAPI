<?php

namespace App\Services\Api\Ynov\Rdv;

use App\Models\Api\Ynov\Rdv;
use Carbon\Carbon;

class CalendrierService
{
    /**
     * Obtenir le calendrier des rendez-vous pour un mois donné
     */
    public function getCalendrierMois(int $mois, int $annee, ?string $agenceUuid = null, ?string $gestionnaireUuid = null): array
    {
        $dateDebut = Carbon::create($annee, $mois, 1)->startOfDay();
        $dateFin = Carbon::create($annee, $mois, 1)->endOfMonth()->endOfDay();

        // Récupérer les RDV du mois
        $query = Rdv::whereBetween('date_rdv_effective', [$dateDebut, $dateFin])
            ->whereIn('status', ['transmis', 'reporte']);

        if ($agenceUuid) {
            $query->where('agence_effective_uuid', $agenceUuid);
        }

        if ($gestionnaireUuid) {
            $query->where('gestionnaire_uuid', $gestionnaireUuid);
        }

        $rdvs = $query->get();
        if ($rdvs->isEmpty()) {
            return [
                'success' => false,
                'message' => 'Aucun rendez-vous trouvé',
                'code' => 'RDV_NOT_FOUND',
                'data' => [],
            ];
        }

        // Grouper les RDV par jour
        $rdvsParJour = [];
        foreach ($rdvs as $rdv) {
            $dateStr = $rdv->date_rdv_effective->format('Y-m-d');
            if (!isset($rdvsParJour[$dateStr])) {
                $rdvsParJour[$dateStr] = [
                    'total' => 0,
                    'en_attente' => 0,
                    'transmis' => 0,
                    'traite' => 0,
                    'reporte' => 0,
                    'expire' => 0,
                ];
            }
            $rdvsParJour[$dateStr]['total']++;
            $rdvsParJour[$dateStr][$rdv->status]++;
        }

        // Générer le calendrier
        $calendrier = [];
        $premierJour = Carbon::create($annee, $mois, 1);
        $dernierJour = Carbon::create($annee, $mois, 1)->endOfMonth();

        // Ajouter les jours avant le 1er du mois (vides)
        $jourSemaineDebut = $premierJour->dayOfWeekIso; // 1 = lundi, 7 = dimanche
        for ($i = 1; $i < $jourSemaineDebut; $i++) {
            $calendrier[] = [
                'jour' => null,
                'est_du_mois' => false,
            ];
        }

        // Ajouter les jours du mois
        for ($jour = 1; $jour <= $dernierJour->day; $jour++) {
            $date = Carbon::create($annee, $mois, $jour);
            $dateStr = $date->format('Y-m-d');
            $rdvsJour = $rdvsParJour[$dateStr] ?? [
                'total' => 0,
                'en_attente' => 0,
                'transmis' => 0,
                'traite' => 0,
                'reporte' => 0,
                'expire' => 0,
            ];

            $calendrier[] = [
                'jour' => $jour,
                'date' => $dateStr,
                'date_formatee' => $date->locale('fr')->translatedFormat('d F Y'),
                'jour_semaine' => $date->locale('fr')->dayName,
                'est_du_mois' => true,
                'est_aujourdhui' => $date->isToday(),
                'est_passe' => $date->isPast(),
                'rdvs' => $rdvsJour['total'],
                'details' => $rdvsJour,
                'statut' => $this->getStatutJour($rdvsJour),
            ];
        }

        // Mois précédent et suivant
        $moisPrecedent = Carbon::create($annee, $mois, 1)->subMonth();
        $moisSuivant = Carbon::create($annee, $mois, 1)->addMonth();

        return [
            'success' => true,
            'code' => 'SUCCESS',
            'message' => 'Calendrier chargé.',
            'mois' => $mois,
            'annee' => $annee,
            'mois_formate' => $premierJour->locale('fr')->translatedFormat('F Y'),
            'jours' => $calendrier,
            'navigation' => [
                'precedent' => [
                    'mois' => $moisPrecedent->month,
                    'annee' => $moisPrecedent->year,
                    'label' => $moisPrecedent->locale('fr')->translatedFormat('F Y'),
                ],
                'suivant' => [
                    'mois' => $moisSuivant->month,
                    'annee' => $moisSuivant->year,
                    'label' => $moisSuivant->locale('fr')->translatedFormat('F Y'),
                ],
                'aujourdhui' => [
                    'mois' => now()->month,
                    'annee' => now()->year,
                    'label' => now()->locale('fr')->translatedFormat('F Y'),
                ],
            ],
            'filtres' => [
                'agence_uuid' => $agenceUuid,
                'gestionnaire_uuid' => $gestionnaireUuid,
            ],
        ];
    }

    // /**
    //  * Obtenir les détails d'un jour spécifique
    //  */
    // public function getDetailsJour(string $date, ?string $agenceUuid = null, ?string $gestionnaireUuid = null): array
    // {
    //     $dateCarbon = Carbon::parse($date);

    //     $query = Rdv::whereDate('date_rdv_effective', $date)
    //         ->whereIn('status', ['transmis', 'reporte'])
    //         ->with(['client.details', 'motif', 'agenceEffective', 'gestionnaire'])
    //         ->orderBy('date_rdv_effective', 'asc');

    //     if ($agenceUuid) {
    //         $query->where('agence_effective_uuid', $agenceUuid);
    //     }

    //     if ($gestionnaireUuid) {
    //         $query->where('gestionnaire_uuid', $gestionnaireUuid);
    //     }

    //     $rdvs = $query->get();

    //     return [
    //         'date' => $date,
    //         'date_formatee' => $dateCarbon->locale('fr')->translatedFormat('l d F Y'),
    //         'est_aujourdhui' => $dateCarbon->isToday(),
    //         'total_rdvs' => $rdvs->count(),
    //         'rdvs' => $rdvs->map(function ($rdv) {
    //             return [
    //                 'uuid_rdvs' => $rdv->uuid_rdvs,
    //                 'code' => $rdv->code,
    //                 'client' => [
    //                     'uuid_user' => $rdv->client?->uuid_user,
    //                     'nom_complet' => $rdv->client?->details ? 
    //                         $rdv->client->details->nom . ' ' . $rdv->client->details->prenoms : 
    //                         $rdv->client?->email,
    //                     'email' => $rdv->client?->email,
    //                 ],
    //                 'motif' => $rdv->motif ? [
    //                     'uuid' => $rdv->motif->uuid_type_prestation,
    //                     'libelle' => $rdv->motif->libelle,
    //                     'code' => $rdv->motif->code,
    //                 ] : null,
    //                 'agence' => $rdv->agenceEffective ? [
    //                     'uuid' => $rdv->agenceEffective->uuid_agence,
    //                     'libelle' => $rdv->agenceEffective->libelle,
    //                     'ville' => $rdv->agenceEffective->ville,
    //                 ] : null,
    //                 'gestionnaire' => $rdv->gestionnaire ? [
    //                     'uuid' => $rdv->gestionnaire->uuid_user,
    //                     'nom_complet' => $rdv->gestionnaire->details ? 
    //                         $rdv->gestionnaire->details->nom . ' ' . $rdv->gestionnaire->details->prenoms : 
    //                         $rdv->gestionnaire->email,
    //                 ] : null,
    //                 'status' => $rdv->status,
    //                 'status_label' => Rdv::STATUS[$rdv->status] ?? $rdv->status,
    //                 'is_present' => $rdv->is_present,
    //             ];
    //         })->toArray(),
    //     ];
    // }

    /**
     * Obtenir les statistiques du calendrier
     */
    public function getStatsCalendrier(int $mois, int $annee, ?string $agenceUuid = null): array
    {
        $dateDebut = Carbon::create($annee, $mois, 1)->startOfDay();
        $dateFin = Carbon::create($annee, $mois, 1)->endOfMonth()->endOfDay();

        $query = Rdv::whereBetween('date_rdv_effective', [$dateDebut, $dateFin])
            ->whereIn('status', ['transmis', 'reporte']);

        if ($agenceUuid) {
            $query->where('agence_effective_uuid', $agenceUuid);
        }

        $total = (clone $query)->count();
        $enAttente = (clone $query)->where('status', 'en_attente')->count();
        $transmis = (clone $query)->where('status', 'transmis')->count();
        $traite = (clone $query)->where('status', 'traite')->count();
        $reporte = (clone $query)->where('status', 'reporte')->count();
        $expire = (clone $query)->where('status', 'expire')->count();

        // RDV par jour du mois
        $rdvsParJour = $query->selectRaw('DATE(date_rdv_effective) as date, COUNT(*) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => $item->date,
                    'date_formatee' => Carbon::parse($item->date)->locale('fr')->translatedFormat('d/m'),
                    'total' => $item->total,
                ];
            })
            ->toArray();

        return [
            'periode' => [
                'mois' => $mois,
                'annee' => $annee,
                'label' => Carbon::create($annee, $mois, 1)->locale('fr')->translatedFormat('F Y'),
            ],
            'total' => $total,
            'par_statut' => [
                'en_attente' => $enAttente,
                'transmis' => $transmis,
                'traite' => $traite,
                'reporte' => $reporte,
                'expire' => $expire,
            ],
            'par_jour' => $rdvsParJour,
            'taux_traitement' => $total > 0 ? round(($traite / $total) * 100, 2) : 0,
        ];
    }

    /**
     * Déterminer le statut d'un jour (plein, partiel, vide)
     */
    private function getStatutJour(array $rdvsJour): string
    {
        if ($rdvsJour['total'] === 0) {
            return 'vide';
        }

        // On pourrait ajouter une logique pour vérifier la capacité ici
        // Pour l'instant, on considère qu'il y a des RDV
        return 'occupe';
    }
}
