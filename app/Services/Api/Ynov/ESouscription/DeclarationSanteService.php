<?php

namespace App\Services\Api\Ynov\ESouscription;

use App\Models\Api\Ynov\Esouscription\DeclarationSante;
use App\Models\Api\Ynov\parameter\ActivityLog;
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

            $declaration = DeclarationSante::create($data);

            ActivityLog::log([
                'user_uuid' => request()->user()?->uuid_user,
                'action' => 'create',
                'action_type' => 'crud',
                'module' => 'esouscription_sante',
                'description' => "Création d'une déclaration santé : {$declaration->uuid_declaration_santes}",
                'resource_type' => 'declaration_sante',
                'resource_id' => $declaration->uuid_declaration_santes,
                'new_values' => $declaration->toArray(),
                'level' => 'info',
            ]);

            return $declaration;
        });
    }

    public function update(DeclarationSante $declaration, array $data): DeclarationSante
    {
        return DB::transaction(function () use ($declaration, $data): DeclarationSante {
            $oldValues = $declaration->toArray();
            $declaration->update($data);

            ActivityLog::log([
                'user_uuid' => request()->user()?->uuid_user,
                'action' => 'update',
                'action_type' => 'crud',
                'module' => 'esouscription_sante',
                'description' => "Mise à jour d'une déclaration santé : {$declaration->uuid_declaration_santes}",
                'resource_type' => 'declaration_sante',
                'resource_id' => $declaration->uuid_declaration_santes,
                'old_values' => $oldValues,
                'new_values' => $declaration->toArray(),
                'level' => 'info',
            ]);

            return $declaration->fresh();
        });
    }

    public function delete(
        DeclarationSante $declaration,
        bool $force = false,
        ?string $deletedBy = null
    ): void {
        DB::transaction(function () use ($declaration, $force, $deletedBy): void {
            $oldValues = $declaration->toArray();
            if ($force) {
                $declaration->forceDelete();

                ActivityLog::log([
                    'user_uuid' => $deletedBy ?? request()->user()?->uuid_user,
                    'action' => 'force_delete',
                    'action_type' => 'crud',
                    'module' => 'esouscription_sante',
                    'description' => "Suppression définitive d'une déclaration santé : {$declaration->uuid_declaration_santes}",
                    'resource_type' => 'declaration_sante',
                    'resource_id' => $declaration->uuid_declaration_santes,
                    'old_values' => $oldValues,
                    'level' => 'warning',
                ]);

                return;
            }

            $declaration->update(['deleted_by' => $deletedBy]);
            $declaration->delete();

            ActivityLog::log([
                'user_uuid' => $deletedBy ?? request()->user()?->uuid_user,
                'action' => 'delete',
                'action_type' => 'crud',
                'module' => 'esouscription_sante',
                'description' => "Suppression d'une déclaration santé : {$declaration->uuid_declaration_santes}",
                'resource_type' => 'declaration_sante',
                'resource_id' => $declaration->uuid_declaration_santes,
                'old_values' => $oldValues,
                'level' => 'warning',
            ]);
        });
    }

    public function restore(DeclarationSante $declaration): DeclarationSante
    {
        return DB::transaction(function () use ($declaration): DeclarationSante {
            $oldValues = $declaration->toArray();
            $declaration->restore();
            $declaration->update(['deleted_by' => null]);

            ActivityLog::log([
                'user_uuid' => request()->user()?->uuid_user,
                'action' => 'restore',
                'action_type' => 'crud',
                'module' => 'esouscription_sante',
                'description' => "Restauration d'une déclaration santé : {$declaration->uuid_declaration_santes}",
                'resource_type' => 'declaration_sante',
                'resource_id' => $declaration->uuid_declaration_santes,
                'old_values' => $oldValues,
                'new_values' => $declaration->fresh()->toArray(),
                'level' => 'info',
            ]);

            return $declaration->fresh();
        });
    }
}
