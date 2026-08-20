<?php

namespace App\Models\Api\Ynov\parameter;

use App\Models\Api\Ynov\parameter\UserSecurityAnswer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

// class SecurityQuestion extends Model
// {
//     use SoftDeletes;

//     protected $table = 'security_questions';

//     protected $fillable = [
//         'uuid',
//         'question_text',
//         'category',
//         'is_active',
//         'is_system',
//         'min_answers',
//         'max_answers',
//         'created_by',
//         'updated_by',
//         'deleted_by',
//     ];

//     protected $casts = [
//         'is_active' => 'boolean',
//         'is_system' => 'boolean',
//         'min_answers' => 'integer',
//         'max_answers' => 'integer',
//     ];

//     protected static function booted(): void
//     {
//         static::creating(fn($model) => $model->uuid ??= (string) Str::uuid());
//     }

//     /**
//      * Relation avec les réponses des utilisateurs
//      */
//     public function userAnswers(): HasMany
//     {
//         return $this->hasMany(UserSecurityAnswer::class, 'security_question_uuid', 'uuid');
//     }

//     /**
//      * Scope pour les questions actives
//      */
//     public function scopeActive($query)
//     {
//         return $query->where('is_active', true);
//     }

//     /**
//      * Scope pour les questions système
//      */
//     public function scopeSystem($query)
//     {
//         return $query->where('is_system', true);
//     }

//     /**
//      * Scope pour les questions personnalisées
//      */
//     public function scopeCustom($query)
//     {
//         return $query->where('is_system', false);
//     }

//     /**
//      * Scope par catégorie
//      */
//     public function scopeCategory($query, string $category)
//     {
//         return $query->where('category', $category);
//     }
// }

class SecurityQuestion extends Model
{
    use SoftDeletes;

    protected $table = 'security_questions';

    protected $fillable = [
        'uuid',
        'question_text',
        'category',
        'is_active',
        'is_system',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_system' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(fn($model) => $model->uuid ??= (string) Str::uuid());
    }

    /**
     * Relation avec les réponses des utilisateurs
     */
    public function userAnswers(): HasMany
    {
        return $this->hasMany(UserSecurityAnswer::class, 'security_question_uuid', 'uuid');
    }

    /**
     * Scope pour les questions actives
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope pour les questions système
     */
    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    /**
     * Scope pour les questions personnalisées
     */
    public function scopeCustom($query)
    {
        return $query->where('is_system', false);
    }

    /**
     * Scope par catégorie
     */
    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }
}