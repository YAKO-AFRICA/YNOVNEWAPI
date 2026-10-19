<?php

namespace App\Services\Api\Ynov\ESouscription;

use App\Models\Api\Ynov\Esouscription\ContratActeur;
use App\Models\Api\Ynov\parameter\ActivityLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

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
            $relation = ContratActeur::create($data);

            ActivityLog::log([
                'user_uuid' => request()->user()?->uuid_user,
                'action' => 'create',
                'action_type' => 'crud',
                'module' => 'esouscription_contrat_acteurs',
                'description' => "Création du lien contrat-acteur : {$relation->uuid_contrat_acteur}",
                'resource_type' => 'contrat_acteur',
                'resource_id' => $relation->uuid_contrat_acteur,
                'new_values' => $relation->toArray(),
                'level' => 'info',
            ]);

            return $relation;
        });
    }

    public function update(ContratActeur $contratActeur, array $data): ContratActeur
    {
        return DB::transaction(function () use ($contratActeur, $data): ContratActeur {
            $oldValues = $contratActeur->toArray();
            $contratActeur->update($data);

            ActivityLog::log([
                'user_uuid' => request()->user()?->uuid_user,
                'action' => 'update',
                'action_type' => 'crud',
                'module' => 'esouscription_contrat_acteurs',
                'description' => "Mise à jour du lien contrat-acteur : {$contratActeur->uuid_contrat_acteur}",
                'resource_type' => 'contrat_acteur',
                'resource_id' => $contratActeur->uuid_contrat_acteur,
                'old_values' => $oldValues,
                'new_values' => $contratActeur->toArray(),
                'level' => 'info',
            ]);

            return $contratActeur->fresh();
        });
    }

    public function delete(
        ContratActeur $contratActeur,
        bool $force = false,
        ?string $deletedBy = null
    ): void {
        DB::transaction(function () use ($contratActeur, $force, $deletedBy): void {
            $oldValues = $contratActeur->toArray();
            if ($force) {
                $contratActeur->forceDelete();

                ActivityLog::log([
                    'user_uuid' => $deletedBy ?? request()->user()?->uuid_user,
                    'action' => 'force_delete',
                    'action_type' => 'crud',
                    'module' => 'esouscription_contrat_acteurs',
                    'description' => "Suppression définitive du lien contrat-acteur : {$contratActeur->uuid_contrat_acteur}",
                    'resource_type' => 'contrat_acteur',
                    'resource_id' => $contratActeur->uuid_contrat_acteur,
                    'old_values' => $oldValues,
                    'level' => 'warning',
                ]);

                return;
            }

            $contratActeur->update(['deleted_by' => $deletedBy]);
            $contratActeur->delete();

            ActivityLog::log([
                'user_uuid' => $deletedBy ?? request()->user()?->uuid_user,
                'action' => 'delete',
                'action_type' => 'crud',
                'module' => 'esouscription_contrat_acteurs',
                'description' => "Suppression du lien contrat-acteur : {$contratActeur->uuid_contrat_acteur}",
                'resource_type' => 'contrat_acteur',
                'resource_id' => $contratActeur->uuid_contrat_acteur,
                'old_values' => $oldValues,
                'level' => 'warning',
            ]);
        });
    }

    public function restore(ContratActeur $contratActeur): ContratActeur
    {
        return DB::transaction(function () use ($contratActeur): ContratActeur {
            $oldValues = $contratActeur->toArray();
            $contratActeur->restore();
            $contratActeur->update(['deleted_by' => null]);

            ActivityLog::log([
                'user_uuid' => request()->user()?->uuid_user,
                'action' => 'restore',
                'action_type' => 'crud',
                'module' => 'esouscription_contrat_acteurs',
                'description' => "Restauration du lien contrat-acteur : {$contratActeur->uuid_contrat_acteur}",
                'resource_type' => 'contrat_acteur',
                'resource_id' => $contratActeur->uuid_contrat_acteur,
                'old_values' => $oldValues,
                'new_values' => $contratActeur->fresh()->toArray(),
                'level' => 'info',
            ]);

            return $contratActeur->fresh();
        });
    }
}
