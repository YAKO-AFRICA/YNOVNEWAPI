<?php

namespace App\Services\Api\Ynov\Esouscription;

use App\Models\Api\Ynov\Esouscription\Contrat;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
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

            return Contrat::create($data);
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
            $contrat->update($data);

            return $contrat->fresh();
        });
    }

    public function delete(Contrat $contrat, bool $force = false, ?string $deletedBy = null): void
    {
        DB::transaction(function () use ($contrat, $force, $deletedBy): void {
            if ($force) {
                $contrat->forceDelete();

                return;
            }

            $contrat->update(['deleted_by' => $deletedBy]);
            $contrat->delete();
        });
    }

    public function restore(Contrat $contrat): Contrat
    {
        return DB::transaction(function () use ($contrat): Contrat {
            $contrat->restore();
            $contrat->update(['deleted_by' => null]);

            return $contrat->fresh();
        });
    }
}
