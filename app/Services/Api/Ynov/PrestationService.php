<?php
// app/Services/Api/Ynov/PrestationService.php

namespace App\Services\Api\Ynov;

use App\Models\Api\Ynov\parameter\Produit;
use App\Models\Api\Ynov\parameter\ProduitPrestation;
use App\Models\Api\Ynov\parameter\TypePrestation;
use App\Models\Api\Ynov\parameter\CategoryTypePrestation;
use App\Models\Api\Ynov\parameter\ActivityLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PrestationService
{
    /**
     * Créer une catégorie de prestation
     */
    public function createCategory(array $data, string $creatorUuid): CategoryTypePrestation
    {
        return DB::transaction(function () use ($data, $creatorUuid) {
            // Vérifier si le code existe déjà
            if (isset($data['code']) && CategoryTypePrestation::where('code', $data['code'])->exists()) {
                throw ValidationException::withMessages([
                    'code' => ['Ce code est déjà utilisé.']
                ]);
            }

            $category = CategoryTypePrestation::create([
                'uuid_category_type_prestations' => (string) Str::uuid(),
                'code' => $data['code'] ?? Str::slug($data['libelle'], '_'),
                'libelle' => $data['libelle'],
                'description' => $data['description'] ?? null,
                'status' => $data['status'] ?? 'actif',
                'created_by' => $creatorUuid,
            ]);

            ActivityLog::log([
                'user_uuid' => $creatorUuid,
                'action' => 'create',
                'action_type' => 'crud',
                'module' => 'category_type_prestations',
                'description' => "Création de la catégorie de prestation : {$category->libelle}",
                'resource_type' => 'category_type_prestation',
                'resource_id' => $category->uuid_category_type_prestations,
                'new_values' => $category->toArray(),
                'level' => 'info',
            ]);

            return $category;
        });
    }

    /**
     * Mettre à jour une catégorie
     */
    public function updateCategory(CategoryTypePrestation $category, array $data, string $updaterUuid): CategoryTypePrestation
    {
        return DB::transaction(function () use ($category, $data, $updaterUuid) {
            // Vérifier si le code existe déjà (pour une autre catégorie)
            if (isset($data['code']) && 
                CategoryTypePrestation::where('code', $data['code'])
                    ->where('uuid_category_type_prestations', '!=', $category->uuid_category_type_prestations)
                    ->exists()) {
                throw ValidationException::withMessages([
                    'code' => ['Ce code est déjà utilisé.']
                ]);
            }

            $oldValues = $category->toArray();

            $category->update([
                'code' => $data['code'] ?? $category->code,
                'libelle' => $data['libelle'] ?? $category->libelle,
                'description' => $data['description'] ?? $category->description,
                'status' => $data['status'] ?? $category->status,
                'updated_by' => $updaterUuid,
            ]);

            ActivityLog::log([
                'user_uuid' => $updaterUuid,
                'action' => 'update',
                'action_type' => 'crud',
                'module' => 'category_type_prestations',
                'description' => "Mise à jour de la catégorie : {$category->libelle}",
                'resource_type' => 'category_type_prestation',
                'resource_id' => $category->uuid_category_type_prestations,
                'old_values' => $oldValues,
                'new_values' => $category->toArray(),
                'level' => 'info',
            ]);

            return $category->fresh();
        });
    }

    /**
     * Supprimer une catégorie
     */
    public function deleteCategory(CategoryTypePrestation $category, string $deleterUuid): void
    {
        // Vérifier si la catégorie contient des types de prestations
        if ($category->typePrestations()->count() > 0) {
            throw new \RuntimeException('Cette catégorie contient des types de prestations et ne peut pas être supprimée.');
        }

        $category->update([
            'status' => 'inactif',
            'deleted_by' => $deleterUuid,
        ]);

        $category->delete();

        ActivityLog::log([
            'user_uuid' => $deleterUuid,
            'action' => 'delete',
            'action_type' => 'crud',
            'module' => 'category_type_prestations',
            'description' => "Suppression de la catégorie : {$category->libelle}",
            'resource_type' => 'category_type_prestation',
            'resource_id' => $category->uuid_category_type_prestations,
            'level' => 'warning',
        ]);
    }

    /**
     * Créer un type de prestation
     */
    public function createTypePrestation(array $data, string $creatorUuid): TypePrestation
    {
        return DB::transaction(function () use ($data, $creatorUuid) {
            // Vérifier si le code existe déjà
            if (isset($data['code']) && TypePrestation::where('code', $data['code'])->exists()) {
                throw ValidationException::withMessages([
                    'code' => ['Ce code est déjà utilisé.']
                ]);
            }

            $typePrestation = TypePrestation::create([
                'uuid_type_prestation' => (string) Str::uuid(),
                'code' => $data['code'] ?? Str::slug($data['libelle'], '_'),
                'libelle' => $data['libelle'],
                'description' => $data['description'] ?? null,
                'category_uuid' => $data['category_uuid'],
                'impact' => $data['impact'] ?? TypePrestation::IMPACT_NON_SORTIE_PORTEFEUILLE,
                'delai_traitement' => $data['delai_traitement'] ?? null,
                'status' => $data['status'] ?? 'actif',
                'created_by' => $creatorUuid,
            ]);

            ActivityLog::log([
                'user_uuid' => $creatorUuid,
                'action' => 'create',
                'action_type' => 'crud',
                'module' => 'type_prestations',
                'description' => "Création du type de prestation : {$typePrestation->libelle}",
                'resource_type' => 'type_prestation',
                'resource_id' => $typePrestation->uuid_type_prestation,
                'new_values' => $typePrestation->toArray(),
                'level' => 'info',
            ]);

            return $typePrestation;
        });
    }

    /**
     * Mettre à jour un type de prestation
     */
    public function updateTypePrestation(TypePrestation $typePrestation, array $data, string $updaterUuid): TypePrestation
    {
        return DB::transaction(function () use ($typePrestation, $data, $updaterUuid) {
            // Vérifier si le code existe déjà (pour un autre type)
            if (isset($data['code']) && 
                TypePrestation::where('code', $data['code'])
                    ->where('uuid_type_prestation', '!=', $typePrestation->uuid_type_prestation)
                    ->exists()) {
                throw ValidationException::withMessages([
                    'code' => ['Ce code est déjà utilisé.']
                ]);
            }

            $oldValues = $typePrestation->toArray();

            $typePrestation->update([
                'code' => $data['code'] ?? $typePrestation->code,
                'libelle' => $data['libelle'] ?? $typePrestation->libelle,
                'description' => $data['description'] ?? $typePrestation->description,
                'category_uuid' => $data['category_uuid'] ?? $typePrestation->category_uuid,
                'impact' => $data['impact'] ?? $typePrestation->impact,
                'delai_traitement' => $data['delai_traitement'] ?? $typePrestation->delai_traitement,
                'status' => $data['status'] ?? $typePrestation->status,
                'updated_by' => $updaterUuid,
            ]);

            ActivityLog::log([
                'user_uuid' => $updaterUuid,
                'action' => 'update',
                'action_type' => 'crud',
                'module' => 'type_prestations',
                'description' => "Mise à jour du type de prestation : {$typePrestation->libelle}",
                'resource_type' => 'type_prestation',
                'resource_id' => $typePrestation->uuid_type_prestation,
                'old_values' => $oldValues,
                'new_values' => $typePrestation->toArray(),
                'level' => 'info',
            ]);

            return $typePrestation->fresh();
        });
    }

    /**
     * Supprimer un type de prestation
     */
    public function deleteTypePrestation(TypePrestation $typePrestation, string $deleterUuid): void
    {
        // Vérifier si le type de prestation est associé à des produits
        if ($typePrestation->produits()->count() > 0) {
            throw new \RuntimeException('Ce type de prestation est associé à des produits et ne peut pas être supprimé.');
        }

        $typePrestation->update([
            'status' => 'inactif',
            'deleted_by' => $deleterUuid,
        ]);

        $typePrestation->delete();

        ActivityLog::log([
            'user_uuid' => $deleterUuid,
            'action' => 'delete',
            'action_type' => 'crud',
            'module' => 'type_prestations',
            'description' => "Suppression du type de prestation : {$typePrestation->libelle}",
            'resource_type' => 'type_prestation',
            'resource_id' => $typePrestation->uuid_type_prestation,
            'level' => 'warning',
        ]);
    }

    // /**
    //  * Associer une prestation à un produit
    //  */
    // public function assignPrestation(Produit $produit, TypePrestation $typePrestation, array $data, string $creatorUuid): ProduitPrestation
    // {
    //     return DB::transaction(function () use ($produit, $typePrestation, $data, $creatorUuid) {
    //         // Vérifier si l'association existe déjà
    //         $existing = ProduitPrestation::where('produit_uuid', $produit->uuid_produit)
    //             ->where('type_prestation_uuid', $typePrestation->uuid_type_prestation)
    //             ->first();

    //         if ($existing) {
    //             throw new \RuntimeException('Cette prestation est déjà associée à ce produit.');
    //         }

    //         $association = ProduitPrestation::create([
    //             'uuid_product_prestation' => (string) Str::uuid(),
    //             'produit_uuid' => $produit->uuid_produit,
    //             'produit_type' => $data['produit_type'] ?? null,
    //             'type_prestation_uuid' => $typePrestation->uuid_type_prestation,
    //             'status' => $data['status'] ?? 'actif',
    //             'created_by' => $creatorUuid,
    //         ]);

    //         ActivityLog::log([
    //             'user_uuid' => $creatorUuid,
    //             'action' => 'assign',
    //             'action_type' => 'crud',
    //             'module' => 'produit_prestations',
    //             'description' => "Association de la prestation {$typePrestation->libelle} au produit {$produit->libelle}",
    //             'resource_type' => 'produit_prestation',
    //             'resource_id' => $association->uuid_product_prestation,
    //             'new_values' => $association->toArray(),
    //             'level' => 'info',
    //         ]);

    //         return $association;
    //     });
    // }

    /**
     * Associer une ou plusieurs prestations à un produit
     */
    public function assignPrestations(Produit $produit, array $data, string $creatorUuid): array
    {
        return DB::transaction(function () use ($produit, $data, $creatorUuid) {
            $associations = [];
            $prestationUuids = $data['type_prestation_uuids'] ?? [];
            $produitType = $produit->typeProduit->libelle ?? null;
            $status = $data['status'] ?? 'actif';

            if (empty($prestationUuids)) {
                throw new \RuntimeException('Aucune prestation sélectionnée.');
            }

            // Récupérer tous les types de prestations en une seule requête
            $typePrestations = TypePrestation::whereIn('uuid_type_prestation', $prestationUuids)
                ->where('status', 'actif')
                ->get()
                ->keyBy('uuid_type_prestation');

            // Vérifier que toutes les prestations existent
            $missing = array_diff($prestationUuids, $typePrestations->keys()->toArray());
            if (!empty($missing)) {
                throw new \RuntimeException('Certaines prestations sélectionnées n\'existent pas ou sont inactives.');
            }

            // Récupérer les associations déjà existantes
            $existingAssociations = ProduitPrestation::where('produit_uuid', $produit->uuid_produit)
                ->whereIn('type_prestation_uuid', $prestationUuids)
                ->pluck('type_prestation_uuid')
                ->toArray();

            $existingUuids = [];

            foreach ($typePrestations as $uuid => $typePrestation) {
                // Vérifier si l'association existe déjà
                if (in_array($uuid, $existingAssociations)) {
                    $existingUuids[] = $uuid;
                    continue;
                }

                $association = ProduitPrestation::create([
                    'uuid_product_prestation' => (string) Str::uuid(),
                    'produit_uuid' => $produit->uuid_produit,
                    'produit_type' => $produitType,
                    'type_prestation_uuid' => $uuid,
                    'status' => $status,
                    'created_by' => $creatorUuid,
                ]);

                $associations[] = $association;

                ActivityLog::log([
                    'user_uuid' => $creatorUuid,
                    'action' => 'assign',
                    'action_type' => 'crud',
                    'module' => 'produit_prestations',
                    'description' => "Association de la prestation {$typePrestation->libelle} au produit {$produit->libelle}",
                    'resource_type' => 'produit_prestation',
                    'resource_id' => $association->uuid_product_prestation,
                    'new_values' => $association->toArray(),
                    'level' => 'info',
                ]);
            }

            // Si des prestations étaient déjà associées, les retourner dans le message
            if (!empty($existingUuids) && empty($associations)) {
                throw new \RuntimeException('Toutes les prestations sélectionnées sont déjà associées à ce produit.');
            }

            return [
                'associations' => $associations,
                'already_assigned' => $existingUuids,
                'assigned_count' => count($associations),
                'skipped_count' => count($existingUuids),
            ];
        });
    }

    /**
     * Associer une seule prestation à un produit (méthode existante)
     */
    public function assignPrestation(Produit $produit, TypePrestation $typePrestation, array $data, string $creatorUuid): ProduitPrestation
    {
        return DB::transaction(function () use ($produit, $typePrestation, $data, $creatorUuid) {
            // Vérifier si l'association existe déjà
            $existing = ProduitPrestation::where('produit_uuid', $produit->uuid_produit)
                ->where('type_prestation_uuid', $typePrestation->uuid_type_prestation)
                ->first();

            if ($existing) {
                throw new \RuntimeException('Cette prestation est déjà associée à ce produit.');
            }

            $association = ProduitPrestation::create([
                'uuid_product_prestation' => (string) Str::uuid(),
                'produit_uuid' => $produit->uuid_produit,
                'produit_type' => $produit->typeProduit->libelle ?? null,
                'type_prestation_uuid' => $typePrestation->uuid_type_prestation,
                'status' => $data['status'] ?? 'actif',
                'created_by' => $creatorUuid,
            ]);

            ActivityLog::log([
                'user_uuid' => $creatorUuid,
                'action' => 'assign',
                'action_type' => 'crud',
                'module' => 'produit_prestations',
                'description' => "Association de la prestation {$typePrestation->libelle} au produit {$produit->libelle}",
                'resource_type' => 'produit_prestation',
                'resource_id' => $association->uuid_product_prestation,
                'new_values' => $association->toArray(),
                'level' => 'info',
            ]);

            return $association;
        });
    }

    /**
     * Retirer une prestation d'un produit
     */
    public function removePrestation(ProduitPrestation $association, string $removerUuid): void
    {
        $association->update([
            'status' => 'inactif',
            'deleted_by' => $removerUuid,
        ]);

        $association->delete();

        ActivityLog::log([
            'user_uuid' => $removerUuid,
            'action' => 'remove',
            'action_type' => 'crud',
            'module' => 'produit_prestations',
            'description' => "Retrait de la prestation du produit",
            'resource_type' => 'produit_prestation',
            'resource_id' => $association->uuid_product_prestation,
            'level' => 'info',
        ]);
    }

    /**
     * Récupérer les catégories avec leurs types de prestations
     */
    public function getCategoriesWithTypes(array $filters = [], int $perPage = 20)
    {
        $query = CategoryTypePrestation::with(['typePrestations' => function ($q) {
            $q->where('status', 'actif')->orderBy('libelle');
        }]);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['search'])) {
            $query->search($filters['search']);
        }

        return $query->orderBy('libelle')->paginate($perPage);
    }

    /**
     * Récupérer les types de prestations disponibles pour un produit
     */
    public function getAvailableTypesForProduit(string $produitUuid, array $filters = [])
    {
        $query = TypePrestation::whereDoesntHave('produits', function ($q) use ($produitUuid) {
            $q->where('produit_uuid', $produitUuid);
        })->where('status', 'actif');

        if (isset($filters['category_uuid'])) {
            $query->where('category_uuid', $filters['category_uuid']);
        }

        if (isset($filters['search'])) {
            $query->search($filters['search']);
        }

        return $query->orderBy('libelle')->get();
    }

    /**
     * Statistiques des prestations
     */
    public function getStats(): array
    {
        return [
            'categories_total' => CategoryTypePrestation::count(),
            'categories_active' => CategoryTypePrestation::active()->count(),
            'types_total' => TypePrestation::count(),
            'types_active' => TypePrestation::active()->count(),
            'associations_total' => ProduitPrestation::count(),
            'associations_active' => ProduitPrestation::active()->count(),
        ];
    }
}