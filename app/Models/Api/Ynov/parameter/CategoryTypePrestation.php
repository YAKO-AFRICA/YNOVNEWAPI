<?php
// app/Models/Api/Ynov/parameter/CategoryTypePrestation.php

namespace App\Models\Api\Ynov\parameter;

use App\Models\Api\Ynov\parameter\TypePrestation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class CategoryTypePrestation extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'category_type_prestations';

    protected $fillable = [
        'uuid_category_type_prestations',
        'code',
        'libelle',
        'description',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->uuid_category_type_prestations ??= (string) Str::uuid();
        });
    }

    /**
     * Relation avec les types de prestations
     */
    public function typePrestations()
    {
        return $this->hasMany(TypePrestation::class, 'category_uuid', 'uuid_category_type_prestations');
    }

    /**
     * Vérifier si la catégorie est active
     */
    public function isActive(): bool
    {
        return $this->status === 'actif';
    }

    /**
     * Vérifier si la catégorie est inactive
     */
    public function isInactive(): bool
    {
        return $this->status === 'inactif';
    }

    /**
     * Scope pour les catégories actives
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'actif');
    }

    /**
     * Scope pour la recherche textuelle
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('code', 'LIKE', "%{$search}%")
              ->orWhere('libelle', 'LIKE', "%{$search}%")
              ->orWhere('description', 'LIKE', "%{$search}%");
        });
    }

    /**
     * Scope pour filtrer par statut
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Obtenir le nombre de types de prestations associés
     */
    public function getTypesCountAttribute(): int
    {
        return $this->typePrestations()->count();
    }
}