<?php

namespace App\Services\Api\Ynov\Rdv;

use App\Models\Api\Ynov\BordereauRdv;
use App\Models\Api\Ynov\DetailBordereauRdv;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class BordereauDashboardService
{
    /**
     * Vue d'ensemble du dashboard bordereau.
     */
    public function getOverview(array $filters = []): array
    {
        $lotsQuery = BordereauRdv::query();
        $detailsQuery = DetailBordereauRdv::query();

        $this->applyLotFilters($lotsQuery, $filters);
        $this->applyDetailFilters($detailsQuery, $filters);

        $totalLots = $lotsQuery->count();
        $lotsEnAttente = (clone $lotsQuery)->where('status', 'en_attente')->count();
        $lotsTransfere = (clone $lotsQuery)->where('status', 'transfere')->count();
        $lotsCloture = (clone $lotsQuery)->where('status', 'cloture')->count();

        $totalDetails = $detailsQuery->count();
        $detailsEnAttente = (clone $detailsQuery)->where('status', 'en_attente')->count();
        $detailsSoumis = (clone $detailsQuery)->where('status', 'soumis')->count();
        $detailsTraite = (clone $detailsQuery)->where('status', 'traite')->count();

        return [
            'total_lots' => $totalLots,
            'lots_en_attente' => $lotsEnAttente,
            'lots_transfere' => $lotsTransfere,
            'lots_cloture' => $lotsCloture,
            'total_details' => $totalDetails,
            'details_en_attente' => $detailsEnAttente,
            'details_soumis' => $detailsSoumis,
            'details_traite' => $detailsTraite,
            'taux_traitement_details' => $totalDetails > 0
                ? round(($detailsTraite / $totalDetails) * 100, 2)
                : 0,
            'stats_par_statut' => [
                'en_attente' => $lotsEnAttente,
                'transfere' => $lotsTransfere,
                'cloture' => $lotsCloture,
            ],
        ];
    }

    /**
     * Liste paginée des lots de bordereau.
     */
    public function getLots(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = BordereauRdv::query()
            ->withCount('details')
            ->with(['details']);

        $this->applyLotFilters($query, $filters);

        $sortBy = $filters['sort_by'] ?? 'periode_1';
        $sortOrder = strtolower((string) ($filters['sort_order'] ?? 'desc'));

        if (!in_array($sortBy, ['reference', 'periode_1', 'periode_2', 'status', 'created_at'], true)) {
            $sortBy = 'periode_1';
        }

        if (!in_array($sortOrder, ['asc', 'desc'], true)) {
            $sortOrder = 'desc';
        }

        return $query->orderBy($sortBy, $sortOrder)
            ->paginate($perPage);
    }

    private function applyLotFilters(Builder $query, array $filters): void
    {
        if (!empty($filters['search'])) {
            $search = $filters['search'];

            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                    ->orWhereDate('periode_1', $search)
                    ->orWhereDate('periode_2', $search)
                    ->orWhere('status', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['date_debut'])) {
            $query->whereDate('periode_1', '>=', $filters['date_debut']);
        }

        if (!empty($filters['date_fin'])) {
            $query->whereDate('periode_2', '<=', $filters['date_fin']);
        }
    }

    private function applyDetailFilters(Builder $query, array $filters): void
    {
        if (!empty($filters['search'])) {
            $search = $filters['search'];

            $query->where(function ($q) use ($search) {
                $q->where('observation', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%")
                    ->orWhereHas('bordereauRdv', function ($sub) use ($search) {
                        $sub->where('reference', 'like', "%{$search}%");
                    });
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['date_debut'])) {
            $query->whereDate('created_at', '>=', $filters['date_debut']);
        }

        if (!empty($filters['date_fin'])) {
            $query->whereDate('created_at', '<=', $filters['date_fin']);
        }
    }
}
