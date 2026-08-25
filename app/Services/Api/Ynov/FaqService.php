<?php
// app/Services/Api/Ynov/FaqService.php
namespace App\Services\Api\Ynov;

use App\Models\Api\Ynov\parameter\Faq;
use App\Models\Api\Ynov\parameter\ActivityLog;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class FaqService
{
    /**
     * Créer une FAQ
     */
    public function create(array $data, string $creatorUuid): Faq
    {
        return DB::transaction(function () use ($data, $creatorUuid) {
            $faq = Faq::create([
                'uuid_faq' => (string) Str::uuid(),
                'faq_category_uuid' => $data['faq_category_uuid'] ?? null,
                'category' => $data['category'] ?? null,
                'category_label' => $data['category_label'] ?? null,
                'question' => $data['question'],
                'answer' => $data['answer'],
                'order' => $data['order'] ?? 0,
                'is_active' => $data['is_active'] ?? true,
                'is_featured' => $data['is_featured'] ?? false,
                'tags' => $data['tags'] ?? null,
                'created_by' => $creatorUuid,
            ]);

            ActivityLog::log([
                'user_uuid' => $creatorUuid,
                'action' => 'create',
                'action_type' => 'crud',
                'module' => 'faqs',
                'description' => "Création de la FAQ : {$faq->question}",
                'resource_type' => 'faq',
                'resource_id' => $faq->uuid_faq,
                'new_values' => $faq->toArray(),
                'level' => 'info',
            ]);

            return $faq;
        });
    }

    /**
     * Mettre à jour une FAQ
     */
    public function update(Faq $faq, array $data, string $updaterUuid): Faq
    {
        return DB::transaction(function () use ($faq, $data, $updaterUuid) {
            $oldValues = $faq->toArray();

            $faq->update([
                'faq_category_uuid' => $data['faq_category_uuid'] ?? $faq->faq_category_uuid,
                'category' => $data['category'] ?? $faq->category,
                'category_label' => $data['category_label'] ?? $faq->category_label,
                'question' => $data['question'] ?? $faq->question,
                'answer' => $data['answer'] ?? $faq->answer,
                'order' => $data['order'] ?? $faq->order,
                'is_active' => $data['is_active'] ?? $faq->is_active,
                'is_featured' => $data['is_featured'] ?? $faq->is_featured,
                'tags' => $data['tags'] ?? $faq->tags,
                'updated_by' => $updaterUuid,
            ]);

            ActivityLog::log([
                'user_uuid' => $updaterUuid,
                'action' => 'update',
                'action_type' => 'crud',
                'module' => 'faqs',
                'description' => "Mise à jour de la FAQ : {$faq->question}",
                'resource_type' => 'faq',
                'resource_id' => $faq->uuid_faq,
                'old_values' => $oldValues,
                'new_values' => $faq->toArray(),
                'level' => 'info',
            ]);

            return $faq->fresh();
        });
    }

    /**
     * Supprimer une FAQ (soft delete)
     */
    public function delete(Faq $faq, string $deleterUuid): void
    {
        $faq->update([
            'is_active' => false,
            'deleted_by' => $deleterUuid,
        ]);
        
        $faq->delete();

        ActivityLog::log([
            'user_uuid' => $deleterUuid,
            'action' => 'delete',
            'action_type' => 'crud',
            'module' => 'faqs',
            'description' => "Suppression de la FAQ : {$faq->question}",
            'resource_type' => 'faq',
            'resource_id' => $faq->uuid_faq,
            'level' => 'warning',
        ]);
    }

    /**
     * Récupérer les FAQs avec filtres
     */
    public function getFaqs(array $filters = [], int $perPage = 20)
    {
        $query = Faq::query()->with('faqCategory');

        if (isset($filters['faq_category_uuid'])) {
            $query->inCategory($filters['faq_category_uuid']);
        }

        if (isset($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (isset($filters['is_featured'])) {
            $query->where('is_featured', $filters['is_featured']);
        }

        if (isset($filters['search'])) {
            $query->search($filters['search']);
        }

        return $query->orderBy('order')->orderBy('created_at')->paginate($perPage);
    }

    /**
     * Incrémenter le compteur de vues d'une FAQ
     */
    public function incrementViews(Faq $faq): Faq
    {
        $faq->incrementViews();
        return $faq;
    }
}