<?php
// app/Models/Api/Ynov/parameter/Produit.php

namespace App\Models\Api\Ynov\parameter;

use App\Models\Api\Ynov\parameter\ProduitFormule;
use App\Models\Api\Ynov\parameter\ProduitPrestation;
use App\Models\Api\Ynov\parameter\TypePrestation;
use App\Models\Api\Ynov\parameter\TypeProduit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Produit extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'produits';

    protected $fillable = [
        'uuid_produit',
        'code',
        'libelle',
        'date_creation',
        'code_branche',
        'code_produit_nature',
        'description',
        'statut',
        'code_groupe_assure',
        'code_groupe_profil',
        'age_mini_adh',
        'age_maxi_adh',
        'table_tarification',
        'table_reglementaire',
        'table_fiscale',
        'table_comptable',
        'code_contractant',
        'num_seq',
        'delai_carrence',
        'capital_assure_pmok',
        'capital_assure_vers_excp_ok',
        'code_branche_deux',
        'type_produit_uuid', // represente type_contrat
        'capital',
        'code_produit_court',
        'duree_souscription_annee',
        'duree_souscription_mois',
        'vie_entiere',
        'duree_cotisation_ans',
        'duree_cotisation_mois',
        'code_marque',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'date_creation' => 'datetime',
        'age_mini_adh' => 'integer',
        'age_maxi_adh' => 'integer',
        'delai_carrence' => 'integer',
        'capital_assure_pmok' => 'integer',
        'capital_assure_vers_excp_ok' => 'integer',
        'capital' => 'integer',
        'vie_entiere' => 'boolean',
        'duree_souscription_annee' => 'integer',
        'duree_souscription_mois' => 'integer',
        'duree_cotisation_ans' => 'integer',
        'duree_cotisation_mois' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->uuid_produit ??= (string) Str::uuid();
        });
    }

    /**
     * Relation avec les formules du produit
     */
    public function formules()
    {
        return $this->hasMany(ProduitFormule::class, 'produit_uuid', 'uuid_produit');
    }

    /**
     * Relation avec le type de produit
     */
    public function typeProduit()
    {
        return $this->belongsTo(TypeProduit::class, 'type_produit_uuid', 'uuid_type_produit');
    }

    /**
     * Relation avec les prestations du produit (table pivot)
     */
    public function prestations()
    {
        return $this->hasMany(ProduitPrestation::class, 'produit_uuid', 'uuid_produit');
    }

    /**
     * Relation avec les types de prestations via la table pivot
     */
    public function typePrestations()
    {
        return $this->belongsToMany(
            TypePrestation::class,
            'produit_prestations',
            'produit_uuid',
            'type_prestation_uuid',
            'uuid_produit',
            'uuid_type_prestation'
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
     * Vérifier si le produit est actif
     */
    public function isActive(): bool
    {
        return $this->statut === 'actif';
    }

    /**
     * Vérifier si le produit est inactif
     */
    public function isInactive(): bool
    {
        return $this->statut === 'inactif';
    }

    /**
     * Vérifier si le produit a une durée de vie entière
     */
    public function isVieEntiere(): bool
    {
        return (bool) $this->vie_entiere;
    }

    /**
     * Obtenir la durée totale de souscription en mois
     */
    public function getDureeSouscriptionTotale(): int
    {
        return ($this->duree_souscription_annee ?? 0) * 12 + ($this->duree_souscription_mois ?? 0);
    }

    /**
     * Obtenir la durée totale de cotisation en mois
     */
    public function getDureeCotisationTotale(): int
    {
        return ($this->duree_cotisation_ans ?? 0) * 12 + ($this->duree_cotisation_mois ?? 0);
    }

    /**
     * Scope pour les produits actifs
     */
    public function scopeActive($query)
    {
        return $query->where('statut', 'actif');
    }

    /**
     * Scope pour les produits par type de produit
     */
    public function scopeByTypeProduit($query, string $typeProduitUuid)
    {
        return $query->where('type_produit_uuid', $typeProduitUuid);
    }

    /**
     * Scope pour la recherche textuelle
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('code', 'LIKE', "%{$search}%")
              ->orWhere('libelle', 'LIKE', "%{$search}%")
              ->orWhere('description', 'LIKE', "%{$search}%")
              ->orWhere('code_branche', 'LIKE', "%{$search}%")
              ->orWhere('code_produit_court', 'LIKE', "%{$search}%");
        });
    }

    /**
     * Scope pour les produits avec âge valide
     */
    public function scopeWithValidAge($query, int $age)
    {
        return $query->where(function ($q) use ($age) {
            $q->whereNull('age_mini_adh')
              ->orWhere('age_mini_adh', '<=', $age);
        })->where(function ($q) use ($age) {
            $q->whereNull('age_maxi_adh')
              ->orWhere('age_maxi_adh', '>=', $age);
        });
    }

    /**
     * Scope pour les produits par branche
     */
    public function scopeByBranche($query, string $codeBranche)
    {
        return $query->where('code_branche', $codeBranche);
    }

    /**
     * Scope pour les produits par statut
     */
    public function scopeWithStatus($query, string $statut)
    {
        return $query->where('statut', $statut);
    }

    /**
     * Obtenir le nombre de formules actives
     */
    public function getFormulesActivesCountAttribute(): int
    {
        return $this->formules()->where('est_actif', true)->count();
    }

    /**
     * Obtenir le nombre de prestations actives
     */
    public function getPrestationsActivesCountAttribute(): int
    {
        return $this->prestations()->where('status', 'actif')->count();
    }
}