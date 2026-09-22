<?php

namespace App\Services\Api\Ynov\Esouscription;

use App\Models\Api\Ynov\Esouscription\Acteur;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class ActeurService
{
    public function getActeurs(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = Acteur::query();

        foreach (['uuid_acteur', 'idClient', 'code', 'email', 'nni'] as $field) {
            if (!empty($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }

        foreach (['nom', 'prenoms'] as $field) {
            if (!empty($filters[$field])) {
                $query->where($field, 'like', '%' . $filters[$field] . '%');
            }
        }

        if (($filters['etat'] ?? null) === 'actif') {
            $query->whereNull('deleted_at');
        } elseif (($filters['etat'] ?? null) === 'supprime') {
            $query->onlyTrashed();
        }

        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    public function findByUuid(string $uuid): ?Acteur
    {
        return Acteur::where('uuid_acteur', $uuid)->first();
    }

    public function findTrashedByUuid(string $uuid): ?Acteur
    {
        return Acteur::onlyTrashed()->where('uuid_acteur', $uuid)->first();
    }

    public function findWithTrashedByUuid(string $uuid): ?Acteur
    {
        return Acteur::withTrashed()->where('uuid_acteur', $uuid)->first();
    }

    public function create(array $data): Acteur
    {
        return DB::transaction(function () use ($data): Acteur {
            $data['uuid_acteur'] = (string) Str::uuid();
            $data['code'] = Refgenerate(Acteur::class, 'AC', 'code');

            return Acteur::create($data);
        });
    }

    public function update(Acteur $acteur, array $data): Acteur
    {
        return DB::transaction(function () use ($acteur, $data): Acteur {
            $acteur->update($data);

            return $acteur->fresh();
        });
    }

    public function delete(Acteur $acteur, bool $force = false, ?string $deletedBy = null): void
    {
        DB::transaction(function () use ($acteur, $force, $deletedBy): void {
            if ($force) {
                $acteur->forceDelete();

                return;
            }

            $acteur->update(['deleted_by' => $deletedBy]);
            $acteur->delete();
        });
    }

    public function restore(Acteur $acteur): Acteur
    {
        return DB::transaction(function () use ($acteur): Acteur {
            $acteur->restore();
            $acteur->update(['deleted_by' => null]);

            return $acteur->fresh();
        });
    }
}