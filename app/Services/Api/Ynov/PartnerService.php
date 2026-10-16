<?php
// app/Services/Api/Ynov/PartnerService.php
namespace App\Services\Api\Ynov;

use App\Models\Api\Ynov\parameter\ActivityLog;
use App\Models\Api\Ynov\parameter\Partner;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PartnerService
{
    /**
     * Créer un nouveau partenaire
     */
    public function create(array $data, string $creatorUuid): Partner
    {

        Log::info('Creating partner with data with service partenaire : ' . json_encode($data));
        return DB::transaction(function () use ($data, $creatorUuid) {
            $partner = Partner::create([
                'uuid_partner' => (string) Str::uuid(),
                'code' => $data['code'],
                'code_contractant' => $data['code_contractant'] ?? null,
                'code_branche' => $data['code_branche'] ?? null,
                'designation' => $data['designation'],
                'description' => $data['description'] ?? null,
                'logo' => $data['logo'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'status' => $data['status'] ?? 'actif',
                'created_by' => $data['created_by'] ?? $creatorUuid,
                
            ]);

            ActivityLog::log([
                'user_uuid' => $creatorUuid,
                'action' => 'create',
                'action_type' => 'crud',
                'module' => 'partners',
                'description' => "Création du partenaire : {$partner->designation}",
                'resource_type' => 'partner',
                'resource_id' => $partner->uuid_partner,
                'new_values' => $partner->toArray(),
                'level' => 'info',
            ]);

            return $partner;
        });
    }

    /**
     * Mettre à jour un partenaire
     */
    public function update(Partner $partner, array $data, string $updaterUuid): Partner
    {
        Log::info('Updating partner with data: ' . json_encode($data));
        return DB::transaction(function () use ($partner, $data, $updaterUuid) {
            $oldValues = $partner->toArray();
            
            $partner->update([
                // 'code' => $data['code'] ?? $partner->code,
                'designation' => $data['designation'] ?? $partner->designation,
                'code_contractant' => $data['code_contractant'] ?? $partner->code_contractant,
                'code_branche' => $data['code_branche'] ?? $partner->code_branche,
                'description' => $data['description'] ?? $partner->description,
                'logo' => $data['logo'] ?? $partner->logo,
                'is_active' => $data['is_active'] ?? $partner->is_active,
                'status' => $data['status'] ?? $partner->status,
                'created_by' => $data['created_by'] ?? $partner->created_by,
                
            ]);

            ActivityLog::log([
                'user_uuid' => $updaterUuid,
                'action' => 'update',
                'action_type' => 'crud',
                'module' => 'partners',
                'description' => "Mise à jour du partenaire : {$partner->designation}",
                'resource_type' => 'partner',
                'resource_id' => $partner->uuid_partner,
                'old_values' => $oldValues,
                'new_values' => $partner->toArray(),
                'level' => 'info',
            ]);

            return $partner->fresh();
        });
    }

    /**
     * Supprimer un partenaire (soft delete)
     */
    public function delete(Partner $partner, string $deleterUuid): void
    {
        // Vérifier si le partenaire a des réseaux
        if ($partner->reseaux()->count() > 0) {
            throw new \RuntimeException('Ce partenaire a des réseaux associés et ne peut pas être supprimé.');
        }

        $partner->update([
            'status' => 'inactif',
            'is_active' => false,
            'deleted_by' => $deleterUuid,
        ]);
        
        $partner->delete();

        ActivityLog::log([
            'user_uuid' => $deleterUuid,
            'action' => 'delete',
            'action_type' => 'crud',
            'module' => 'partners',
            'description' => "Suppression du partenaire : {$partner->designation}",
            'resource_type' => 'partner',
            'resource_id' => $partner->uuid_partner,
            'level' => 'warning',
        ]);
    }

    /**
     * Récupérer les partenaires avec filtres
     */
    public function getPartners(array $filters = [], int $perPage = 20)
    {
        $query = Partner::query()->with(['reseaux', 'reseaux.agences', 'reseaux.agences.horaires', 'users', 'logoDocuments']);
        
        // Filtres
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        if (isset($filters['is_active'])) {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        if (isset($filters['code_branche'])) {
            $query->where('code_branche', $filters['code_branche']);
        }

        if (isset($filters['code_contractant'])) {
            $query->where('code_contractant', $filters['code_contractant']);
        }
        
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('designation', 'LIKE', "%{$search}%")
                  ->orWhere('code', 'LIKE', "%{$search}%")
                  ->orWhere('code_contractant', 'LIKE', "%{$search}%")
                  ->orWhere('code_branche', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        return $query->orderBy('designation')->paginate($perPage);
    }
}