<?php

namespace App\Services\Api\Ynov\Esouscription;

use App\Models\Api\Ynov\Esouscription\ContratActeur;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ContratActeurService
{
    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = ContratActeur::query();

        foreach (['contrat_uuid', 'acteur_uuid', 'type_acteur', 'type_beneficiaire'] as $field) {
            if (!empty($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }

        if (($filters['etat'] ?? null) === 'supprime') {
            $query->onlyTrashed();
        }

        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    public function findByUuid(string $uuid): ?ContratActeur
    {
        return ContratActeur::where('uuid_contrat_acteur', $uuid)->first();
    }

    public function findWithTrashedByUuid(string $uuid): ?ContratActeur
    {
        return ContratActeur::withTrashed()
            ->where('uuid_contrat_acteur', $uuid)
            ->first();
    }

    public function findTrashedByUuid(string $uuid): ?ContratActeur
    {
        return ContratActeur::onlyTrashed()
            ->where('uuid_contrat_acteur', $uuid)
            ->first();
    }

    public function create(array $data): ContratActeur
    {
        return DB::transaction(function () use ($data): ContratActeur {
            // $data['uuid_contrat_acteur'] = (string) Str::uuid();

            return ContratActeur::create($data);
        });
    }

    public function update(ContratActeur $contratActeur, array $data): ContratActeur
    {
        return DB::transaction(function () use ($contratActeur, $data): ContratActeur {
            $contratActeur->update($data);

            return $contratActeur->fresh();
        });
    }

    public function delete(
        ContratActeur $contratActeur,
        bool $force = false,
        ?string $deletedBy = null
    ): void {
        DB::transaction(function () use ($contratActeur, $force, $deletedBy): void {
            if ($force) {
                $contratActeur->forceDelete();

                return;
            }

            $contratActeur->update(['deleted_by' => $deletedBy]);
            $contratActeur->delete();
        });
    }

    public function restore(ContratActeur $contratActeur): ContratActeur
    {
        return DB::transaction(function () use ($contratActeur): ContratActeur {
            $contratActeur->restore();
            $contratActeur->update(['deleted_by' => null]);

            return $contratActeur->fresh();
        });
    }
}