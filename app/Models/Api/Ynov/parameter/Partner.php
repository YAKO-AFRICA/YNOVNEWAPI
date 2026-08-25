<?php
// app/Models/Api/Ynov/parameter/Partner.php
namespace App\Models\Api\Ynov\parameter;

use App\Models\Api\Ynov\parameter\Reseau;
use App\Models\Api\Ynov\parameter\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

// class Partner extends Model
// {
//     use SoftDeletes;
    
    
//     protected $table = 'partners';
    
//     protected $fillable = [
//         'uuid_partner',
//         'code',
//         'designation',
//         'logo',
//         'code_branche',
//         'email',
//         'telephone',
//         'adresse',
//         'site_web',
//         'config',
//         'is_active',
//         'status',
//         'created_by',
//         'updated_by',
//         'deleted_by',
//     ];
    
//     protected $casts = [
//         'config' => 'array',
//         'is_active' => 'boolean',
//     ];

//     protected static function booted(): void
//     {
//         static::creating(fn (self $model) => $model->uuid_partner ??= (string) Str::uuid());
//     }

//     public function reseaux(): HasMany
//     {
//         return $this->hasMany(Reseau::class, 'partner_uuid', 'uuid_partner');
//     }

//     public function users(): HasMany
//     {
//         return $this->hasMany(User::class, 'partner_uuid', 'uuid_partner');
//     }
    
//     /**
//      * Récupérer les réseaux actifs du partenaire
//      */
//     public function activeReseaux()
//     {
//         return $this->reseaux()->where('status', 'actif');
//     }
    
//     /**
//      * Récupérer les utilisateurs actifs du partenaire
//      */
//     public function activeUsers()
//     {
//         return $this->users()->where('status', 'actif');
//     }
    
//     /**
//      * Vérifier si le partenaire est actif
//      */
//     public function isActive(): bool
//     {
//         return $this->is_active && $this->status === 'actif';
//     }
    
//     /**
//      * Scope pour les partenaires actifs
//      */
//     public function scopeActive($query)
//     {
//         return $query->where('is_active', true)->where('status', 'actif');
//     }
    
//     /**
//      * Scope pour un code branche spécifique
//      */
//     public function scopeBranche($query, string $codeBranche)
//     {
//         return $query->where('code_branche', $codeBranche);
//     }
// }

class Partner extends Model
{
    use SoftDeletes;
    
    protected $table = 'partners';
    
    protected $fillable = [
        'uuid_partner',
        'code',
        'designation',
        'sigle',
        'description',
        'logo',
        'code_branche',
        'email',
        'email_2',
        'telephone',
        'telephone_2',
        'adresse',
        'ville',
        'pays',
        'site_web',
        'latitude',
        'longitude',
        'type',
        'secteur_activite',
        'categorie',
        'config',
        'metadata',
        'is_active',
        'status',
        'date_agrement',
        'date_expiration',
        'created_by',
        'updated_by',
        'deleted_by',
    ];
    
    protected $casts = [
        'config' => 'array',
        'metadata' => 'array',
        'is_active' => 'boolean',
        'date_agrement' => 'date',
        'date_expiration' => 'date',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    protected static function booted(): void
    {
        static::creating(fn (self $model) => $model->uuid_partner ??= (string) Str::uuid());
    }

    /**
     * Relation avec les réseaux
     */
    public function reseaux(): HasMany
    {
        return $this->hasMany(Reseau::class, 'partner_uuid', 'uuid_partner');
    }

    /**
     * Relation avec les utilisateurs
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'partner_uuid', 'uuid_partner');
    }
    
    /**
     * Récupérer les réseaux actifs du partenaire
     */
    public function activeReseaux()
    {
        return $this->reseaux()->where('status', 'actif');
    }
    
    /**
     * Récupérer les utilisateurs actifs du partenaire
     */
    public function activeUsers()
    {
        return $this->users()->where('status', 'actif');
    }
    
    /**
     * Vérifier si le partenaire est actif
     */
    public function isActive(): bool
    {
        return $this->is_active && $this->status === 'actif';
    }
    
    /**
     * Vérifier si le partenaire est expiré
     */
    public function isExpired(): bool
    {
        return $this->date_expiration && $this->date_expiration->isPast();
    }
    
    /**
     * Obtenir l'adresse complète
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->adresse,
            $this->ville,
            $this->pays,
        ]);
        
        return implode(', ', $parts);
    }
    
    /**
     * Scope pour les partenaires actifs
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where('status', 'actif');
    }
    
    /**
     * Scope pour un code branche spécifique
     */
    public function scopeBranche($query, string $codeBranche)
    {
        return $query->where('code_branche', $codeBranche);
    }
    
    /**
     * Scope pour un type spécifique
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }
    
    /**
     * Scope pour une catégorie spécifique
     */
    public function scopeCategorie($query, string $categorie)
    {
        return $query->where('categorie', $categorie);
    }
    
    /**
     * Scope pour les partenaires non expirés
     */
    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('date_expiration')
              ->orWhere('date_expiration', '>=', now());
        });
    }
}