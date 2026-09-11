<?php

namespace App\Services\Api\Ynov;

use App\Models\Api\Ynov\BordereauRdv;
use App\Models\Api\Ynov\DetailBordereauRdv;
use Illuminate\Database\Eloquent\Builder;

class BordereauService
{
    /**
     * Liste les lots avec filtres et pagination.
     */
    public function listLots(array $filters = [], int $perPage = 20)
    {
        $query = BordereauRdv::query()
            ->withCount('details')
            ->with([
                'details.rdv.client.details',
                'details.rdv.motif',
                'details.rdv.gestionnaire.details',
            ]);

        $this->applyLotFilters($query, $filters);

        $sortBy = in_array($filters['sort_by'] ?? null, ['reference', 'periode_1', 'periode_2', 'status', 'created_at'], true)
            ? $filters['sort_by']
            : 'periode_1';
        $sortOrder = in_array(strtolower((string) ($filters['sort_order'] ?? 'desc')), ['asc', 'desc'], true)
            ? strtolower((string) $filters['sort_order'])
            : 'desc';

        return $query->orderBy($sortBy, $sortOrder)
            ->paginate((int) ($filters['per_page'] ?? $perPage));
    }

    /**
     * Liste les lignes de bordereau.
     * Si bordereau_rdv_uuid est fourni, on r�cup�re le d�tail complet du lot.
     */
    public function listDetails(array $filters = [], int $perPage = 20)
    {
        $query = DetailBordereauRdv::query()
            ->with([
                'bordereauRdv',
                'rdv.client.details',
                'rdv.motif',
                'rdv.gestionnaire.details',
                'rdv.agenceSouhaitee',
                'rdv.agenceEffective',
            ]);

        $this->applyDetailFilters($query, $filters);

        $sortBy = in_array($filters['sort_by'] ?? null, ['status', 'rdv.date_rdv_effective', 'rdv.date_rdv_souhaitee', 'created_at'], true)
            ? $filters['sort_by']
            : 'rdv.date_rdv_effective' ?? 'rdv.date_rdv_souhaitee' ?? 'created_at';
        $sortOrder = in_array(strtolower((string) ($filters['sort_order'] ?? 'asc')), ['asc', 'desc'], true)
            ? strtolower((string) $filters['sort_order'])
            : 'asc';

        return $query->orderBy($sortBy, $sortOrder)
            ->paginate((int) ($filters['per_page'] ?? $perPage));
    }

    private function applyLotFilters(Builder $query, array $filters): void
    {
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                    ->orWhereDate('periode_1', $search)
                    ->orWhereDate('periode_2', $search)
                    ->orWhereHas('details.rdv', function ($sub) use ($search) {
                        $sub->where('code', 'like', "%{$search}%")
                            ->orWhereHas('client.details', function ($clientQuery) use ($search) {
                                $clientQuery->where('nom', 'like', "%{$search}%")
                                    ->orWhere('prenoms', 'like', "%{$search}%");
                            });
                    });
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['reference'])) {
            $query->where('reference', 'like', "%{$filters['reference']}%") ;
        }

        if (!empty($filters['periode_1'])) {
            $query->whereDate('periode_1', $filters['periode_1']) ;
        }

        if (!empty($filters['periode_2'])) {
            $query->whereDate('periode_2', $filters['periode_2']);
        }

        if (!empty($filters['date'])) {
            $query->whereDate('periode_1', '<=', $filters['date'])
                ->whereDate('periode_2', '>=', $filters['date']);
        }

        if (!empty($filters['date_debut'])) {
            $query->whereDate('date_rdv_effective', '>=', $filters['date_debut']);
        }

        if (!empty($filters['date_fin'])) {
            $query->whereDate('date_rdv_effective', '<=', $filters['date_fin']);
        }

        if (!empty($filters['agence_uuid'])) {
            $query->whereHas('details.rdv', function ($q) use ($filters) {
                $q->where('agence_souhaiter_uuid', $filters['agence_uuid'])
                    ->orWhere('agence_effective_uuid', $filters['agence_uuid']);
            });
        }

        if (!empty($filters['gestionnaire_uuid'])) {
            $query->whereHas('details.rdv', function ($q) use ($filters) {
                $q->where('gestionnaire_uuid', $filters['gestionnaire_uuid']);
            });
        }

        if (!empty($filters['motif_uuid'])) {
            $query->whereHas('details.rdv', function ($q) use ($filters) {
                $q->where('motif_rdv', $filters['motif_uuid']);
            });
        }
    }

    private function applyDetailFilters(Builder $query, array $filters): void
    {
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->whereHas('bordereauRdv', function ($sub) use ($search) {
                    $sub->where('reference', 'like', "%{$search}%");
                })->orWhereHas('rdv', function ($sub) use ($search) {
                    $sub->where('code', 'like', "%{$search}%")
                        ->orWhereHas('client.details', function ($clientQuery) use ($search) {
                            $clientQuery->where('nom', 'like', "%{$search}%")
                                ->orWhere('prenoms', 'like', "%{$search}%");
                        });
                });
            });
        }

        if (!empty($filters['status'])) {
            $query->whereHas('rdv', function ($q) use ($filters) {
                $q->where('status', $filters['status']);
            });
        }

        if (!empty($filters['bordereau_rdv_uuid'])) {
            $query->where('bordereau_rdv_uuid', $filters['bordereau_rdv_uuid']);
        }

        if (!empty($filters['rdv_uuid'])) {
            $query->where('rdv_uuid', $filters['rdv_uuid']);
        }

        if (!empty($filters['date'])) {
            $query->whereHas('bordereauRdv', function ($q) use ($filters) {
                $q->whereDate('periode_1', '<=', $filters['date'])
                    ->whereDate('periode_2', '>=', $filters['date']);
            });
        }

        if (!empty($filters['date_debut'])) {
            $query->whereHas('bordereauRdv', function ($q) use ($filters) {
                $q->whereDate('periode_2', '>=', $filters['date_debut']);
            });
        }

        if (!empty($filters['date_fin'])) {
            $query->whereHas('bordereauRdv', function ($q) use ($filters) {
                $q->whereDate('periode_1', '<=', $filters['date_fin']);
            });
        }

        if (!empty($filters['agence_uuid'])) {
            $query->whereHas('rdv', function ($q) use ($filters) {
                $q->where('agence_souhaiter_uuid', $filters['agence_uuid'])
                    ->orWhere('agence_effective_uuid', $filters['agence_uuid']);
            });
        }

        if (!empty($filters['gestionnaire_uuid'])) {
            $query->whereHas('rdv', function ($q) use ($filters) {
                $q->where('gestionnaire_uuid', $filters['gestionnaire_uuid']);
            });
        }

        if (!empty($filters['motif_uuid'])) {
            $query->whereHas('rdv', function ($q) use ($filters) {
                $q->where('motif_rdv', $filters['motif_uuid']);
            });
        }
    }
}
