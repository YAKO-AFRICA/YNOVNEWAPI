<?php

namespace App\Services\Api\Ynov\ESouscription;

use \InvalidArgumentException;
use App\Models\Api\Ynov\Esouscription\Contrat;
use App\Models\Api\Ynov\parameter\ActivityLog;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ContratService
{
    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Contrat::query();

        foreach (['uuid_contrat', 'agence_uuid', 'code_produit', 'libelle_produit', 'code_proposition', 'numero_police', 'partner_uuid', 'conseiller_uuid', 'mode_paiement', 'etape'] as $field) {
            if (!empty($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('uuid_contrat', 'like', '%' . $search . '%')
                    ->orWhere('numero_police', 'like', '%' . $search . '%')
                    ->orWhere('code_proposition', 'like', '%' . $search . '%')
                    ->orWhere('libelle_produit', 'like', '%' . $search . '%');
            });
        }

        if (($filters['etat'] ?? null) === 'actif') {
            $query->whereNull('deleted_at');
        } elseif (($filters['etat'] ?? null) === 'supprime') {
            $query->onlyTrashed();
        }

        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    public function getDashboard(array $filters = []): array
    {
        $query = $this->dashboardQuery($filters, true);
        $allDatesQuery = $this->dashboardQuery($filters, false);
        $allStagesQuery = $this->dashboardQuery($filters, false, false);

        $rankings = [
            'produits' => [
                'id' => 'contrats.code_produit',
                'label' => 'contrats.libelle_produit',
            ],
            'agences' => [
                'id' => 'dashboard_agences.uuid_agence',
                'label' => 'dashboard_agences.libelle',
            ],
            'reseaux' => [
                'id' => 'dashboard_reseaux.uuid_reseau',
                'label' => 'dashboard_reseaux.libelle',
            ],
            'partenaires' => [
                'id' => 'dashboard_partners.uuid_partner',
                'label' => 'dashboard_partners.designation',
            ],
        ];

        $topByPremium = [];
        $leaders = [];

        foreach ($rankings as $type => $columns) {
            $rows = $this->getDashboardRanking(
                clone $query,
                $columns['id'],
                $columns['label']
            );

            $topByPremium[$type] = $rows->take(5)->values()->all();
            $leaders[$type . '_par_primes'] = $topByPremium[$type][0] ?? null;
            $leaders[$type . '_par_contrats'] = $this->getDashboardRanking(
                clone $query,
                $columns['id'],
                $columns['label'],
                'contrats'
            )->first();
        }

        $evolution = $this->getSubscriptionEvolution($filters);

        $volume = [
            'contrats_aujourdhui' => $this->countContractsBetween(
                clone $allDatesQuery,
                now()->copy()->startOfDay(),
                now()->copy()->endOfDay()
            ),
            'contrats_cette_semaine' => $this->countContractsBetween(
                clone $allDatesQuery,
                now()->copy()->startOfWeek(),
                now()->copy()->endOfWeek()
            ),
            'contrats_ce_mois' => $this->countContractsBetween(
                clone $allDatesQuery,
                now()->copy()->startOfMonth(),
                now()->copy()->endOfMonth()
            ),
            'contrats_cette_annee' => $this->countContractsBetween(
                clone $allDatesQuery,
                now()->copy()->startOfYear(),
                now()->copy()->endOfYear()
            ),
            'total_contrats_periode' => (clone $query)->count('contrats.uuid_contrat'),
            'contrats_en_attente_validation' => (clone $query)->where('contrats.etape', 2)->count(),
            'contrats_transmis' => (clone $query)->whereIn('contrats.etape', [2, 3, 4])
                ->whereNotNull('contrats.date_transmission')->count(),
            'contrats_valides' => (clone $query)->where('contrats.etape', 3)->count(),
            'contrats_rejetes' => (clone $query)->where('contrats.etape', 4)->count(),
            'contrats_annules' => (clone $query)->whereNotNull('contrats.date_annulation')->count(),
            'partenaires_actifs' => (clone $query)->whereNotNull('contrats.partner_uuid')
                ->distinct()->count('contrats.partner_uuid'),
            'agences_actives' => (clone $query)->whereNotNull('contrats.agence_uuid')
                ->distinct()->count('contrats.agence_uuid'),
            'reseaux_actifs' => (clone $query)
                ->join('agences as dashboard_agences', 'contrats.agence_uuid', '=', 'dashboard_agences.uuid_agence')
                ->whereNotNull('dashboard_agences.reseau_uuid')
                ->distinct()->count('dashboard_agences.reseau_uuid'),
        ];

        $statusTotals = $this->getDashboardStatusTotals(clone $query);
        $totalSaisie = (clone $allStagesQuery)->count('contrats.uuid_contrat');
        $saisiesNonTransmis = (clone $allStagesQuery)->where('contrats.etape', 1)
            ->count('contrats.uuid_contrat');

        return [
            'meilleurs' => $leaders,
            'top_5_par_primes' => $topByPremium,
            'top_5_par_contrats' => $this->getTopFiveByContracts($query, $rankings),
            'evolution_souscriptions' => $evolution,
            'indicateurs_volume' => $volume,
            'indicateurs' => [
                'total_saisie' => $totalSaisie,
                'saisie_non_transmis' => $saisiesNonTransmis,
                'total_propositions' => $statusTotals['total_propositions'],
                'total_en_attente' => $statusTotals['total_en_attente'],
                'total_transmis' => $statusTotals['total_transmis'],
                'total_valides' => $statusTotals['total_valides'],
                'total_rejetes' => $statusTotals['total_rejetes'],
            ],
            'repartition_statuts' => $this->getDashboardStatusDistribution($statusTotals),
            'repartition_produits' => $this->getDashboardProductDistribution(clone $query),
            'evolution_propositions' => $this->getPropositionEvolution($filters),
        ];
    }

    private function dashboardQuery(array $filters, bool $applyDateRange, bool $applyStatusFilter = true): Builder
    {
        $query = Contrat::query();

        foreach (['agence_uuid', 'partner_uuid', 'code_produit'] as $filter) {
            if (!empty($filters[$filter])) {
                $query->where('contrats.' . $filter, $filters[$filter]);
            }
        }

        $commercialUuid = $filters['commercial_uuid'] ?? $filters['conseiller_uuid'] ?? null;
        if (!empty($commercialUuid)) {
            $query->where('contrats.conseiller_uuid', $commercialUuid);
        }

        if ($applyStatusFilter && !empty($filters['statut'])) {
            $this->applyDashboardStatusFilter($query, (string) $filters['statut']);
        }

        if (!empty($filters['reseau_uuid'])) {
            $query->whereExists(function ($subquery) use ($filters): void {
                $subquery->selectRaw('1')
                    ->from('agences as filter_agences')
                    ->whereColumn('filter_agences.uuid_agence', 'contrats.agence_uuid')
                    ->where('filter_agences.reseau_uuid', $filters['reseau_uuid']);
            });
        }

        if ($applyDateRange && !empty($filters['date'])) {
            $query->whereDate('contrats.created_at', $filters['date']);
        } elseif ($applyDateRange && !empty($filters['date_debut'])) {
            $query->whereDate('contrats.created_at', '>=', $filters['date_debut']);
        }

        if ($applyDateRange && empty($filters['date']) && !empty($filters['date_fin'])) {
            $query->whereDate('contrats.created_at', '<=', $filters['date_fin']);
        }

        return $query;
    }

    private function applyDashboardStatusFilter(Builder $query, string $status): void
    {
        if (in_array($status, ['1', '2', '3', '4'], true)) {
            $query->where('contrats.etape', (int) $status);
            return;
        }

        match ($status) {
            'en_attente' => $query->where('contrats.etape', 2),
            'transmis' => $query->whereIn('contrats.etape', [2, 3, 4])
                ->whereNotNull('contrats.date_transmission'),
            'valide' => $query->where('contrats.etape', 3),
            'rejete' => $query->where('contrats.etape', 4),
            'annule' => $query->whereNotNull('contrats.date_annulation'),
        };
    }

    private function getDashboardStatusTotals(Builder $query): array
    {
        $row = $query
            ->selectRaw('COUNT(contrats.uuid_contrat) as total_propositions')
            ->selectRaw('COALESCE(SUM(CASE WHEN contrats.etape = 1 THEN 1 ELSE 0 END), 0) as saisies_non_transmis')
            ->selectRaw('COALESCE(SUM(CASE WHEN contrats.etape = 2 THEN 1 ELSE 0 END), 0) as total_en_attente')
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN contrats.etape IN (2, 3, 4) AND contrats.date_transmission IS NOT NULL THEN 1 ELSE 0 END), 0) as total_transmis'
            )
            ->selectRaw('COALESCE(SUM(CASE WHEN contrats.etape = 3 THEN 1 ELSE 0 END), 0) as total_valides')
            ->selectRaw('COALESCE(SUM(CASE WHEN contrats.etape = 4 THEN 1 ELSE 0 END), 0) as total_rejetes')
            ->selectRaw('COALESCE(SUM(CASE WHEN contrats.date_annulation IS NOT NULL THEN 1 ELSE 0 END), 0) as total_annules')
            ->selectRaw('COALESCE(SUM(CASE WHEN contrats.etape = 1 THEN contrats.prime ELSE 0 END), 0) as primes_saisies_non_transmises')
            ->selectRaw('COALESCE(SUM(CASE WHEN contrats.etape = 2 THEN contrats.prime ELSE 0 END), 0) as primes_en_attente')
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN contrats.etape IN (2, 3, 4) AND contrats.date_transmission IS NOT NULL THEN contrats.prime ELSE 0 END), 0) as primes_transmises'
            )
            ->selectRaw('COALESCE(SUM(CASE WHEN contrats.etape = 3 THEN contrats.prime ELSE 0 END), 0) as primes_validees')
            ->selectRaw('COALESCE(SUM(CASE WHEN contrats.etape = 4 THEN contrats.prime ELSE 0 END), 0) as primes_rejetees')
            ->selectRaw('COALESCE(SUM(CASE WHEN contrats.date_annulation IS NOT NULL THEN contrats.prime ELSE 0 END), 0) as primes_annulees')
            ->first();

        return [
            'total_propositions' => (int) $row->total_propositions,
            'saisies_non_transmis' => (int) $row->saisies_non_transmis,
            'total_en_attente' => (int) $row->total_en_attente,
            'total_transmis' => (int) $row->total_transmis,
            'total_valides' => (int) $row->total_valides,
            'total_rejetes' => (int) $row->total_rejetes,
            'total_annules' => (int) $row->total_annules,
            'primes_en_attente' => (float) $row->primes_en_attente,
            'primes_saisies_non_transmises' => (float) $row->primes_saisies_non_transmises,
            'primes_transmises' => (float) $row->primes_transmises,
            'primes_validees' => (float) $row->primes_validees,
            'primes_rejetees' => (float) $row->primes_rejetees,
            'primes_annulees' => (float) $row->primes_annulees,
        ];
    }

    private function getDashboardStatusDistribution(array $totals): array
    {
        $statuses = [
            'saisie_non_transmis' => [
                'libelle' => 'Saisie non transmise',
                'total' => 'saisies_non_transmis',
                'primes' => 'primes_saisies_non_transmises',
            ],
            'en_attente' => ['libelle' => 'En attente', 'total' => 'total_en_attente', 'primes' => 'primes_en_attente'],
            'transmis' => ['libelle' => 'Transmis', 'total' => 'total_transmis', 'primes' => 'primes_transmises'],
            'valide' => ['libelle' => 'Validé', 'total' => 'total_valides', 'primes' => 'primes_validees'],
            'rejete' => ['libelle' => 'Rejeté', 'total' => 'total_rejetes', 'primes' => 'primes_rejetees'],
            'annule' => ['libelle' => 'Annulé', 'total' => 'total_annules', 'primes' => 'primes_annulees'],
        ];

        return array_map(static fn (array $status, string $key): array => [
            'statut' => $key,
            'libelle' => $status['libelle'],
            'total' => $totals[$status['total']],
            'total_primes' => $status['primes'] === null ? 0.0 : $totals[$status['primes']],
        ], $statuses, array_keys($statuses));
    }

    private function getDashboardProductDistribution(Builder $query): array
    {
        return $query
            ->selectRaw('contrats.code_produit as code_produit')
            ->selectRaw('COALESCE(contrats.libelle_produit, contrats.code_produit) as libelle_produit')
            ->selectRaw('COUNT(contrats.uuid_contrat) as total')
            ->selectRaw('COALESCE(SUM(contrats.prime), 0) as total_primes')
            ->groupBy('contrats.code_produit', 'contrats.libelle_produit')
            ->orderByDesc('total')
            ->orderBy('libelle_produit')
            ->get()
            ->map(static fn ($row): array => [
                'code_produit' => $row->code_produit,
                'libelle' => $row->libelle_produit ?: 'Non renseigné',
                'total' => (int) $row->total,
                'total_primes' => (float) $row->total_primes,
            ])
            ->all();
    }

    private function getPropositionEvolution(array $filters): array
    {
        $granularity = $filters['granularite'] ?? 'mois';
        if (!empty($filters['date'])) {
            $rangeStart = Carbon::parse($filters['date'])->startOfDay();
            $rangeEnd = $rangeStart->copy()->endOfDay();
        } else {
            $rangeStart = Carbon::parse($filters['date_debut'] ?? now()->startOfYear())->startOfDay();
            $rangeEnd = Carbon::parse($filters['date_fin'] ?? now())->endOfDay();
        }

        $start = $this->startOfDashboardPeriod($rangeStart->copy(), $granularity);
        $end = $this->startOfDashboardPeriod($rangeEnd->copy(), $granularity);
        $periods = [];

        for ($period = $start->copy(); $period <= $end; $period = $this->nextDashboardPeriod($period, $granularity)) {
            $key = $this->formatDashboardPeriod($period, $granularity);
            $periods[$key] = [
                'periode' => $key,
                'total_propositions' => 0,
                'saisies_non_transmis' => 0,
                'total_en_attente' => 0,
                'primes_en_attente' => 0.0,
                'total_transmis' => 0,
                'primes_transmises' => 0.0,
                'total_valides' => 0,
                'primes_validees' => 0.0,
                'total_rejetes' => 0,
                'primes_rejetees' => 0.0,
            ];
        }

        $rows = $this->dashboardQuery($filters, true)
            ->whereBetween('contrats.created_at', [$rangeStart, $rangeEnd])
            ->selectRaw('DATE(contrats.created_at) as date_periode')
            ->selectRaw('COUNT(contrats.uuid_contrat) as total_propositions')
            ->selectRaw('COALESCE(SUM(CASE WHEN contrats.etape = 1 THEN 1 ELSE 0 END), 0) as saisies_non_transmis')
            ->selectRaw('COALESCE(SUM(CASE WHEN contrats.etape = 2 THEN 1 ELSE 0 END), 0) as total_en_attente')
            ->selectRaw('COALESCE(SUM(CASE WHEN contrats.etape = 2 THEN contrats.prime ELSE 0 END), 0) as primes_en_attente')
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN contrats.etape IN (2, 3, 4) AND contrats.date_transmission IS NOT NULL THEN 1 ELSE 0 END), 0) as total_transmis'
            )
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN contrats.etape IN (2, 3, 4) AND contrats.date_transmission IS NOT NULL THEN contrats.prime ELSE 0 END), 0) as primes_transmises'
            )
            ->selectRaw('COALESCE(SUM(CASE WHEN contrats.etape = 3 THEN 1 ELSE 0 END), 0) as total_valides')
            ->selectRaw('COALESCE(SUM(CASE WHEN contrats.etape = 3 THEN contrats.prime ELSE 0 END), 0) as primes_validees')
            ->selectRaw('COALESCE(SUM(CASE WHEN contrats.etape = 4 THEN 1 ELSE 0 END), 0) as total_rejetes')
            ->selectRaw('COALESCE(SUM(CASE WHEN contrats.etape = 4 THEN contrats.prime ELSE 0 END), 0) as primes_rejetees')
            ->groupByRaw('DATE(contrats.created_at)')
            ->get();

        foreach ($rows as $row) {
            $key = $this->formatDashboardPeriod(Carbon::parse($row->date_periode), $granularity);
            if (isset($periods[$key])) {
                foreach (array_keys($periods[$key]) as $field) {
                    if ($field !== 'periode') {
                        $periods[$key][$field] += str_starts_with($field, 'primes_')
                            ? (float) $row->{$field}
                            : (int) $row->{$field};
                    }
                }
            }
        }

        return [
            'granularite' => $granularity,
            'date_debut' => $rangeStart->toDateString(),
            'date_fin' => $rangeEnd->toDateString(),
            'donnees' => array_values($periods),
        ];
    }

    private function startOfDashboardPeriod(Carbon $date, string $granularity): Carbon
    {
        return match ($granularity) {
            'jour' => $date->startOfDay(),
            'semaine' => $date->startOfWeek(),
            'trimestre' => $date->startOfQuarter(),
            'semestre' => $date->setMonth($date->month <= 6 ? 1 : 7)->startOfMonth(),
            'annee' => $date->startOfYear(),
            default => $date->startOfMonth(),
        };
    }

    private function nextDashboardPeriod(Carbon $date, string $granularity): Carbon
    {
        return match ($granularity) {
            'jour' => $date->addDay(),
            'semaine' => $date->addWeek(),
            'trimestre' => $date->addQuarter(),
            'semestre' => $date->addMonths(6),
            'annee' => $date->addYear(),
            default => $date->addMonth(),
        };
    }

    private function formatDashboardPeriod(Carbon $date, string $granularity): string
    {
        return match ($granularity) {
            'jour' => $date->format('Y-m-d'),
            'semaine' => $date->format('o-\WW'),
            'trimestre' => $date->format('Y') . '-T' . $date->quarter,
            'semestre' => $date->format('Y') . '-S' . ($date->month <= 6 ? '1' : '2'),
            'annee' => $date->format('Y'),
            default => $date->format('Y-m'),
        };
    }

    private function getTopFiveByContracts(Builder $query, array $rankings): array
    {
        $topFive = [];

        foreach ($rankings as $type => $columns) {
            $topFive[$type] = $this->getDashboardRanking(
                clone $query,
                $columns['id'],
                $columns['label'],
                'contrats'
            )->take(5)->values()->all();
        }

        return $topFive;
    }

    private function getDashboardRanking(
        Builder $query,
        string $idColumn,
        string $labelColumn,
        string $orderBy = 'primes'
    ) {
        $allowedColumns = [
            'contrats.code_produit' => ['contrats.libelle_produit'],
            'dashboard_agences.uuid_agence' => ['dashboard_agences.libelle'],
            'dashboard_reseaux.uuid_reseau' => ['dashboard_reseaux.libelle'],
            'dashboard_partners.uuid_partner' => ['dashboard_partners.designation'],
        ];

        if (!in_array($labelColumn, $allowedColumns[$idColumn] ?? [], true)) {
            throw new InvalidArgumentException('Invalid dashboard ranking columns.');
        }

        if (str_starts_with($idColumn, 'dashboard_agences.')) {
            $query->join('agences as dashboard_agences', 'contrats.agence_uuid', '=', 'dashboard_agences.uuid_agence');
        } elseif (str_starts_with($idColumn, 'dashboard_reseaux.')) {
            $query->join('agences as dashboard_agences', 'contrats.agence_uuid', '=', 'dashboard_agences.uuid_agence')
                ->join('reseaux as dashboard_reseaux', 'dashboard_agences.reseau_uuid', '=', 'dashboard_reseaux.uuid_reseau');
        } elseif (str_starts_with($idColumn, 'dashboard_partners.')) {
            $query->join('partners as dashboard_partners', 'contrats.partner_uuid', '=', 'dashboard_partners.uuid_partner');
        }

        return $query
            ->select([$idColumn . ' as identifiant', $labelColumn . ' as libelle'])
            ->selectRaw('COALESCE(SUM(contrats.prime), 0) as total_primes')
            ->selectRaw('COUNT(*) as nombre_contrats')
            ->groupBy($idColumn, $labelColumn)
            ->orderByDesc($orderBy === 'contrats' ? 'nombre_contrats' : 'total_primes')
            ->orderByDesc($orderBy === 'contrats' ? 'total_primes' : 'nombre_contrats')
            ->orderBy($idColumn)
            ->get()
            ->map(static fn ($row): array => [
                'identifiant' => $row->identifiant,
                'libelle' => $row->libelle ?: $row->identifiant,
                'total_primes' => (float) $row->total_primes,
                'nombre_contrats' => (int) $row->nombre_contrats,
            ]);
    }

    private function getSubscriptionEvolution(array $filters): array
    {
        $granularity = $filters['granularite'] ?? 'mois';
        if (!empty($filters['date'])) {
            $rangeStart = Carbon::parse($filters['date'])->startOfDay();
            $rangeEnd = $rangeStart->copy()->endOfDay();
        } else {
            $rangeStart = Carbon::parse($filters['date_debut'] ?? now()->startOfYear())->startOfDay();
            $rangeEnd = Carbon::parse($filters['date_fin'] ?? now())->endOfDay();
        }

        $start = $this->startOfDashboardPeriod($rangeStart->copy(), $granularity);
        $end = $this->startOfDashboardPeriod($rangeEnd->copy(), $granularity);
        $periods = [];
        for ($period = $start->copy(); $period <= $end; $period = $this->nextDashboardPeriod($period, $granularity)) {
            $key = $this->formatDashboardPeriod($period, $granularity);
            $periods[$key] = [
                'periode' => $key,
                'all_saisies' => 0,
                'saisies_non_transmis' => 0,
                'saisies' => 0,
                'transmis' => 0,
                'acceptes' => 0,
                'rejetes' => 0,
                'annules' => 0,
            ];
        }

        $events = [
            'saisies' => ['column' => 'created_at'],
            'saisies_non_transmis' => ['column' => 'created_at', 'etape' => 1],
            'transmis' => ['column' => 'date_transmission', 'etapes' => [2, 3, 4]],
            'acceptes' => ['column' => 'acceptance_date', 'etape' => 3],
            'rejetes' => ['column' => 'date_rejet', 'etape' => 4],
            'annules' => ['column' => 'date_annulation'],
        ];

        foreach ($events as $status => $event) {
            $column = $event['column'];
            $eventQuery = $this->dashboardQuery($filters, false, $status === 'saisies' || $status === 'saisies_non_transmis' ? false : true);
            if (isset($event['etape'])) {
                $eventQuery->where('contrats.etape', $event['etape']);
            } elseif (isset($event['etapes'])) {
                $eventQuery->whereIn('contrats.etape', $event['etapes']);
            }

            $counts = $eventQuery
                ->whereNotNull('contrats.' . $column)
                ->whereBetween('contrats.' . $column, [$rangeStart, $rangeEnd])
                ->select('contrats.' . $column . ' as date_evenement')
                ->selectRaw('COUNT(DISTINCT contrats.uuid_contrat) as total')
                ->groupBy('contrats.' . $column)
                ->get();

            foreach ($counts as $count) {
                $key = $this->formatDashboardPeriod(Carbon::parse($count->date_evenement), $granularity);
                if (isset($periods[$key])) {
                    $periods[$key][$status] += (int) $count->total;
                    if ($status === 'saisies') {
                        $periods[$key]['all_saisies'] += (int) $count->total;
                    }
                }
            }
        }

        $totalSaisie = $this->dashboardQuery($filters, false, false)->count('contrats.uuid_contrat');
        $saisieNonTransmis = $this->dashboardQuery($filters, false, false)
            ->where('contrats.etape', 1)
            ->count('contrats.uuid_contrat');

        return [
            'granularite' => $granularity,
            'date_debut' => $rangeStart->toDateString(),
            'date_fin' => $rangeEnd->toDateString(),
            'total_saisie' => $totalSaisie,
            'saisie_non_transmis' => $saisieNonTransmis,
            'donnees' => array_values($periods),
        ];
    }

    private function countContractsBetween(Builder $query, $start, $end): int
    {
        return $query
            ->whereBetween('contrats.created_at', [$start, $end])
            ->count('contrats.uuid_contrat');
    }

    public function findByUuid(string $uuid): ?Contrat
    {
        return Contrat::where('uuid_contrat', $uuid)->first();
    }

    public function findWithTrashedByUuid(string $uuid): ?Contrat
    {
        return Contrat::withTrashed()->where('uuid_contrat', $uuid)->first();
    }

    public function findTrashedByUuid(string $uuid): ?Contrat
    {
        return Contrat::onlyTrashed()->where('uuid_contrat', $uuid)->first();
    }

    public function create(array $data): Contrat
    {
        return DB::transaction(function () use ($data): Contrat {
            $data['uuid_contrat'] = $data['uuid_contrat'] ?? (string) Str::uuid();
            $data['id_contrat'] = $this->generateNextIdContrat();

            $contrat = Contrat::create($data);

            ActivityLog::log([
                'user_uuid' => request()->user()?->uuid_user,
                'action' => 'create',
                'action_type' => 'crud',
                'module' => 'esouscription_contrats',
                'description' => "Création du contrat : {$contrat->id_contrat}",
                'resource_type' => 'contrat',
                'resource_id' => $contrat->uuid_contrat,
                'new_values' => $contrat->toArray(),
                'level' => 'info',
            ]);

            return $contrat;
        });
    }

    /**
     * Génère le prochain id_contrat auto-incrémenté unique.
     * Utilise un verrou pessimiste pour éviter les conflits d'accès concurrents.
     *
     * @return int
     */
    protected function generateNextIdContrat(): int
    {
        return DB::transaction(function () {
            $lastId = Contrat::withTrashed()
                ->lockForUpdate()
                ->max('id_contrat');

            return ($lastId ?? 0) + 1;
        });
    }

    public function update(Contrat $contrat, array $data): Contrat
    {
        return DB::transaction(function () use ($contrat, $data): Contrat {
            $oldValues = $contrat->toArray();
            $contrat->update($data);

            ActivityLog::log([
                'user_uuid' => request()->user()?->uuid_user,
                'action' => 'update',
                'action_type' => 'crud',
                'module' => 'esouscription_contrats',
                'description' => "Mise à jour du contrat : {$contrat->id_contrat}",
                'resource_type' => 'contrat',
                'resource_id' => $contrat->uuid_contrat,
                'old_values' => $oldValues,
                'new_values' => $contrat->toArray(),
                'level' => 'info',
            ]);

            return $contrat->fresh();
        });
    }

    public function transmit(Contrat $contrat, string $userUuid): Contrat
    {
        return DB::transaction(function () use ($contrat, $userUuid): Contrat {
            $oldValues = $contrat->toArray();
            $contrat->update([
                'etape' => 3,
                'transmis_par' => $userUuid,
                'date_transmission' => now(),
            ]);

            ActivityLog::log([
                'user_uuid' => $userUuid,
                'action' => 'transmit',
                'action_type' => 'crud',
                'module' => 'esouscription_contrats',
                'description' => "Transmission du contrat : {$contrat->id_contrat}",
                'resource_type' => 'contrat',
                'resource_id' => $contrat->uuid_contrat,
                'old_values' => $oldValues,
                'new_values' => $contrat->toArray(),
                'level' => 'info',
            ]);

            return $contrat->fresh();
        });
    }

    public function accept(Contrat $contrat, string $userUuid): Contrat
    {
        return DB::transaction(function () use ($contrat, $userUuid): Contrat {
            $oldValues = $contrat->toArray();
            $contrat->update([
                'etape' => 3,
                'accepted_by' => $userUuid,
                'acceptance_date' => now(),
            ]);

            ActivityLog::log([
                'user_uuid' => $userUuid,
                'action' => 'accept',
                'action_type' => 'crud',
                'module' => 'esouscription_contrats',
                'description' => "Acceptation du contrat : {$contrat->id_contrat}",
                'resource_type' => 'contrat',
                'resource_id' => $contrat->uuid_contrat,
                'old_values' => $oldValues,
                'new_values' => $contrat->toArray(),
                'level' => 'info',
            ]);

            return $contrat->fresh();
        });
    }

    public function reject(Contrat $contrat, array $motifs, string $userUuid): Contrat
    {
        return DB::transaction(function () use ($contrat, $motifs, $userUuid): Contrat {
            $oldValues = $contrat->toArray();
            $contrat->update([
                'etape' => 4,
                'rejeter_par' => $userUuid,
                'date_rejet' => now(),
                'motif_rejet' => array_values(array_unique($motifs)),
            ]);

            ActivityLog::log([
                'user_uuid' => $userUuid,
                'action' => 'reject',
                'action_type' => 'crud',
                'module' => 'esouscription_contrats',
                'description' => "Rejet du contrat : {$contrat->id_contrat}",
                'resource_type' => 'contrat',
                'resource_id' => $contrat->uuid_contrat,
                'old_values' => $oldValues,
                'new_values' => $contrat->toArray(),
                'level' => 'warning',
            ]);

            return $contrat->fresh();
        });
    }

    public function cancel(Contrat $contrat, string $userUuid): Contrat
    {
        return DB::transaction(function () use ($contrat, $userUuid): Contrat {
            $oldValues = $contrat->toArray();
            $contrat->update([
                'etape' => 4,
                'annuler_par' => $userUuid,
                'date_annulation' => now(),
            ]);

            ActivityLog::log([
                'user_uuid' => $userUuid,
                'action' => 'cancel',
                'action_type' => 'crud',
                'module' => 'esouscription_contrats',
                'description' => "Annulation du contrat : {$contrat->id_contrat}",
                'resource_type' => 'contrat',
                'resource_id' => $contrat->uuid_contrat,
                'old_values' => $oldValues,
                'new_values' => $contrat->toArray(),
                'level' => 'warning',
            ]);

            return $contrat->fresh();
        });
    }

    public function delete(Contrat $contrat, bool $force = false, ?string $deletedBy = null): void
    {
        DB::transaction(function () use ($contrat, $force, $deletedBy): void {
            $oldValues = $contrat->toArray();
            if ($force) {
                $contrat->forceDelete();

                ActivityLog::log([
                    'user_uuid' => $deletedBy ?? request()->user()?->uuid_user,
                    'action' => 'force_delete',
                    'action_type' => 'crud',
                    'module' => 'esouscription_contrats',
                    'description' => "Suppression définitive du contrat : {$contrat->id_contrat}",
                    'resource_type' => 'contrat',
                    'resource_id' => $contrat->uuid_contrat,
                    'old_values' => $oldValues,
                    'level' => 'warning',
                ]);

                return;
            }

            $contrat->update(['deleted_by' => $deletedBy]);
            $contrat->delete();

            ActivityLog::log([
                'user_uuid' => $deletedBy ?? request()->user()?->uuid_user,
                'action' => 'delete',
                'action_type' => 'crud',
                'module' => 'esouscription_contrats',
                'description' => "Suppression du contrat : {$contrat->id_contrat}",
                'resource_type' => 'contrat',
                'resource_id' => $contrat->uuid_contrat,
                'old_values' => $oldValues,
                'level' => 'warning',
            ]);
        });
    }

    public function restore(Contrat $contrat): Contrat
    {
        return DB::transaction(function () use ($contrat): Contrat {
            $oldValues = $contrat->toArray();
            $contrat->restore();
            $contrat->update(['deleted_by' => null]);

            ActivityLog::log([
                'user_uuid' => request()->user()?->uuid_user,
                'action' => 'restore',
                'action_type' => 'crud',
                'module' => 'esouscription_contrats',
                'description' => "Restauration du contrat : {$contrat->id_contrat}",
                'resource_type' => 'contrat',
                'resource_id' => $contrat->uuid_contrat,
                'old_values' => $oldValues,
                'new_values' => $contrat->fresh()->toArray(),
                'level' => 'info',
            ]);

            return $contrat->fresh();
        });
    }
}
