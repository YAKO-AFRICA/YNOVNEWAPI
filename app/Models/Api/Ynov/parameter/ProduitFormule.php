<?php
// app/Models/Api/Ynov/parameter/ProduitFormule.php

namespace App\Models\Api\Ynov\parameter;

use App\Models\Api\Ynov\parameter\Produit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ProduitFormule extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'produit_formules';

    protected $fillable = [
        'uuid_produit_formule',
        'produit_uuid',
        'code_produit_formule',
        'code_produit',
        'libelle',
        'date_creation',
        'date_debut',
        'date_fin',
        'est_actif',
        'code_plan_com',
        'code_contractant',
        'code_groupe_profil',
        'code_groupe_assure',
        'fa',
        'fg',
        'tx',
        'code_canal_distribution',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'date_creation' => 'datetime',
        'date_debut' => 'datetime',
        'date_fin' => 'datetime',
        'est_actif' => 'boolean',
        'fa' => 'float',
        'fg' => 'float',
        'tx' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->uuid_produit_formule ??= (string) Str::uuid();
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
     * Vérifier si la formule est active
     */
    public function isActive(): bool
    {
        return (bool) $this->est_actif;
    }

    /**
     * Scope pour les formules actives
     */
    public function scopeActive($query)
    {
        return $query->where('est_actif', true);
    }

    /**
     * Scope pour les formules par produit
     */
    public function scopeForProduit($query, string $produitUuid)
    {
        return $query->where('produit_uuid', $produitUuid);
    }

    /**
     * Scope pour la recherche textuelle
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('code_produit_formule', 'LIKE', "%{$search}%")
              ->orWhere('libelle', 'LIKE', "%{$search}%")
              ->orWhere('code_produit', 'LIKE', "%{$search}%");
        });
    }

    /**
     * Scope pour les formules par canal de distribution
     */
    public function scopeByCanalDistribution($query, string $canal)
    {
        return $query->where('code_canal_distribution', $canal);
    }
}