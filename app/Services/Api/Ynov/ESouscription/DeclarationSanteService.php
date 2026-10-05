<?php

namespace App\Services\Api\Ynov\Esouscription;

use App\Models\Api\Ynov\Esouscription\DeclarationSante;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DeclarationSanteService
{
    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = DeclarationSante::query();

        foreach (['contrat_uuid', 'assure_uuid'] as $field) {
            if (!empty($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }

        if (($filters['etat'] ?? null) === 'supprime') {
            $query->onlyTrashed();
        }

        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    public function getTrashed(): \Illuminate\Database\Eloquent\Collection
    {
        return DeclarationSante::onlyTrashed()->latest('deleted_at')->get();
    }

    public function findByUuid(string $uuid): ?DeclarationSante
    {
        return DeclarationSante::where('uuid_declaration_santes', $uuid)->first();
    }

    public function findWithTrashedByUuid(string $uuid): ?DeclarationSante
    {
        return DeclarationSante::withTrashed()
            ->where('uuid_declaration_santes', $uuid)
            ->first();
    }

    public function findTrashedByUuid(string $uuid): ?DeclarationSante
    {
        return DeclarationSante::onlyTrashed()
            ->where('uuid_declaration_santes', $uuid)
            ->first();
    }

    public function create(array $data): DeclarationSante
    {
        return DB::transaction(function () use ($data): DeclarationSante {
            $data['uuid_declaration_santes'] = (string) Str::uuid();

            return DeclarationSante::create($data);
        });
    }

    public function update(DeclarationSante $declaration, array $data): DeclarationSante
    {
        return DB::transaction(function () use ($declaration, $data): DeclarationSante {
            $declaration->update($data);

            return $declaration->fresh();
        });
    }

    public function delete(
        DeclarationSante $declaration,
        bool $force = false,
        ?string $deletedBy = null
    ): void {
        DB::transaction(function () use ($declaration, $force, $deletedBy): void {
            if ($force) {
                $declaration->forceDelete();

                return;
            }

            $declaration->update(['deleted_by' => $deletedBy]);
            $declaration->delete();
        });
    }

    public function restore(DeclarationSante $declaration): DeclarationSante
    {
        return DB::transaction(function () use ($declaration): DeclarationSante {
            $declaration->restore();
            $declaration->update(['deleted_by' => null]);

            return $declaration->fresh();
        });
    }
}