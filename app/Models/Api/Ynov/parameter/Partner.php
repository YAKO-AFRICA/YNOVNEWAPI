<?php
// app/Models/Api/Ynov/parameter/Partner.php
namespace App\Models\Api\Ynov\parameter;

use App\Models\Api\Ynov\parameter\Reseau;
use App\Models\Api\Ynov\parameter\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Partner extends Model
{
    use SoftDeletes;
    
    
    protected $table = 'partners';
    
    protected $fillable = [
        'uuid_partner',
        'code',
        'designation',
        'logo',
        'code_branche',
        'email',
        'telephone',
        'adresse',
        'site_web',
        'config',
        'is_active',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];
    
    protected $casts = [
        'config' => 'array',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(fn (self $model) => $model->uuid_partner ??= (string) Str::uuid());
    }

    public function reseaux(): HasMany
    {
        return $this->hasMany(Reseau::class, 'partner_uuid', 'uuid_partner');
    }

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
}