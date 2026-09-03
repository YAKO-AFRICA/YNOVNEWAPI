<?php
// app/Services/Api/Ynov/ProduitFormuleService.php

namespace App\Services\Api\Ynov;

use App\Models\Api\Ynov\parameter\Produit;
use App\Models\Api\Ynov\parameter\ProduitFormule;
use App\Models\Api\Ynov\parameter\ActivityLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProduitFormuleService
{
    /**
     * Créer une formule pour un produit
     */
    public function createFormule(Produit $produit, array $data, string $creatorUuid): ProduitFormule
    {
        return DB::transaction(function () use ($produit, $data, $creatorUuid) {
            // Vérifier si le code de la formule existe déjà
            if (isset($data['code_produit_formule']) && 
                ProduitFormule::where('code_produit_formule', $data['code_produit_formule'])->exists()) {
                throw ValidationException::withMessages([
                    'code_produit_formule' => ['Ce code de formule est déjà utilisé.']
                ]);
            }

            $formule = ProduitFormule::create([
                'uuid_produit_formule' => (string) Str::uuid(),
                'produit_uuid' => $produit->uuid_produit,
                'code_produit_formule' => $data['code_produit_formule'] ?? Str::slug($data['libelle'], '_'),
                'code_produit' => $data['code_produit'] ?? $produit->code,
                'libelle' => $data['libelle'],
                'date_creation' => $data['date_creation'] ?? now(),
                'date_debut' => $data['date_debut'] ?? null,
                'date_fin' => $data['date_fin'] ?? null,
                'est_actif' => $data['est_actif'] ?? true,
                'code_plan_com' => $data['code_plan_com'] ?? null,
                'code_contractant' => $data['code_contractant'] ?? null,
                'code_groupe_profil' => $data['code_groupe_profil'] ?? null,
                'code_groupe_assure' => $data['code_groupe_assure'] ?? null,
                'fa' => $data['fa'] ?? null,
                'fg' => $data['fg'] ?? null,
                'tx' => $data['tx'] ?? null,
                'code_canal_distribution' => $data['code_canal_distribution'] ?? null,
                'created_by' => $creatorUuid,
            ]);

            ActivityLog::log([
                'user_uuid' => $creatorUuid,
                'action' => 'create',
                'action_type' => 'crud',
                'module' => 'produit_formules',
                'description' => "Création de la formule : {$formule->libelle} pour le produit {$produit->libelle}",
                'resource_type' => 'produit_formule',
                'resource_id' => $formule->uuid_produit_formule,
                'new_values' => $formule->toArray(),
                'level' => 'info',
            ]);

            return $formule;
        });
    }

    /**
     * Mettre à jour une formule
     */
    public function updateFormule(ProduitFormule $formule, array $data, string $updaterUuid): ProduitFormule
    {
        return DB::transaction(function () use ($formule, $data, $updaterUuid) {
            // Vérifier si le code de la formule existe déjà (pour une autre formule)
            if (isset($data['code_produit_formule']) && 
                ProduitFormule::where('code_produit_formule', $data['code_produit_formule'])
                    ->where('uuid_produit_formule', '!=', $formule->uuid_produit_formule)
                    ->exists()) {
                throw ValidationException::withMessages([
                    'code_produit_formule' => ['Ce code de formule est déjà utilisé.']
                ]);
            }

            $oldValues = $formule->toArray();

            $formule->update([
                'code_produit_formule' => $data['code_produit_formule'] ?? $formule->code_produit_formule,
                'code_produit' => $data['code_produit'] ?? $formule->code_produit,
                'libelle' => $data['libelle'] ?? $formule->libelle,
                'date_creation' => $data['date_creation'] ?? $formule->date_creation,
                'date_debut' => $data['date_debut'] ?? $formule->date_debut,
                'date_fin' => $data['date_fin'] ?? $formule->date_fin,
                'est_actif' => $data['est_actif'] ?? $formule->est_actif,
                'code_plan_com' => $data['code_plan_com'] ?? $formule->code_plan_com,
                'code_contractant' => $data['code_contractant'] ?? $formule->code_contractant,
                'code_groupe_profil' => $data['code_groupe_profil'] ?? $formule->code_groupe_profil,
                'code_groupe_assure' => $data['code_groupe_assure'] ?? $formule->code_groupe_assure,
                'fa' => $data['fa'] ?? $formule->fa,
                'fg' => $data['fg'] ?? $formule->fg,
                'tx' => $data['tx'] ?? $formule->tx,
                'code_canal_distribution' => $data['code_canal_distribution'] ?? $formule->code_canal_distribution,
                'updated_by' => $updaterUuid,
            ]);

            ActivityLog::log([
                'user_uuid' => $updaterUuid,
                'action' => 'update',
                'action_type' => 'crud',
                'module' => 'produit_formules',
                'description' => "Mise à jour de la formule : {$formule->libelle}",
                'resource_type' => 'produit_formule',
                'resource_id' => $formule->uuid_produit_formule,
                'old_values' => $oldValues,
                'new_values' => $formule->toArray(),
                'level' => 'info',
            ]);

            return $formule->fresh();
        });
    }

    /**
     * Supprimer une formule
     */
    public function deleteFormule(ProduitFormule $formule, string $deleterUuid): void
    {
        $formule->update([
            'est_actif' => false,
            'deleted_by' => $deleterUuid,
        ]);

        $formule->delete();

        ActivityLog::log([
            'user_uuid' => $deleterUuid,
            'action' => 'delete',
            'action_type' => 'crud',
            'module' => 'produit_formules',
            'description' => "Suppression de la formule : {$formule->libelle}",
            'resource_type' => 'produit_formule',
            'resource_id' => $formule->uuid_produit_formule,
            'level' => 'warning',
        ]);
    }

    /**
     * Récupérer les formules d'un produit
     */
    public function getFormulesForProduit(string $produitUuid, array $filters = [])
    {
        $query = ProduitFormule::where('produit_uuid', $produitUuid);

        if (isset($filters['est_actif'])) {
            $query->where('est_actif', $filters['est_actif']);
        }

        if (isset($filters['search'])) {
            $query->search($filters['search']);
        }

        return $query->orderBy('libelle')->get();
    }
}