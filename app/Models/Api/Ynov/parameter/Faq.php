<?php
// app/Models/Api/Ynov/parameter/Faq.php
namespace App\Models\Api\Ynov\parameter;

use App\Models\Api\Ynov\parameter\FaqCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Faq extends Model
{
    use SoftDeletes;

    protected $table = 'faqs';

    protected $fillable = [
        'uuid_faq',
        'faq_category_uuid',
        'category',
        'category_label',
        'question',
        'answer',
        'order',
        'is_active',
        'is_featured',
        'tags',
        'views',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'tags' => 'array',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'order' => 'integer',
        'views' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(fn (self $model) => $model->uuid_faq ??= (string) Str::uuid());
        
        static::creating(function (self $model) {
            if (empty($model->category) && $model->faqCategory) {
                $model->category = $model->faqCategory->code;
                $model->category_label = $model->faqCategory->label;
            }
        });
    }

    /**
     * Relation avec la catégorie
     */
    public function faqCategory(): BelongsTo
    {
        return $this->belongsTo(FaqCategory::class, 'faq_category_uuid', 'uuid_faq_category');
    }

    /**
     * Incrémenter le compteur de vues
     */
    public function incrementViews(): self
    {
        $this->increment('views');
        return $this;
    }

    /**
     * Scope pour les FAQs actives
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope pour les FAQs en vedette
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope par catégorie (UUID)
     */
    public function scopeInCategory($query, string $categoryUuid)
    {
        return $query->where('faq_category_uuid', $categoryUuid);
    }

    /**
     * Scope pour la recherche
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('question', 'LIKE', "%{$search}%")
              ->orWhere('answer', 'LIKE', "%{$search}%")
              ->orWhereJsonContains('tags', $search);
        });
    }
}