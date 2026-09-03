<?php
// app/Models/Api/Ynov/parameter/TypePrestation.php

namespace App\Models\Api\Ynov\parameter;

use App\Models\Api\Ynov\parameter\CategoryTypePrestation;
use App\Models\Api\Ynov\parameter\Produit;
// use App\Models\Api\Ynov\parameter\Rdv;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class TypePrestation extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'type_prestations';

    protected $fillable = [
        'uuid_type_prestation',
        'code',
        'libelle',
        'description',
        'category_uuid',
        'impact',
        'delai_traitement',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'delai_traitement' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Impacts disponibles
    public const IMPACT_SORTIE_PORTEFEUILLE = '1';
    public const IMPACT_NON_SORTIE_PORTEFEUILLE = '0';

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->uuid_type_prestation ??= (string) Str::uuid();
        });
    }

    /**
     * Relation avec la catégorie
     */
    public function category()
    {
        return $this->belongsTo(CategoryTypePrestation::class, 'category_uuid', 'uuid_category_type_prestations');
    }

    /**
     * Relation avec les produits via la table pivot
     */
    public function produits()
    {
        return $this->belongsToMany(
            Produit::class,
            'produit_prestations',
            'type_prestation_uuid',
            'produit_uuid',
            'uuid_type_prestation',
            'uuid_produit'
        )->withPivot([
            'uuid_product_prestation',
            'produit_type',
            'status',
            'created_by',
            'updated_by',
            'deleted_by'
        ])->withTimestamps();
    }

    /**
     * Relation avec les rendez-vous
     */
    // public function rdvs()
    // {
    //     return $this->hasMany(Rdv::class, 'motif_rdv', 'uuid_type_prestation');
    // }

    /**
     * Vérifier si le type de prestation est actif
     */
    public function isActive(): bool
    {
        return $this->status === 'actif';
    }

    /**
     * Vérifier si le type de prestation est inactif
     */
    public function isInactive(): bool
    {
        return $this->status === 'inactif';
    }

    /**
     * Vérifier si le type de prestation a un impact sortie portefeuille
     */
    public function hasPortfolioExitImpact(): bool
    {
        return $this->impact === self::IMPACT_SORTIE_PORTEFEUILLE;
    }

    /**
     * Obtenir le libellé de l'impact
     */
    public function getImpactLabel(): string
    {
        return $this->impact === self::IMPACT_SORTIE_PORTEFEUILLE 
            ? 'Sortie portefeuille' 
            : 'Non sortie portefeuille';
    }

    /**
     * Obtenir le nombre de produits associés actifs
     */
    public function getProduitsActifsCountAttribute(): int
    {
        return $this->produits()
            ->wherePivot('status', 'actif')
            ->count();
    }

    /**
     * Scope pour les types actifs
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'actif');
    }

    /**
     * Scope pour les types par catégorie
     */
    public function scopeByCategory($query, string $categoryUuid)
    {
        return $query->where('category_uuid', $categoryUuid);
    }

    /**
     * Scope pour les types avec impact sortie portefeuille
     */
    public function scopeWithPortfolioExit($query)
    {
        return $query->where('impact', self::IMPACT_SORTIE_PORTEFEUILLE);
    }

    /**
     * Scope pour les types sans impact sortie portefeuille
     */
    public function scopeWithoutPortfolioExit($query)
    {
        return $query->where('impact', self::IMPACT_NON_SORTIE_PORTEFEUILLE);
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
     * Scope pour les types avec un délai de traitement
     */
    public function scopeWithDelai($query, ?int $delai = null)
    {
        if ($delai !== null) {
            return $query->where('delai_traitement', $delai);
        }
        return $query->whereNotNull('delai_traitement');
    }

    /**
     * Scope pour les types sans délai de traitement
     */
    public function scopeWithoutDelai($query)
    {
        return $query->whereNull('delai_traitement');
    }
}