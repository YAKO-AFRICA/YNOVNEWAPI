<?php
// app/Models/Api/Ynov/parameter/Partner.php
namespace App\Models\Api\Ynov\parameter;

use App\Models\Api\Ynov\Esouscription\Document;
use App\Models\Api\Ynov\parameter\Reseau;
use App\Models\Api\Ynov\parameter\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;


class Partner extends Model
{
    use SoftDeletes;
    
    protected $table = 'partners';

    protected $primaryKey = 'uuid_partner';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'uuid_partner',
        'code',
        'code_contractant',
        'code_branche',
        'designation',
        'description',
        'logo',
        'is_active',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
        'created_at',
        'updated_at',
        'deleted_at',
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

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'uuid_user');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by', 'uuid_user');
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by', 'uuid_user');
    }
    
    /**
     * Récupérer le logo du partenaire
     */
    public function logoDocuments()
    {
        return $this->hasMany(Document::class, 'reference_uuid', 'uuid_partner')
            ->where('source', 'PARANTER_PARTNER_LOGO')
            ->where('statut', 'actif');
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

    public function getPartnerStatusLabel(): ?string
    {
        return $this->status ? match ($this->status) {
            'actif' => 'Actif',
            'inactif' => 'Inactif',
            'suspendu' => 'Suspendu',
            default => $this->status,
        } : null;
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
     * Scope pour un code contractant spécifique
     */
    public function scopeContractant($query, string $codeContractant)
    {
        return $query->where('code_contractant', $codeContractant);
    }
    
   
    
}