<?php
// app/Services/Api/Ynov/DashboardRdvService.php

namespace App\Services\Api\Ynov\Rdv;

use App\Models\Api\Ynov\Rdv;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DashboardService
{
    /**
     * Récupérer les statistiques globales du tableau de bord
     */
    public function getDashboardStats(array $filters = []): array
    {
        $query = Rdv::query();

        // Filtre par agence
        if (isset($filters['agence_uuid'])) {
            $query->where('agence_effective_uuid', $filters['agence_uuid'])->orWhere('agence_souhaiter_uuid', $filters['agence_uuid']);
        }

        if (isset($filters['date'])) {
            $query->whereDate('created_at', $filters['date']);
        }
    
        // Filtre par date
        if (isset($filters['date_debut'])) {
            $query->whereDate('created_at', '>=', $filters['date_debut']);
        }
        if (isset($filters['date_fin'])) {
            $query->whereDate('created_at', '<=', $filters['date_fin']);
        }

        // Filtre par gestionnaire
        if (isset($filters['gestionnaire_uuid'])) {
            $query->where('gestionnaire_uuid', $filters['gestionnaire_uuid']);
        }

        $total = (clone $query)->count();
        $enAttente = (clone $query)->where('status', 'en_attente')->count();
        $transmis = (clone $query)->where('status', 'transmis')->count();
        $traite = (clone $query)->where('status', 'traite')->count();
        $annule = (clone $query)->where('status', 'annule')->count();
        $rejete = (clone $query)->where('status', 'rejete')->count();
        $reporte = (clone $query)->where('status', 'reporte')->count();
        $expire = (clone $query)->where('status', 'expire')->count();

        return [
            'total' => $total,
            'en_attente' => $enAttente,
            'transmis' => $transmis,
            'traite' => $traite,
            'annule' => $annule,
            'rejete' => $rejete,
            'reporte' => $reporte,
            'expire' => $expire,
            'taux_traitement' => $total > 0 ? round(($traite / $total) * 100, 2) : 0,
        ];
    }

    /**
     * Statistiques par statut (pour les graphiques)
     */
    public function getStatsByStatus(array $filters = []): array
    {
        $query = Rdv::query();

        if (isset($filters['agence_uuid'])) {
            $query->where('agence_effective_uuid', $filters['agence_uuid'])->orWhere('agence_souhaiter_uuid', $filters['agence_uuid']);
        }

        if (isset($filters['date'])) {
            $query->whereDate('created_at', $filters['date']);
        }
        if (isset($filters['date_debut'])) {
            $query->whereDate('created_at', '>=', $filters['date_debut']);
        }
        if (isset($filters['date_fin'])) {
            $query->whereDate('created_at', '<=', $filters['date_fin']);
        }

        if (isset($filters['gestionnaire_uuid'])) {
            $query->where('gestionnaire_uuid', $filters['gestionnaire_uuid']);
        }

        // Récupérer le total pour calculer les pourcentages
        $total = (clone $query)->count();

        // Récupérer les comptes par statut
        $groups = $query->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $labels = [
            'en_attente' => 'En attente',
            'transmis' => 'Transmis',
            'traite' => 'Traité',
            'annule' => 'Annulé',
            'rejete' => 'Rejeté',
            'reporte' => 'Reporté',
            'expire' => 'Expiré',
        ];

        $result = [];
        foreach ($labels as $key => $label) {
            $count = $groups->has($key) ? (int) $groups->get($key)->total : 0;
            $percent = $total > 0 ? round(($count / $total) * 100, 2) : 0;

            $result[$key] = [
                'label' => $label,
                'value' => $count,
                'percent' => $percent,
                'color' => $this->getStatusColor($key),
            ];
        }

        return $result;
    }

    /**
     * Répartition par motif de rendez-vous
     */
    public function getStatsByMotif(array $filters = []): array
    {
        $query = Rdv::query()
            ->join('type_prestations', 'rdvs.motif_rdv', '=', 'type_prestations.uuid_type_prestation')
            ->select(
                'type_prestations.uuid_type_prestation',
                'type_prestations.libelle',
                'type_prestations.code',
                DB::raw('count(*) as total')
            )
            ->groupBy('type_prestations.uuid_type_prestation', 'type_prestations.libelle', 'type_prestations.code');

        if (isset($filters['agence_uuid'])) {
            $query->where('rdvs.agence_effective_uuid', $filters['agence_uuid'])->orWhere('rdvs.agence_souhaiter_uuid', $filters['agence_uuid']);
        }

        if (isset($filters['gestionnaire_uuid'])) {
            $query->where('rdvs.gestionnaire_uuid', $filters['gestionnaire_uuid']);
        }

         if (isset($filters['date'])) {
            $query->whereDate('rdvs.created_at', $filters['date']);
        }

        if (isset($filters['date_debut'])) {
            $query->whereDate('rdvs.created_at', '>=', $filters['date_debut']);
        }
        if (isset($filters['date_fin'])) {
            $query->whereDate('rdvs.created_at', '<=', $filters['date_fin']);
        }

        return $query->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'uuid_type_prestation' => $item->uuid_type_prestation,
                    'libelle' => $item->libelle,
                    'code' => $item->code,
                    'total' => $item->total,
                ];
            })
            ->toArray();
    }

    /**
     * Charge par gestionnaire
     */
    public function getStatsByGestionnaire(array $filters = []): array
    {
        $query = Rdv::query()
            ->join('users', 'rdvs.gestionnaire_uuid', '=', 'users.uuid_user')
            ->join('user_details', 'users.uuid_user', '=', 'user_details.user_uuid')
            ->select(
                'users.uuid_user',
                'users.email',
                DB::raw("CONCAT(user_details.nom, ' ', user_details.prenoms) as nom_complet"),
                DB::raw('count(*) as total'),
                DB::raw("SUM(CASE WHEN rdvs.status IN ('traite') THEN 1 ELSE 0 END) as traites"),
                DB::raw("SUM(CASE WHEN rdvs.status = 'en_attente' THEN 1 ELSE 0 END) as en_attente"),
                // DB::raw("SUM(CASE WHEN rdvs.status = 'confirme' THEN 1 ELSE 0 END) as confirme")
            )
            ->whereNotNull('rdvs.gestionnaire_uuid')
            ->groupBy('users.uuid_user', 'users.email', 'user_details.nom', 'user_details.prenoms');

        if (isset($filters['agence_uuid'])) {
            $query->where('rdvs.agence_effective_uuid', $filters['agence_uuid'])->orWhere('rdvs.agence_souhaiter_uuid', $filters['agence_uuid']);
        }

        if (isset($filters['date'])) {
            $query->whereDate('rdvs.created_at', $filters['date']);
        }

        if (isset($filters['date_debut'])) {
            $query->whereDate('rdvs.created_at', '>=', $filters['date_debut']);
        }
        if (isset($filters['date_fin'])) {
            $query->whereDate('rdvs.created_at', '<=', $filters['date_fin']);
        }

        if (isset($filters['gestionnaire_uuid'])) {
            $query->where('rdvs.gestionnaire_uuid', $filters['gestionnaire_uuid']);
        }

        return $query->orderByDesc('total')
            ->get()
            ->map(function ($item) {
                return [
                    'uuid_user' => $item->uuid_user,
                    'nom_complet' => $item->nom_complet,
                    'email' => $item->email,
                    'total' => $item->total,
                    'traites' => $item->traites,
                    'en_attente' => $item->en_attente,
                    // 'confirme' => $item->confirme,
                    'taux_traitement' => $item->total > 0 ? round(($item->traites / $item->total) * 100, 2) : 0,
                ];
            })
            ->toArray();
    }

    /**
     * File d'attente des rendez-vous non affectés
     */
    public function getFileAttente(array $filters = [], int $limit = 10): array
    {
        $query = Rdv::query()
            // ->whereNotNull('gestionnaire_uuid')
            ->whereIn('status', ['transmis'])
            ->where('is_present', true)
            ->with(['client.details', 'motif', 'agenceEffective', 'agenceSouhaitee'])
            ->orderBy('present_at', 'asc');

        Log::debug('Récupération de la file d\'attente avec les filtres : ' . json_encode($filters) . ' et limite : ' . $limit);
        Log::debug('Requête SQL générée : ' . $query->toSql());

        if (isset($filters['agence_uuid'])) {
            $query->where('agence_effective_uuid', $filters['agence_uuid']);
        }

        if (isset($filters['gestionnaire_uuid'])) {
            $query->where('gestionnaire_uuid', $filters['gestionnaire_uuid']);
        }

        if (isset($filters['date_debut'])) {
            $query->whereDate('date_rdv_effective', '>=', $filters['date_debut']);
        }
        if (isset($filters['date_fin'])) {
            $query->whereDate('date_rdv_effective', '<=', $filters['date_fin']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->limit($limit)
            ->get()
            ->map(function ($rdv) {
                return [
                    'uuid_rdvs' => $rdv->uuid_rdvs,
                    'code' => $rdv->code,
                    'client' => [
                        'uuid_user' => $rdv->client?->uuid_user,
                        'nom_complet' => $rdv->client?->details ?
                            $rdv->client->details->nom . ' ' . $rdv->client->details->prenoms :
                            $rdv->client?->email,
                        'email' => $rdv->client?->email,
                    ],
                    'motif' => $rdv->motif ? [
                        'uuid_type_prestation' => $rdv->motif->uuid_type_prestation,
                        'libelle' => $rdv->motif->libelle,
                        'code' => $rdv->motif->code,
                    ] : null,
                    'agence' => $rdv->agenceSouhaitee ? [
                        'uuid_agence' => $rdv->agenceSouhaitee->uuid_agence,
                        'libelle' => $rdv->agenceSouhaitee->libelle,
                        'ville' => $rdv->agenceSouhaitee->ville,
                    ] : null,
                    'date_rdv_effective' => $rdv->date_rdv_effective?->format('d/m/Y'),
                    'date_effective_original' => $rdv->date_rdv_effective?->format('Y-m-d'),
                    'status' => $rdv->status,
                    'status_label' => Rdv::STATUS[$rdv->status] ?? $rdv->status,
                    'created_at' => $rdv->created_at?->format('d/m/Y H:i'),
                    'date_creation' => $rdv->created_at?->format('Y-m-d H:i:s'),
                    // Indiquer si le rendez-vous est urgent (moins de 3 jours avant la date souhaitée)
                    'est_urgent' => $rdv->date_rdv_effective && $rdv->date_rdv_effective->diffInDays(now()) <= 3,
                ];
            })
            ->toArray();
    }

    /**
     * Statistiques par ville/agence
     */
    public function getStatsByAgence(array $filters = []): array
    {
        $query = Rdv::query()
            ->join('agences', 'rdvs.agence_souhaiter_uuid', '=', 'agences.uuid_agence')
            ->select(
                'agences.uuid_agence',
                'agences.libelle',
                'agences.code',
                'agences.ville',
                DB::raw('count(*) as total'),
                DB::raw("SUM(CASE WHEN rdvs.status IN ('traite') THEN 1 ELSE 0 END) as traites"),
                DB::raw("SUM(CASE WHEN rdvs.status = 'en_attente' THEN 1 ELSE 0 END) as en_attente"),
                DB::raw("SUM(CASE WHEN rdvs.status IN ('transmis') THEN 1 ELSE 0 END) as transmis"),
                DB::raw("SUM(CASE WHEN rdvs.status IN ('en_attente') THEN 1 ELSE 0 END) as en_attente"),
                DB::raw("SUM(CASE WHEN rdvs.status IN ('annule') THEN 1 ELSE 0 END) as annule"),
                DB::raw("SUM(CASE WHEN rdvs.status IN ('rejete') THEN 1 ELSE 0 END) as rejete"),
                DB::raw("SUM(CASE WHEN rdvs.status IN ('reporte') THEN 1 ELSE 0 END) as reporte"),
                DB::raw("SUM(CASE WHEN rdvs.status IN ('expire') THEN 1 ELSE 0 END) as expire")
            )
            ->groupBy('agences.uuid_agence', 'agences.libelle', 'agences.code', 'agences.ville');

        if (isset($filters['date'])) {
            $query->whereDate('rdvs.created_at', $filters['date']);
        }

        if (isset($filters['date_debut'])) {
            $query->whereDate('rdvs.created_at', '>=', $filters['date_debut']);
        }
        if (isset($filters['date_fin'])) {
            $query->whereDate('rdvs.created_at', '<=', $filters['date_fin']);
        }

        return $query->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'uuid_agence' => $item->uuid_agence,
                    'libelle' => $item->libelle,
                    'code' => $item->code,
                    'ville' => $item->ville,
                    'total' => $item->total,
                    'traites' => $item->traites,
                    'en_attente' => $item->en_attente,
                    'taux_traitement' => $item->total > 0 ? round(($item->traites / $item->total) * 100, 2) : 0,
                ];
            })
            ->toArray();
    }

    /**
     * Évolution des rendez-vous par période
     */
    public function getEvolution(array $filters = [], string $period = 'daily'): array
    {
        $query = Rdv::query();

        if (isset($filters['agence_uuid'])) {
            $query->where('agence_souhaiter_uuid', $filters['agence_uuid'])->orWhere('agence_effective_uuid', $filters['agence_uuid']);
        }

        $dateDebut = $filters['date_debut'] ?? now()->subDays(30);
        $dateFin = $filters['date_fin'] ?? now();

        if (isset($filters['date'])) {
            $query->whereDate('created_at', $filters['date']);
        }

        $query->whereDate('created_at', '>=', $dateDebut)
            ->whereDate('created_at', '<=', $dateFin);

        if (isset($filters['gestionnaire_uuid'])) {
            $query->where('gestionnaire_uuid', $filters['gestionnaire_uuid']);
        }

        if ($period === 'daily') {
            return $query->select(
                DB::raw("DATE(created_at) as date"),
                DB::raw('count(*) as total'),
                DB::raw("SUM(CASE WHEN status IN ('traite') THEN 1 ELSE 0 END) as traites"),
                DB::raw("SUM(CASE WHEN status IN ('transmis') THEN 1 ELSE 0 END) as transmis"),
                DB::raw("SUM(CASE WHEN status IN ('en_attente') THEN 1 ELSE 0 END) as en_attente"),
                DB::raw("SUM(CASE WHEN status IN ('annule') THEN 1 ELSE 0 END) as annule"),
                DB::raw("SUM(CASE WHEN status IN ('rejete') THEN 1 ELSE 0 END) as rejete"),
                DB::raw("SUM(CASE WHEN status IN ('reporte') THEN 1 ELSE 0 END) as reporte"),
                DB::raw("SUM(CASE WHEN status IN ('expire') THEN 1 ELSE 0 END) as expire")

            )
                ->groupBy(DB::raw("DATE(created_at)"))
                ->orderBy('date')
                ->get()
                ->map(function ($item) {
                    return [
                        'date' => $item->date,
                        'date_formatee' => Carbon::parse($item->date)->format('d/m/Y'),
                        'total' => $item->total,
                        'traites' => $item->traites,
                        'transmis' => $item->transmis,
                        'en_attente' => $item->en_attente,
                        'annule' => $item->annule,
                        'rejete' => $item->rejete,
                        'reporte' => $item->reporte,
                        'expire' => $item->expire
                    ];
                })
                ->toArray();
        }

        if ($period === 'monthly') {
            return $query->select(
                DB::raw("DATE_FORMAT(created_at, '%Y-%m') as mois"),
                DB::raw('count(*) as total'),
                DB::raw("SUM(CASE WHEN status IN ('traite') THEN 1 ELSE 0 END) as traites"),
                DB::raw("SUM(CASE WHEN status IN ('transmis') THEN 1 ELSE 0 END) as transmis"),
                DB::raw("SUM(CASE WHEN status IN ('en_attente') THEN 1 ELSE 0 END) as en_attente"),
                DB::raw("SUM(CASE WHEN status IN ('annule') THEN 1 ELSE 0 END) as annule"),
                DB::raw("SUM(CASE WHEN status IN ('rejete') THEN 1 ELSE 0 END) as rejete"),
                DB::raw("SUM(CASE WHEN status IN ('reporte') THEN 1 ELSE 0 END) as reporte"),
                DB::raw("SUM(CASE WHEN status IN ('expire') THEN 1 ELSE 0 END) as expire")
            )
                ->groupBy(DB::raw("DATE_FORMAT(created_at, '%Y-%m')"))
                ->orderBy('mois')
                ->get()
                ->map(function ($item) {
                    $carbon = Carbon::createFromFormat('Y-m', $item->mois);
                    return [
                        'mois' => $item->mois,
                        'mois_formate' => $carbon->locale('fr')->translatedFormat('F Y'),
                        'total' => $item->total,
                        'traites' => $item->traites,
                        'transmis' => $item->transmis,
                        'en_attente' => $item->en_attente,
                        'annule' => $item->annule,
                        'rejete' => $item->rejete,
                        'reporte' => $item->reporte,
                        'expire' => $item->expire
                    ];
                })
                ->toArray();
        }

        return [];
    }

    /**
     * Obtenir la couleur pour un statut
     */
    private function getStatusColor(string $status): string
    {
        $colors = [
            'en_attente' => '#FFA726', // Orange
            'transmis' => '#7E57C2',   // Violet
            'traite' => '#66BB6A',     // Vert
            'annule' => '#EF5350',     // Rouge
            'rejete' => '#EF5350',     // Rouge
            'reporte' => '#FFA726',    // Orange
            'expire' => '#78909C',     // Gris
        ];
        return $colors[$status] ?? '#9E9E9E';
    }

    
}
