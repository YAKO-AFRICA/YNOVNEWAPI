<?php

namespace App\Services\Api\Ynov\ESouscription;

use \InvalidArgumentException;
use App\Models\Api\Ynov\Esouscription\Contrat;
use App\Models\Api\Ynov\parameter\ActivityLog;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
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
            'contrats_valides' => (clone $query)->whereNotNull('contrats.acceptance_date')->count(),
            'contrats_annules' => (clone $query)->where('contrats.etape', 4)->count(),
            'partenaires_actifs' => (clone $query)->whereNotNull('contrats.partner_uuid')
                ->distinct()->count('contrats.partner_uuid'),
            'agences_actives' => (clone $query)->whereNotNull('contrats.agence_uuid')
                ->distinct()->count('contrats.agence_uuid'),
            'reseaux_actifs' => (clone $query)
                ->join('agences as dashboard_agences', 'contrats.agence_uuid', '=', 'dashboard_agences.uuid_agence')
                ->whereNotNull('dashboard_agences.reseau_uuid')
                ->distinct()->count('dashboard_agences.reseau_uuid'),
        ];

        return [
            'meilleurs' => $leaders,
            'top_5_par_primes' => $topByPremium,
            'top_5_par_contrats' => $this->getTopFiveByContracts($query, $rankings),
            'evolution_souscriptions' => $evolution,
            'indicateurs_volume' => $volume,
        ];
    }

    private function dashboardQuery(array $filters, bool $applyDateRange): Builder
    {
        $query = Contrat::query();

        foreach (['agence_uuid', 'partner_uuid', 'code_produit'] as $filter) {
            if (!empty($filters[$filter])) {
                $query->where('contrats.' . $filter, $filters[$filter]);
            }
        }

        if (!empty($filters['reseau_uuid'])) {
            $query->whereExists(function ($subquery) use ($filters): void {
                $subquery->selectRaw('1')
                    ->from('agences as filter_agences')
                    ->whereColumn('filter_agences.uuid_agence', 'contrats.agence_uuid')
                    ->where('filter_agences.reseau_uuid', $filters['reseau_uuid']);
            });
        }

        if ($applyDateRange && !empty($filters['date_debut'])) {
            $query->whereDate('contrats.created_at', '>=', $filters['date_debut']);
        }

        if ($applyDateRange && !empty($filters['date_fin'])) {
            $query->whereDate('contrats.created_at', '<=', $filters['date_fin']);
        }

        return $query;
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
        $rangeStart = Carbon::parse($filters['date_debut'] ?? now()->startOfYear())->startOfDay();
        $rangeEnd = Carbon::parse($filters['date_fin'] ?? now())->endOfDay();
        $start = $rangeStart->copy();
        $end = $rangeEnd->copy();

        if ($granularity === 'mois') {
            $start->startOfMonth();
            $end->startOfMonth();
            $step = 'month';
        } elseif ($granularity === 'semaine') {
            $start->startOfWeek();
            $end->startOfWeek();
            $step = 'week';
        } else {
            $step = 'day';
        }

        $periodEnd = $end->copy()->endOf($step);
        $periods = [];
        for ($period = $start->copy(); $period <= $end;) {
            $key = $this->formatEvolutionPeriod($period, $granularity);
            $periods[$key] = [
                'periode' => $key,
                'saisies' => 0,
                'transmis' => 0,
                'acceptes' => 0,
                'rejetes' => 0,
                'annules' => 0,
            ];

            if ($step === 'month') {
                $period->addMonth();
            } elseif ($step === 'week') {
                $period->addWeek();
            } else {
                $period->addDay();
            }
        }

        $events = [
            'saisies' => 'created_at',
            'transmis' => 'date_transmission',
            'acceptes' => 'acceptance_date',
            'rejetes' => 'date_rejet',
            'annules' => 'date_annulation',
        ];

        foreach ($events as $status => $column) {
            $counts = $this->dashboardQuery($filters, false)
                ->whereNotNull('contrats.' . $column)
                ->whereBetween('contrats.' . $column, [$rangeStart, $rangeEnd])
                ->select('contrats.' . $column . ' as date_evenement')
                ->selectRaw('COUNT(DISTINCT contrats.uuid_contrat) as total')
                ->groupBy('contrats.' . $column)
                ->get();

            foreach ($counts as $count) {
                $key = $this->formatEvolutionPeriod(Carbon::parse($count->date_evenement), $granularity);
                if (isset($periods[$key])) {
                    $periods[$key][$status] += (int) $count->total;
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

    private function formatEvolutionPeriod(Carbon $period, string $granularity): string
    {
        return match ($granularity) {
            'mois' => $period->format('Y-m'),
            'semaine' => $period->format('o-\WW'),
            default => $period->format('Y-m-d'),
        };
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
