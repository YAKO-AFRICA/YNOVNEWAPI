<?php

namespace App\Services\Api\Ynov\ESouscription;

use App\Models\Api\Ynov\Esouscription\Acteur;
use App\Models\Api\Ynov\parameter\ActivityLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ActeurService
{
    public function getActeurs(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = Acteur::query()->with('RelationContrat');

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
            // $data['integration_key'] = now()->format('Ymdhis');
            $data['code'] = Refgenerate(Acteur::class, 'ACT', 'code');

            $acteur = Acteur::create($data);

            ActivityLog::log([
                'user_uuid' => request()->user()?->uuid_user,
                'action' => 'create',
                'action_type' => 'crud',
                'module' => 'esouscription_acteurs',
                'description' => "Création de l'acteur : {$acteur->uuid_acteur}",
                'resource_type' => 'acteur',
                'resource_id' => $acteur->uuid_acteur,
                'new_values' => $acteur->toArray(),
                'level' => 'info',
            ]);

            return $acteur;
        });
    }

    public function update(Acteur $acteur, array $data): Acteur
    {


        return DB::transaction(function () use ($acteur, $data): Acteur {
            $oldValues = $acteur->toArray();
            $acteur->update($data);

            ActivityLog::log([
                'user_uuid' => request()->user()?->uuid_user,
                'action' => 'update',
                'action_type' => 'crud',
                'module' => 'esouscription_acteurs',
                'description' => "Mise à jour de l'acteur : {$acteur->uuid_acteur}",
                'resource_type' => 'acteur',
                'resource_id' => $acteur->uuid_acteur,
                'old_values' => $oldValues,
                'new_values' => $acteur->toArray(),
                'level' => 'info',
            ]);

            return $acteur->fresh();
        });
    }

    public function delete(Acteur $acteur, bool $force = false, ?string $deletedBy = null): void
    {
        DB::transaction(function () use ($acteur, $force, $deletedBy): void {
            $oldValues = $acteur->toArray();
            if ($force) {
                $acteur->forceDelete();

                ActivityLog::log([
                    'user_uuid' => $deletedBy ?? request()->user()?->uuid_user,
                    'action' => 'force_delete',
                    'action_type' => 'crud',
                    'module' => 'esouscription_acteurs',
                    'description' => "Suppression définitive de l'acteur : {$acteur->uuid_acteur}",
                    'resource_type' => 'acteur',
                    'resource_id' => $acteur->uuid_acteur,
                    'old_values' => $oldValues,
                    'level' => 'warning',
                ]);

                return;
            }

            $acteur->update(['deleted_by' => $deletedBy]);
            $acteur->delete();

            ActivityLog::log([
                'user_uuid' => $deletedBy ?? request()->user()?->uuid_user,
                'action' => 'delete',
                'action_type' => 'crud',
                'module' => 'esouscription_acteurs',
                'description' => "Suppression de l'acteur : {$acteur->uuid_acteur}",
                'resource_type' => 'acteur',
                'resource_id' => $acteur->uuid_acteur,
                'old_values' => $oldValues,
                'level' => 'warning',
            ]);
        });
    }

    public function restore(Acteur $acteur): Acteur
    {
        return DB::transaction(function () use ($acteur): Acteur {
            $oldValues = $acteur->toArray();
            $acteur->restore();
            $acteur->update(['deleted_by' => null]);

            ActivityLog::log([
                'user_uuid' => request()->user()?->uuid_user,
                'action' => 'restore',
                'action_type' => 'crud',
                'module' => 'esouscription_acteurs',
                'description' => "Restauration de l'acteur : {$acteur->uuid_acteur}",
                'resource_type' => 'acteur',
                'resource_id' => $acteur->uuid_acteur,
                'old_values' => $oldValues,
                'new_values' => $acteur->fresh()->toArray(),
                'level' => 'info',
            ]);

            return $acteur->fresh();
        });
    }
}
