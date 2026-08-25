<?php
// app/Services/Api/Ynov/FaqCategoryService.php
namespace App\Services\Api\Ynov;

use App\Models\Api\Ynov\parameter\FaqCategory;
use App\Models\Api\Ynov\parameter\ActivityLog;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FaqCategoryService
{
    /**
     * Créer une catégorie de FAQ
     */
    public function create(array $data, string $creatorUuid): FaqCategory
    {
        return DB::transaction(function () use ($data, $creatorUuid) {
            // Générer le code à partir du label si non fourni
            $code = $data['code'] ?? Str::slug($data['label'], '_');
            
            // Vérifier si le code existe déjà
            if (FaqCategory::where('code', $code)->exists()) {
                throw ValidationException::withMessages([
                    'code' => ['Ce code est déjà utilisé.']
                ]);
            }

            $category = FaqCategory::create([
                'uuid_faq_category' => (string) Str::uuid(),
                'code' => $code,
                'label' => $data['label'],
                'icon' => $data['icon'] ?? null,
                'color' => $data['color'] ?? null,
                'description' => $data['description'] ?? null,
                'order' => $data['order'] ?? FaqCategory::max('order') + 1,
                'is_active' => $data['is_active'] ?? true,
                'is_default' => false,
                'metadata' => $data['metadata'] ?? null,
                'created_by' => $creatorUuid,
            ]);

            ActivityLog::log([
                'user_uuid' => $creatorUuid,
                'action' => 'create',
                'action_type' => 'crud',
                'module' => 'faq_categories',
                'description' => "Création de la catégorie FAQ : {$category->label}",
                'resource_type' => 'faq_category',
                'resource_id' => $category->uuid_faq_category,
                'new_values' => $category->toArray(),
                'level' => 'info',
            ]);

            return $category;
        });
    }

    /**
     * Mettre à jour une catégorie de FAQ
     */
    public function update(FaqCategory $category, array $data, string $updaterUuid): FaqCategory
    {
        return DB::transaction(function () use ($category, $data, $updaterUuid) {
            // Vérifier si la catégorie est protégée
            if ($category->isProtected()) {
                throw ValidationException::withMessages([
                    'category' => ['Les catégories par défaut ne peuvent pas être modifiées.']
                ]);
            }

            $oldValues = $category->toArray();

            // Générer le code à partir du label si modifié
            $code = isset($data['label']) ? Str::slug($data['label'], '_') : $category->code;
            
            // Vérifier si le code existe déjà (sauf pour la catégorie elle-même)
            if (isset($data['label']) && FaqCategory::where('code', $code)->where('uuid_faq_category', '!=', $category->uuid_faq_category)->exists()) {
                throw ValidationException::withMessages([
                    'code' => ['Ce code est déjà utilisé.']
                ]);
            }

            $category->update([
                'label' => $data['label'] ?? $category->label,
                'code' => $code,
                'icon' => $data['icon'] ?? $category->icon,
                'color' => $data['color'] ?? $category->color,
                'description' => $data['description'] ?? $category->description,
                'order' => $data['order'] ?? $category->order,
                'is_active' => $data['is_active'] ?? $category->is_active,
                'metadata' => $data['metadata'] ?? $category->metadata,
                'updated_by' => $updaterUuid,
            ]);

            ActivityLog::log([
                'user_uuid' => $updaterUuid,
                'action' => 'update',
                'action_type' => 'crud',
                'module' => 'faq_categories',
                'description' => "Mise à jour de la catégorie FAQ : {$category->label}",
                'resource_type' => 'faq_category',
                'resource_id' => $category->uuid_faq_category,
                'old_values' => $oldValues,
                'new_values' => $category->toArray(),
                'level' => 'info',
            ]);

            return $category->fresh();
        });
    }

    /**
     * Supprimer une catégorie de FAQ
     */
    public function delete(FaqCategory $category, string $deleterUuid): void
    {
        DB::transaction(function () use ($category, $deleterUuid) {
            // Vérifier si la catégorie est protégée
            if ($category->isProtected()) {
                throw ValidationException::withMessages([
                    'category' => ['Les catégories par défaut ne peuvent pas être supprimées.']
                ]);
            }

            // Vérifier si la catégorie a des FAQs
            if ($category->faqs()->count() > 0) {
                throw ValidationException::withMessages([
                    'category' => ['Cette catégorie contient des FAQs et ne peut pas être supprimée.']
                ]);
            }

            $category->update([
                'is_active' => false,
                'deleted_by' => $deleterUuid,
            ]);
            
            $category->delete();

            ActivityLog::log([
                'user_uuid' => $deleterUuid,
                'action' => 'delete',
                'action_type' => 'crud',
                'module' => 'faq_categories',
                'description' => "Suppression de la catégorie FAQ : {$category->label}",
                'resource_type' => 'faq_category',
                'resource_id' => $category->uuid_faq_category,
                'level' => 'warning',
            ]);
        });
    }

    /**
     * Récupérer les catégories avec leurs compteurs
     */
    public function getCategoriesWithCount(bool $onlyActive = true): array
    {
        $query = FaqCategory::query();
        
        if ($onlyActive) {
            $query->active();
        }

        return $query->orderBy('order')
            ->get()
            ->map(function ($category) {
                return [
                    'uuid' => $category->uuid_faq_category,
                    'code' => $category->code,
                    'label' => $category->label,
                    'icon' => $category->icon,
                    'color' => $category->color,
                    'description' => $category->description,
                    'count' => $category->activeFaqs()->count(),
                    'is_default' => $category->is_default,
                    'is_active' => $category->is_active,
                ];
            })
            ->toArray();
    }

    /**
     * Récupérer une catégorie par son UUID
     */
    public function getCategoryByUuid(string $uuid): ?FaqCategory
    {
        return FaqCategory::where('uuid_faq_category', $uuid)->first();
    }

    /**
     * Récupérer une catégorie par son code
     */
    public function getCategoryByCode(string $code): ?FaqCategory
    {
        return FaqCategory::where('code', $code)->first();
    }

    /**
     * Vérifier si une catégorie peut être supprimée
     */
    public function canDelete(FaqCategory $category): bool
    {
        // Les catégories par défaut ne peuvent pas être supprimées
        if ($category->isProtected()) {
            return false;
        }

        // Les catégories avec des FAQs ne peuvent pas être supprimées
        if ($category->faqs()->count() > 0) {
            return false;
        }

        return true;
    }

    /**
     * Obtenir les catégories pour le dropdown (select)
     */
    public function getCategoriesForSelect(bool $onlyActive = true): array
    {
        $query = FaqCategory::query();
        
        if ($onlyActive) {
            $query->active();
        }

        return $query->orderBy('order')
            ->get()
            ->map(function ($category) {
                return [
                    'value' => $category->uuid_faq_category,
                    'label' => $category->label,
                    'code' => $category->code,
                ];
            })
            ->toArray();
    }

    /**
     * Réordonner les catégories
     */
    public function reorder(array $uuids): void
    {
        DB::transaction(function () use ($uuids) {
            foreach ($uuids as $index => $uuid) {
                FaqCategory::where('uuid_faq_category', $uuid)->update([
                    'order' => $index + 1
                ]);
            }
        });
    }

    /**
     * Dupliquer une catégorie (créer une copie)
     */
    public function duplicate(FaqCategory $category, string $creatorUuid): FaqCategory
    {
        return DB::transaction(function () use ($category, $creatorUuid) {
            $newLabel = $category->label . ' (copie)';
            $newCode = Str::slug($newLabel, '_');
            
            // Vérifier si le code existe déjà
            $counter = 1;
            while (FaqCategory::where('code', $newCode)->exists()) {
                $newCode = Str::slug($category->label, '_') . '_copy_' . $counter;
                $counter++;
            }

            $newCategory = FaqCategory::create([
                'uuid_faq_category' => (string) Str::uuid(),
                'code' => $newCode,
                'label' => $newLabel,
                'icon' => $category->icon,
                'color' => $category->color,
                'description' => $category->description,
                'order' => FaqCategory::max('order') + 1,
                'is_active' => false,
                'is_default' => false,
                'metadata' => $category->metadata,
                'created_by' => $creatorUuid,
            ]);

            ActivityLog::log([
                'user_uuid' => $creatorUuid,
                'action' => 'duplicate',
                'action_type' => 'crud',
                'module' => 'faq_categories',
                'description' => "Duplication de la catégorie FAQ : {$category->label} vers {$newCategory->label}",
                'resource_type' => 'faq_category',
                'resource_id' => $newCategory->uuid_faq_category,
                'level' => 'info',
            ]);

            return $newCategory;
        });
    }

    /**
     * Compter les catégories actives
     */
    public function countActive(): int
    {
        return FaqCategory::active()->count();
    }

    /**
     * Compter les catégories totales
     */
    public function countTotal(): int
    {
        return FaqCategory::count();
    }

    /**
     * Obtenir les statistiques des catégories
     */
    public function getStats(): array
    {
        return [
            'total' => $this->countTotal(),
            'active' => $this->countActive(),
            'inactive' => $this->countTotal() - $this->countActive(),
            'default' => FaqCategory::default()->count(),
            'custom' => FaqCategory::custom()->count(),
        ];
    }
}