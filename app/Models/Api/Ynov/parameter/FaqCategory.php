<?php
// app/Models/Api/Ynov/parameter/FaqCategory.php
namespace App\Models\Api\Ynov\parameter;

use App\Models\Api\Ynov\parameter\Faq;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class FaqCategory extends Model
{
    use SoftDeletes;

    protected $table = 'faq_categories';

    protected $fillable = [
        'uuid_faq_category',
        'code',
        'label',
        'icon',
        'color',
        'description',
        'order',
        'is_active',
        'is_default',
        'metadata',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'order' => 'integer',
        'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->uuid_faq_category ??= (string) Str::uuid();
            if (empty($model->code)) {
                $model->code = Str::slug($model->label, '_');
            }
        });
    }

    /**
     * Relation avec les FAQs
     */
    public function faqs(): HasMany
    {
        return $this->hasMany(Faq::class, 'faq_category_uuid', 'uuid_faq_category');
    }

    /**
     * Récupérer les FAQs actives de la catégorie
     */
    public function activeFaqs()
    {
        return $this->faqs()->where('is_active', true);
    }

    /**
     * Vérifier si la catégorie est protégée (par défaut)
     */
    public function isProtected(): bool
    {
        return $this->is_default;
    }

    /**
     * Scope pour les catégories actives
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope pour les catégories par défaut
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope pour les catégories personnalisées
     */
    public function scopeCustom($query)
    {
        return $query->where('is_default', false);
    }
}