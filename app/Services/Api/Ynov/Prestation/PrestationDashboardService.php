<?php

namespace App\Services\Api\Ynov\Prestation;

use App\Models\Api\Ynov\Prestation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PrestationDashboardService
{
    /**
     * Récupérer les statistiques globales du tableau de bord
     */
    public function getDashboardStats(array $filters = []): array
    {
        $query = Prestation::query();

        // Filtre par gestionnaire
        if (isset($filters['gestionnaire_uuid'])) {
            $query->where('gestionnaire_uuid', $filters['gestionnaire_uuid']);
        }

        // Filtre par client
        if (isset($filters['client_uuid'])) {
            $query->where('client_uuid', $filters['client_uuid']);
        }

        // Filtre par type de prestation
        if (isset($filters['type_prestation_uuid'])) {
            $query->where('type_prestation_uuid', $filters['type_prestation_uuid']);
        }

        // Filtre par partenaire
        if (isset($filters['partner_uuid'])) {
            $query->where('partner_uuid', $filters['partner_uuid']);
        }

        // Filtre par date
        if (isset($filters['date'])) {
            $query->whereDate('created_at', $filters['date']);
        }

        if (isset($filters['date_debut'])) {
            $query->whereDate('created_at', '>=', $filters['date_debut']);
        }
        if (isset($filters['date_fin'])) {
            $query->whereDate('created_at', '<=', $filters['date_fin']);
        }

        $total = (clone $query)->count();
        $inacheve = (clone $query)->where('status', 'inacheve')->count();
        $enAttente = (clone $query)->where('status', 'en_attente')->count();
        $transmis = (clone $query)->where('status', 'transmis')->count();
        $accepte = (clone $query)->where('status', 'accepte')->count();
        $rejete = (clone $query)->where('status', 'rejete')->count();
        $annule = (clone $query)->where('status', 'annule')->count();

        return [
            'total' => $total,
            'inacheve' => $inacheve,
            'en_attente' => $enAttente,
            'transmis' => $transmis,
            'accepte' => $accepte,
            'rejete' => $rejete,
            'annule' => $annule,
            'taux_traitement' => $total > 0 ? round(($accepte / $total) * 100, 2) : 0,
            'taux_rejet' => $total > 0 ? round(($rejete / $total) * 100, 2) : 0,
        ];
    }

    /**
     * Statistiques par statut (pour les graphiques)
     */
    public function getStatsByStatus(array $filters = []): array
    {
        $query = Prestation::query();

        if (isset($filters['gestionnaire_uuid'])) {
            $query->where('gestionnaire_uuid', $filters['gestionnaire_uuid']);
        }

        if (isset($filters['client_uuid'])) {
            $query->where('client_uuid', $filters['client_uuid']);
        }

        if (isset($filters['type_prestation_uuid'])) {
            $query->where('type_prestation_uuid', $filters['type_prestation_uuid']);
        }

        if (isset($filters['partner_uuid'])) {
            $query->where('partner_uuid', $filters['partner_uuid']);
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

        // Récupérer le total pour calculer les pourcentages
        $total = (clone $query)->count();

        // Récupérer les comptes par statut
        $groups = $query->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $labels = [
            'inacheve' => 'Inachevée',
            'en_attente' => 'En attente',
            'transmis' => 'Transmise',
            'accepte' => 'Acceptée',
            'rejete' => 'Rejetée',
            'annule' => 'Annulée',
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
     * Répartition par type de prestation
     */
    public function getStatsByType(array $filters = []): array
    {
        $query = Prestation::query()
            ->join('type_prestations', 'prestations.type_prestation_uuid', '=', 'type_prestations.uuid_type_prestation')
            ->select(
                'type_prestations.uuid_type_prestation',
                'type_prestations.libelle',
                'type_prestations.code',
                DB::raw('count(*) as total')
            )
            ->groupBy('type_prestations.uuid_type_prestation', 'type_prestations.libelle', 'type_prestations.code');

        if (isset($filters['gestionnaire_uuid'])) {
            $query->where('prestations.gestionnaire_uuid', $filters['gestionnaire_uuid']);
        }

        if (isset($filters['client_uuid'])) {
            $query->where('prestations.client_uuid', $filters['client_uuid']);
        }

        if (isset($filters['partner_uuid'])) {
            $query->where('prestations.partner_uuid', $filters['partner_uuid']);
        }

        if (isset($filters['date'])) {
            $query->whereDate('prestations.created_at', $filters['date']);
        }

        if (isset($filters['date_debut'])) {
            $query->whereDate('prestations.created_at', '>=', $filters['date_debut']);
        }
        if (isset($filters['date_fin'])) {
            $query->whereDate('prestations.created_at', '<=', $filters['date_fin']);
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
        $query = Prestation::query()
            ->join('users', 'prestations.gestionnaire_uuid', '=', 'users.uuid_user')
            ->join('user_details', 'users.uuid_user', '=', 'user_details.user_uuid')
            ->select(
                'users.uuid_user',
                'users.email',
                DB::raw("CONCAT(user_details.nom, ' ', user_details.prenoms) as nom_complet"),
                DB::raw('count(*) as total'),
                DB::raw("SUM(CASE WHEN prestations.status IN ('accepte') THEN 1 ELSE 0 END) as acceptes"),
                DB::raw("SUM(CASE WHEN prestations.status = 'en_attente' THEN 1 ELSE 0 END) as en_attente"),
                DB::raw("SUM(CASE WHEN prestations.status = 'transmis' THEN 1 ELSE 0 END) as transmis"),
                DB::raw("SUM(CASE WHEN prestations.status = 'rejete' THEN 1 ELSE 0 END) as rejetes"),
            )
            ->whereNotNull('prestations.gestionnaire_uuid')
            ->groupBy('users.uuid_user', 'users.email', 'user_details.nom', 'user_details.prenoms');

        if (isset($filters['client_uuid'])) {
            $query->where('prestations.client_uuid', $filters['client_uuid']);
        }

        if (isset($filters['type_prestation_uuid'])) {
            $query->where('prestations.type_prestation_uuid', $filters['type_prestation_uuid']);
        }

        if (isset($filters['partner_uuid'])) {
            $query->where('prestations.partner_uuid', $filters['partner_uuid']);
        }

        if (isset($filters['date'])) {
            $query->whereDate('prestations.created_at', $filters['date']);
        }

        if (isset($filters['date_debut'])) {
            $query->whereDate('prestations.created_at', '>=', $filters['date_debut']);
        }
        if (isset($filters['date_fin'])) {
            $query->whereDate('prestations.created_at', '<=', $filters['date_fin']);
        }

        if (isset($filters['gestionnaire_uuid'])) {
            $query->where('prestations.gestionnaire_uuid', $filters['gestionnaire_uuid']);
        }

        return $query->orderByDesc('total')
            ->get()
            ->map(function ($item) {
                return [
                    'uuid_user' => $item->uuid_user,
                    'nom_complet' => $item->nom_complet,
                    'email' => $item->email,
                    'total' => $item->total,
                    'acceptes' => $item->acceptes,
                    'en_attente' => $item->en_attente,
                    'transmis' => $item->transmis,
                    'rejetes' => $item->rejetes,
                    'taux_traitement' => $item->total > 0 ? round(($item->acceptes / $item->total) * 100, 2) : 0,
                ];
            })
            ->toArray();
    }

    /**
     * File d'attente des prestations non assignées
     */
    public function getFileAttente(array $filters = [], int $limit = 10): array
    {
        $query = Prestation::query()
            ->whereNull('gestionnaire_uuid')
            ->whereIn('status', ['en_attente', 'transmis'])
            ->with(['client.details', 'typePrestation.category'])
            ->orderBy('created_at', 'asc');

        if (isset($filters['client_uuid'])) {
            $query->where('client_uuid', $filters['client_uuid']);
        }

        if (isset($filters['type_prestation_uuid'])) {
            $query->where('type_prestation_uuid', $filters['type_prestation_uuid']);
        }

        if (isset($filters['partner_uuid'])) {
            $query->where('partner_uuid', $filters['partner_uuid']);
        }

        if (isset($filters['date_debut'])) {
            $query->whereDate('created_at', '>=', $filters['date_debut']);
        }
        if (isset($filters['date_fin'])) {
            $query->whereDate('created_at', '<=', $filters['date_fin']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->limit($limit)
            ->get()
            ->map(function ($prestation) {
                return [
                    'uuid_prestation' => $prestation->uuid_prestation,
                    'code' => $prestation->code,
                    'client' => [
                        'uuid_user' => $prestation->client?->uuid_user,
                        'nom_complet' => $prestation->client?->details ?
                            $prestation->client->details->nom . ' ' . $prestation->client->details->prenoms :
                            $prestation->client?->email,
                        'email' => $prestation->client?->email,
                    ],
                    'type_prestation' => $prestation->typePrestation ? [
                        'uuid_type_prestation' => $prestation->typePrestation->uuid_type_prestation,
                        'libelle' => $prestation->typePrestation->libelle,
                        'code' => $prestation->typePrestation->code,
                    ] : null,
                    'montant' => $prestation->montant,
                    'status' => $prestation->status,
                    'status_label' => $prestation->getPrestationStatusLabel(),
                    'created_at' => $prestation->created_at?->format('d/m/Y H:i'),
                    'date_creation' => $prestation->created_at?->format('Y-m-d H:i:s'),
                    'est_urgent' => $prestation->created_at && $prestation->created_at->diffInDays(now()) >= 7,
                ];
            })
            ->toArray();
    }

    /**
     * Statistiques par partenaire
     */
    public function getStatsByPartner(array $filters = []): array
    {
        $query = Prestation::query()
            ->join('partners', 'prestations.partner_uuid', '=', 'partners.uuid_partner')
            ->select(
                'partners.uuid_partner',
                'partners.designation',
                'partners.code',
                DB::raw('count(*) as total'),
                DB::raw("SUM(CASE WHEN prestations.status IN ('accepte') THEN 1 ELSE 0 END) as acceptes"),
                DB::raw("SUM(CASE WHEN prestations.status = 'en_attente' THEN 1 ELSE 0 END) as en_attente"),
                DB::raw("SUM(CASE WHEN prestations.status IN ('transmis') THEN 1 ELSE 0 END) as transmis"),
                DB::raw("SUM(CASE WHEN prestations.status IN ('annule') THEN 1 ELSE 0 END) as annule"),
                DB::raw("SUM(CASE WHEN prestations.status IN ('rejete') THEN 1 ELSE 0 END) as rejete"),
            )
            ->whereNotNull('prestations.partner_uuid')
            ->groupBy('partners.uuid_partner', 'partners.designation', 'partners.code');

        if (isset($filters['gestionnaire_uuid'])) {
            $query->where('prestations.gestionnaire_uuid', $filters['gestionnaire_uuid']);
        }

        if (isset($filters['client_uuid'])) {
            $query->where('prestations.client_uuid', $filters['client_uuid']);
        }

        if (isset($filters['date'])) {
            $query->whereDate('prestations.created_at', $filters['date']);
        }

        if (isset($filters['date_debut'])) {
            $query->whereDate('prestations.created_at', '>=', $filters['date_debut']);
        }
        if (isset($filters['date_fin'])) {
            $query->whereDate('prestations.created_at', '<=', $filters['date_fin']);
        }

        return $query->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'uuid_partner' => $item->uuid_partner,
                    'designation' => $item->designation,
                    'code' => $item->code,
                    'total' => $item->total,
                    'acceptes' => $item->acceptes,
                    'en_attente' => $item->en_attente,
                    'transmis' => $item->transmis,
                    'taux_traitement' => $item->total > 0 ? round(($item->acceptes / $item->total) * 100, 2) : 0,
                ];
            })
            ->toArray();
    }

    /**
     * Évolution des prestations par période
     */
    public function getEvolution(array $filters = [], string $period = 'daily'): array
    {
        $query = Prestation::query();

        if (isset($filters['gestionnaire_uuid'])) {
            $query->where('gestionnaire_uuid', $filters['gestionnaire_uuid']);
        }

        if (isset($filters['client_uuid'])) {
            $query->where('client_uuid', $filters['client_uuid']);
        }

        if (isset($filters['type_prestation_uuid'])) {
            $query->where('type_prestation_uuid', $filters['type_prestation_uuid']);
        }

        if (isset($filters['partner_uuid'])) {
            $query->where('partner_uuid', $filters['partner_uuid']);
        }

        $dateDebut = $filters['date_debut'] ?? now()->subDays(30);
        $dateFin = $filters['date_fin'] ?? now();

        if (isset($filters['date'])) {
            $query->whereDate('created_at', $filters['date']);
        }

        $query->whereDate('created_at', '>=', $dateDebut)
            ->whereDate('created_at', '<=', $dateFin);

        if ($period === 'daily') {
            return $query->select(
                DB::raw("DATE(created_at) as date"),
                DB::raw('count(*) as total'),
                DB::raw("SUM(CASE WHEN status IN ('accepte') THEN 1 ELSE 0 END) as acceptes"),
                DB::raw("SUM(CASE WHEN status IN ('transmis') THEN 1 ELSE 0 END) as transmis"),
                DB::raw("SUM(CASE WHEN status IN ('en_attente') THEN 1 ELSE 0 END) as en_attente"),
                DB::raw("SUM(CASE WHEN status IN ('annule') THEN 1 ELSE 0 END) as annule"),
                DB::raw("SUM(CASE WHEN status IN ('rejete') THEN 1 ELSE 0 END) as rejete"),
                DB::raw("SUM(CASE WHEN status IN ('inacheve') THEN 1 ELSE 0 END) as inacheve")
            )
                ->groupBy(DB::raw("DATE(created_at)"))
                ->orderBy('date')
                ->get()
                ->map(function ($item) {
                    return [
                        'date' => $item->date,
                        'date_formatee' => Carbon::parse($item->date)->format('d/m/Y'),
                        'total' => $item->total,
                        'acceptes' => $item->acceptes,
                        'transmis' => $item->transmis,
                        'en_attente' => $item->en_attente,
                        'annule' => $item->annule,
                        'rejete' => $item->rejete,
                        'inacheve' => $item->inacheve
                    ];
                })
                ->toArray();
        }

        if ($period === 'monthly') {
            return $query->select(
                DB::raw("DATE_FORMAT(created_at, '%Y-%m') as mois"),
                DB::raw('count(*) as total'),
                DB::raw("SUM(CASE WHEN status IN ('accepte') THEN 1 ELSE 0 END) as acceptes"),
                DB::raw("SUM(CASE WHEN status IN ('transmis') THEN 1 ELSE 0 END) as transmis"),
                DB::raw("SUM(CASE WHEN status IN ('en_attente') THEN 1 ELSE 0 END) as en_attente"),
                DB::raw("SUM(CASE WHEN status IN ('annule') THEN 1 ELSE 0 END) as annule"),
                DB::raw("SUM(CASE WHEN status IN ('rejete') THEN 1 ELSE 0 END) as rejete"),
                DB::raw("SUM(CASE WHEN status IN ('inacheve') THEN 1 ELSE 0 END) as inacheve")
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
                        'acceptes' => $item->acceptes,
                        'transmis' => $item->transmis,
                        'en_attente' => $item->en_attente,
                        'annule' => $item->annule,
                        'rejete' => $item->rejete,
                        'inacheve' => $item->inacheve
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
            'inacheve' => '#78909C',     // Gris
            'en_attente' => '#FFA726',  // Orange
            'transmis' => '#0b56e0',    // Violet
            'accepte' => '#66BB6A',    // Vert
            'rejete' => '#EF5350',     // Rouge
            'annule' => '#EF5350',     // Rouge
        ];
        return $colors[$status] ?? '#9E9E9E';
    }
}
