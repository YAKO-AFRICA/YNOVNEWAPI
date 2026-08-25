<?php
// app/Services/Api/Ynov/ReseauService.php
namespace App\Services\Api\Ynov;

use App\Models\Api\Ynov\parameter\Reseau;
use App\Models\Api\Ynov\parameter\ActivityLog;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class ReseauService
{
    /**
     * Créer un nouveau réseau
     */
    public function create(array $data, string $creatorUuid): Reseau
    {
        return DB::transaction(function () use ($data, $creatorUuid) {
            $reseau = Reseau::create([
                'uuid_reseau' => (string) Str::uuid(),
                'code' => $data['code'],
                'libelle' => $data['libelle'],
                'description' => $data['description'] ?? null,
                'partner_uuid' => $data['partner_uuid'] ?? null,
                'email' => $data['email'] ?? null,
                'telephone' => $data['telephone'] ?? null,
                'config' => $data['config'] ?? null,
                'metadata' => $data['metadata'] ?? null,
                'status' => $data['status'] ?? 'actif',
                'created_by' => $creatorUuid,
            ]);

            ActivityLog::log([
                'user_uuid' => $creatorUuid,
                'action' => 'create',
                'action_type' => 'crud',
                'module' => 'reseaux',
                'description' => "Création du réseau : {$reseau->libelle}",
                'resource_type' => 'reseau',
                'resource_id' => $reseau->uuid_reseau,
                'new_values' => $reseau->toArray(),
                'level' => 'info',
            ]);

            return $reseau;
        });
    }

    /**
     * Mettre à jour un réseau
     */
    public function update(Reseau $reseau, array $data, string $updaterUuid): Reseau
    {
        return DB::transaction(function () use ($reseau, $data, $updaterUuid) {
            $oldValues = $reseau->toArray();
            
            $reseau->update([
                'code' => $data['code'] ?? $reseau->code,
                'libelle' => $data['libelle'] ?? $reseau->libelle,
                'description' => $data['description'] ?? $reseau->description,
                'partner_uuid' => $data['partner_uuid'] ?? $reseau->partner_uuid,
                'email' => $data['email'] ?? $reseau->email,
                'telephone' => $data['telephone'] ?? $reseau->telephone,
                'config' => $data['config'] ?? $reseau->config,
                'metadata' => $data['metadata'] ?? $reseau->metadata,
                'status' => $data['status'] ?? $reseau->status,
                'updated_by' => $updaterUuid,
            ]);

            ActivityLog::log([
                'user_uuid' => $updaterUuid,
                'action' => 'update',
                'action_type' => 'crud',
                'module' => 'reseaux',
                'description' => "Mise à jour du réseau : {$reseau->libelle}",
                'resource_type' => 'reseau',
                'resource_id' => $reseau->uuid_reseau,
                'old_values' => $oldValues,
                'new_values' => $reseau->toArray(),
                'level' => 'info',
            ]);

            return $reseau->fresh();
        });
    }

    /**
     * Supprimer un réseau (soft delete)
     */
    public function delete(Reseau $reseau, string $deleterUuid): void
    {
        // Vérifier si le réseau a des agences
        if ($reseau->agences()->count() > 0) {
            throw new \RuntimeException('Ce réseau a des agences associées et ne peut pas être supprimé.');
        }

        $reseau->update([
            'status' => 'inactif',
            'deleted_by' => $deleterUuid,
        ]);
        
        $reseau->delete();

        ActivityLog::log([
            'user_uuid' => $deleterUuid,
            'action' => 'delete',
            'action_type' => 'crud',
            'module' => 'reseaux',
            'description' => "Suppression du réseau : {$reseau->libelle}",
            'resource_type' => 'reseau',
            'resource_id' => $reseau->uuid_reseau,
            'level' => 'warning',
        ]);
    }

    /**
     * Récupérer les réseaux avec filtres
     */
    public function getReseaux(array $filters = [], int $perPage = 20)
    {
        $query = Reseau::query()->with(['partner']);
        
        // Filtres
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        if (isset($filters['partner_uuid'])) {
            $query->where('partner_uuid', $filters['partner_uuid']);
        }
        
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->search($search);
        }
        
        return $query->orderBy('libelle')->paginate($perPage);
    }
}