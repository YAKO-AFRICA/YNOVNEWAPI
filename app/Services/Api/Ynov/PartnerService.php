<?php
// app/Services/Api/Ynov/PartnerService.php
namespace App\Services\Api\Ynov;

use App\Models\Api\Ynov\parameter\Partner;
use App\Models\Api\Ynov\parameter\ActivityLog;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class PartnerService
{
    /**
     * Créer un nouveau partenaire
     */
    public function create(array $data, string $creatorUuid): Partner
    {
        return DB::transaction(function () use ($data, $creatorUuid) {
            $partner = Partner::create([
                'uuid_partner' => (string) Str::uuid(),
                'code' => $data['code'],
                'designation' => $data['designation'],
                'sigle' => $data['sigle'] ?? null,
                'description' => $data['description'] ?? null,
                'logo' => $data['logo'] ?? null,
                'code_branche' => $data['code_branche'] ?? null,
                'email' => $data['email'] ?? null,
                'email_2' => $data['email_2'] ?? null,
                'telephone' => $data['telephone'] ?? null,
                'telephone_2' => $data['telephone_2'] ?? null,
                'adresse' => $data['adresse'] ?? null,
                'ville' => $data['ville'] ?? null,
                'pays' => $data['pays'] ?? null,
                'site_web' => $data['site_web'] ?? null,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'type' => $data['type'] ?? null,
                'secteur_activite' => $data['secteur_activite'] ?? null,
                'categorie' => $data['categorie'] ?? null,
                'config' => $data['config'] ?? null,
                'metadata' => $data['metadata'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'status' => $data['status'] ?? 'actif',
                'date_agrement' => $data['date_agrement'] ?? null,
                'date_expiration' => $data['date_expiration'] ?? null,
                'created_by' => $creatorUuid,
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
        return DB::transaction(function () use ($partner, $data, $updaterUuid) {
            $oldValues = $partner->toArray();
            
            $partner->update([
                'code' => $data['code'] ?? $partner->code,
                'designation' => $data['designation'] ?? $partner->designation,
                'sigle' => $data['sigle'] ?? $partner->sigle,
                'description' => $data['description'] ?? $partner->description,
                'logo' => $data['logo'] ?? $partner->logo,
                'code_branche' => $data['code_branche'] ?? $partner->code_branche,
                'email' => $data['email'] ?? $partner->email,
                'email_2' => $data['email_2'] ?? $partner->email_2,
                'telephone' => $data['telephone'] ?? $partner->telephone,
                'telephone_2' => $data['telephone_2'] ?? $partner->telephone_2,
                'adresse' => $data['adresse'] ?? $partner->adresse,
                'ville' => $data['ville'] ?? $partner->ville,
                'pays' => $data['pays'] ?? $partner->pays,
                'site_web' => $data['site_web'] ?? $partner->site_web,
                'latitude' => $data['latitude'] ?? $partner->latitude,
                'longitude' => $data['longitude'] ?? $partner->longitude,
                'type' => $data['type'] ?? $partner->type,
                'secteur_activite' => $data['secteur_activite'] ?? $partner->secteur_activite,
                'categorie' => $data['categorie'] ?? $partner->categorie,
                'config' => $data['config'] ?? $partner->config,
                'metadata' => $data['metadata'] ?? $partner->metadata,
                'is_active' => $data['is_active'] ?? $partner->is_active,
                'status' => $data['status'] ?? $partner->status,
                'date_agrement' => $data['date_agrement'] ?? $partner->date_agrement,
                'date_expiration' => $data['date_expiration'] ?? $partner->date_expiration,
                'updated_by' => $updaterUuid,
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
        $query = Partner::query();
        
        // Filtres
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }
        
        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        
        if (isset($filters['categorie'])) {
            $query->where('categorie', $filters['categorie']);
        }
        
        if (isset($filters['code_branche'])) {
            $query->where('code_branche', $filters['code_branche']);
        }
        
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('designation', 'LIKE', "%{$search}%")
                  ->orWhere('code', 'LIKE', "%{$search}%")
                  ->orWhere('sigle', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }
        
        if (isset($filters['not_expired']) && $filters['not_expired']) {
            $query->notExpired();
        }
        
        return $query->orderBy('designation')->paginate($perPage);
    }
}