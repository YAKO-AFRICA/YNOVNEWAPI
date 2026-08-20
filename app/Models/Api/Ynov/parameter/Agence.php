<?php
// app/Models/Api/Ynov/parameter/Agence.php
namespace App\Models\Api\Ynov\parameter;

use App\Models\Api\Ynov\parameter\Reseau;
use App\Models\Api\Ynov\parameter\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Agence extends Model
{
    use SoftDeletes, HasFactory;
    
    
    protected $table = 'agences';
    
    protected $fillable = [
        'uuid_agence',
        'code',
        'libelle',
        'description',
        'reseau_uuid',
        'email',
        'telephone',
        'adresse',
        'config',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];
    
    protected $casts = [
        'config' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(fn (self $model) => $model->uuid_agence ??= (string) Str::uuid());
    }

    public function reseau(): BelongsTo
    {
        return $this->belongsTo(Reseau::class, 'reseau_uuid', 'uuid_reseau');
    }

    /**
     * Relation Many-to-Many avec les utilisateurs
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'user_agences',
            'agence_uuid',
            'user_uuid',
            'uuid_agence',
            'uuid_user'
        )->withPivot([
            'is_primary',
            'is_active',
            'assigned_at',
            'assigned_by',
            'role_uuid',
            'custom_permissions',
            'metadata'
        ])->withTimestamps();
    }
    
    /**
     * Récupérer les utilisateurs actifs de l'agence
     */
    public function activeUsers()
    {
        return $this->users()->wherePivot('is_active', true);
    }
    
    /**
     * Récupérer les utilisateurs principaux de l'agence
     */
    public function primaryUsers()
    {
        return $this->users()->wherePivot('is_primary', true);
    }
    
    /**
     * Scope pour les agences actives
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'actif');
    }
    
    /**
     * Scope pour les agences d'un réseau
     */
    public function scopeInReseau($query, string $reseauUuid)
    {
        return $query->where('reseau_uuid', $reseauUuid);
    }
}