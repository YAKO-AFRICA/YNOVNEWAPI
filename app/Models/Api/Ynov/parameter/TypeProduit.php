<?php
// app/Models/Api/Ynov/parameter/TypeProduit.php

namespace App\Models\Api\Ynov\parameter;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class TypeProduit extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'type_produits';

    protected $fillable = [
        'uuid_type_produit',
        'code',
        'libelle',
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
            $model->uuid_type_produit ??= (string) Str::uuid();
        });
    }

    /**
     * Relation avec les produits
     */
    public function produits()
    {
        return $this->hasMany(Produit::class, 'type_produit_uuid', 'uuid_type_produit');
    }

    /**
     * Vérifier si le type de produit est actif
     */
    public function isActive(): bool
    {
        return $this->status === 'actif';
    }

    /**
     * Scope pour la recherche textuelle
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('code', 'LIKE', "%{$search}%")
              ->orWhere('libelle', 'LIKE', "%{$search}%");
        });
    }

    /**
     * Obtenir le nombre de produits associés
     */
    public function getProduitsCountAttribute(): int
    {
        return $this->produits()->count();
    }
}