<?php
// app/Models/Api/Ynov/parameter/Permission.php
namespace App\Models\Api\Ynov\parameter;

use App\Models\Api\Ynov\parameter\PermissionGroup;
use App\Models\Api\Ynov\parameter\Role;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Permission extends Model
{
    use SoftDeletes;
    protected $table = 'permissions';
    
    protected $fillable = [
        'uuid_permission',
        'permission_group_uuid',
        'code',
        'action',
        'libelle',
        'description',
        'category',
        'is_guard',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];
    
    protected $casts = [
        'is_guard' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $permission) {
            $permission->uuid_permission ??= (string) Str::uuid();
            
            if (empty($permission->code) && $permission->permission_group_uuid && $permission->action) {
                $group = PermissionGroup::where('uuid_permission_group', $permission->permission_group_uuid)->firstOrFail();
                $permission->code = $group->code . '.' . Str::slug($permission->action, '_');
            }
            
            if (empty($permission->libelle) && $permission->permission_group_uuid) {
                $group = PermissionGroup::where('uuid_permission_group', $permission->permission_group_uuid)->firstOrFail();
                $permission->libelle = $permission->action . ' - ' . $group->libelle;
            }
        });
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(PermissionGroup::class, 'permission_group_uuid', 'uuid_permission_group');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            Role::class,
            'role_permissions',
            'permission_uuid',
            'role_uuid',
            'uuid_permission',
            'uuid_role'
        )->withPivot([
            'uuid_role_permission',
            'granted_by',
            'granted_at',
            'expires_at',
            'metadata'
        ])->withTimestamps();
    }
    
    /**
     * Scope pour les permissions actives
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'actif');
    }
    
    /**
     * Scope pour les permissions de garde
     */
    public function scopeGuard($query)
    {
        return $query->where('is_guard', true);
    }
    
    /**
     * Scope pour une catégorie spécifique
     */
    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }
}