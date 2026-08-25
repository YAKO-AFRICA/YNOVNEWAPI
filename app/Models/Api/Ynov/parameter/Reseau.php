<?php
// app/Models/Api/Ynov/parameter/Reseau.php
namespace App\Models\Api\Ynov\parameter;

use App\Models\Api\Ynov\parameter\Agence;
use App\Models\Api\Ynov\parameter\Partner;
use App\Models\Api\Ynov\parameter\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

// class Reseau extends Model
// {
//     use SoftDeletes;
    
    
//     protected $table = 'reseaux';
    
//     protected $fillable = [
//         'uuid_reseau',
//         'code',
//         'libelle',
//         'description',
//         'partner_uuid',
//         'config',
//         'status',
//         'created_by',
//         'updated_by',
//         'deleted_by',
//     ];
    
//     protected $casts = [
//         'config' => 'array',
//     ];

//     protected static function booted(): void
//     {
//         static::creating(fn (self $model) => $model->uuid_reseau ??= (string) Str::uuid());
//     }

//     public function partner(): BelongsTo
//     {
//         return $this->belongsTo(Partner::class, 'partner_uuid', 'uuid_partner');
//     }

//     public function agences(): HasMany
//     {
//         return $this->hasMany(Agence::class, 'reseau_uuid', 'uuid_reseau');
//     }

//     public function users(): HasMany
//     {
//         return $this->hasMany(User::class, 'reseau_uuid', 'uuid_reseau');
//     }
    
//     /**
//      * Récupérer les agences actives du réseau
//      */
//     public function activeAgences()
//     {
//         return $this->agences()->where('status', 'actif');
//     }
    
//     /**
//      * Récupérer les utilisateurs actifs du réseau
//      */
//     public function activeUsers()
//     {
//         return $this->users()->where('status', 'actif');
//     }
    
//     /**
//      * Vérifier si le réseau est actif
//      */
//     public function isActive(): bool
//     {
//         return $this->status === 'actif';
//     }
    
//     /**
//      * Scope pour les réseaux actifs
//      */
//     public function scopeActive($query)
//     {
//         return $query->where('status', 'actif');
//     }
    
//     /**
//      * Scope pour un partenaire spécifique
//      */
//     public function scopeForPartner($query, string $partnerUuid)
//     {
//         return $query->where('partner_uuid', $partnerUuid);
//     }
// }

class Reseau extends Model
{
    use SoftDeletes;
    
    protected $table = 'reseaux';
    
    protected $fillable = [
        'uuid_reseau',
        'code',
        'libelle',
        'description',
        'partner_uuid',
        'email',
        'telephone',
        'config',
        'metadata',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];
    
    protected $casts = [
        'config' => 'array',
        'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(fn (self $model) => $model->uuid_reseau ??= (string) Str::uuid());
    }

    /**
     * Relation avec le partenaire
     */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'partner_uuid', 'uuid_partner');
    }

    /**
     * Relation avec les agences
     */
    public function agences(): HasMany
    {
        return $this->hasMany(Agence::class, 'reseau_uuid', 'uuid_reseau');
    }

    /**
     * Relation avec les utilisateurs
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'reseau_uuid', 'uuid_reseau');
    }
    
    /**
     * Récupérer les agences actives du réseau
     */
    public function activeAgences()
    {
        return $this->agences()->where('status', 'actif');
    }
    
    /**
     * Récupérer les utilisateurs actifs du réseau
     */
    public function activeUsers()
    {
        return $this->users()->where('status', 'actif');
    }
    
    /**
     * Vérifier si le réseau est actif
     */
    public function isActive(): bool
    {
        return $this->status === 'actif';
    }
    
    /**
     * Obtenir le nombre d'agences
     */
    public function getAgencesCountAttribute(): int
    {
        return $this->agences()->count();
    }
    
    /**
     * Obtenir le nombre d'utilisateurs
     */
    public function getUsersCountAttribute(): int
    {
        return $this->users()->count();
    }
    
    /**
     * Scope pour les réseaux actifs
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'actif');
    }
    
    /**
     * Scope pour un partenaire spécifique
     */
    public function scopeForPartner($query, string $partnerUuid)
    {
        return $query->where('partner_uuid', $partnerUuid);
    }
    
    /**
     * Scope pour une recherche textuelle
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('libelle', 'LIKE', "%{$search}%")
              ->orWhere('code', 'LIKE', "%{$search}%")
              ->orWhere('description', 'LIKE', "%{$search}%");
        });
    }
}