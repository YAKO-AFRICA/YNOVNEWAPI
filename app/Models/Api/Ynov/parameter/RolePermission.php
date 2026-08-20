<?php
// app/Models/Api/Ynov/parameter/RolePermission.php
namespace App\Models\Api\Ynov\parameter;

use App\Models\Api\Ynov\parameter\Permission;
use App\Models\Api\Ynov\parameter\Role;
use App\Models\Api\Ynov\parameter\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class RolePermission extends Model
{
    
    protected $table = 'role_permissions';

    protected $fillable = [
        'uuid_role_permission',
        'role_uuid',
        'permission_uuid',
        'granted_by',
        'granted_at',
        'expires_at',
        'metadata',
    ];

    protected $casts = [
        'granted_at' => 'datetime',
        'expires_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function ($pivot) {
            $pivot->uuid_role_permission ??= (string) Str::uuid();
        });
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_uuid', 'uuid_role');
    }

    public function permission(): BelongsTo
    {
        return $this->belongsTo(Permission::class, 'permission_uuid', 'uuid_permission');
    }

    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by', 'uuid_user');
    }
    
    /**
     * Vérifier si la permission est toujours valide
     */
    public function isValid(): bool
    {
        return !$this->expires_at || $this->expires_at->isFuture();
    }
    
    /**
     * Vérifier si la permission est expirée
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
    
    /**
     * Scope pour les permissions valides
     */
    public function scopeValid($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }
    
    /**
     * Scope pour les permissions expirées
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }
}