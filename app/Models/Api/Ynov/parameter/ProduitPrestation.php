<?php
// app/Models/Api/Ynov/parameter/ProduitPrestation.php

namespace App\Models\Api\Ynov\parameter;

use App\Models\Api\Ynov\parameter\Produit;
use App\Models\Api\Ynov\parameter\TypePrestation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ProduitPrestation extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'produit_prestations';

    protected $fillable = [
        'uuid_product_prestation',
        'produit_uuid',
        'produit_type',
        'type_prestation_uuid',
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
            $model->uuid_product_prestation ??= (string) Str::uuid();
        });
    }

    /**
     * Relation avec le produit
     */
    public function produit()
    {
        return $this->belongsTo(Produit::class, 'produit_uuid', 'uuid_produit');
    }

    /**
     * Relation avec le type de prestation
     */
    public function typePrestation()
    {
        return $this->belongsTo(TypePrestation::class, 'type_prestation_uuid', 'uuid_type_prestation');
    }

    /**
     * Vérifier si l'association est active
     */
    public function isActive(): bool
    {
        return $this->status === 'actif';
    }

    /**
     * Vérifier si l'association est inactive
     */
    public function isInactive(): bool
    {
        return $this->status === 'inactif';
    }

    /**
     * Scope pour les associations actives
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'actif');
    }

    /**
     * Scope pour un produit spécifique
     */
    public function scopeForProduit($query, string $produitUuid)
    {
        return $query->where('produit_uuid', $produitUuid);
    }

    /**
     * Scope pour un type de prestation spécifique
     */
    public function scopeForTypePrestation($query, string $typePrestationUuid)
    {
        return $query->where('type_prestation_uuid', $typePrestationUuid);
    }

    /**
     * Scope pour un statut spécifique
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope pour un type de produit spécifique
     */
    public function scopeByProduitType($query, string $produitType)
    {
        return $query->where('produit_type', $produitType);
    }
}