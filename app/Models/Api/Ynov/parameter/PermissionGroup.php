<?php
// app/Models/Api/Ynov/parameter/PermissionGroup.php
namespace App\Models\Api\Ynov\parameter;

use App\Models\Api\Ynov\parameter\Permission;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class PermissionGroup extends Model
{
    use SoftDeletes;
    
    
    protected $table = 'permission_groups';
    
    protected $fillable = [
        'uuid_permission_group',
        'code',
        'libelle',
        'description',
        'icone',
        'color',
        'ordre_affichage',
        'parent_uuid',
        'route_prefix',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $group) {
            $group->uuid_permission_group ??= (string) Str::uuid();
            $group->code ??= Str::slug($group->libelle, '_');
        });
    }

    public function permissions(): HasMany
    {
        return $this->hasMany(Permission::class, 'permission_group_uuid', 'uuid_permission_group');
    }
    
    /**
     * Relation avec le groupe parent
     */
    public function parent()
    {
        return $this->belongsTo(PermissionGroup::class, 'parent_uuid', 'uuid_permission_group');
    }
    
    /**
     * Relation avec les groupes enfants
     */
    public function children()
    {
        return $this->hasMany(PermissionGroup::class, 'parent_uuid', 'uuid_permission_group');
    }
    
    /**
     * Scope pour les groupes actifs
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'actif');
    }
    
    /**
     * Scope pour les groupes racines
     */
    public function scopeRoots($query)
    {
        return $query->whereNull('parent_uuid');
    }
}