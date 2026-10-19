<?php

namespace App\Services\Api\Ynov\ESouscription;

use App\Models\Api\Ynov\Esouscription\Contrat;
use App\Models\Api\Ynov\parameter\ActivityLog;
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
