<?php
// app/Models/Api/Ynov/parameter/Role.php

namespace App\Models\Api\Ynov\parameter;

use App\Models\Api\Ynov\parameter\Permission;
use App\Models\Api\Ynov\parameter\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class Role extends Model
{
    use SoftDeletes;
    
    
    protected $table = 'roles';
    
    protected $fillable = [
        'uuid_role',
        'code',
        'libelle',
        'description',
        'is_system',
        'is_super_admin',
        'is_default',
        'level',
        'priority',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];
    
    protected $casts = [
        'is_system' => 'boolean',
        'is_super_admin' => 'boolean',
        'is_default' => 'boolean',
        'level' => 'integer',
        'priority' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $role) {
            $role->uuid_role ??= (string) Str::uuid();
            $role->code ??= Str::slug($role->libelle, '_');
        });

        static::updating(function (self $role) {
            if ($role->getOriginal('is_system') && $role->isDirty(['code', 'is_super_admin', 'is_system'])) {
                throw new \RuntimeException('Rôle système protégé : modification interdite sur ces champs.');
            }
        });

        static::deleting(function (self $role) {
            if ($role->is_system) {
                throw new \RuntimeException('Rôle système protégé : suppression interdite.');
            }
        });

        // Invalider le cache des permissions quand un rôle est modifié
        static::saved(function ($role) {
            $role->invalidateCache();
        });

        static::deleted(function ($role) {
            $role->invalidateCache();
        });
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            Permission::class,
            'role_permissions',
            'role_uuid',
            'permission_uuid',
            'uuid_role',
            'uuid_permission'
        )->withPivot([
            'uuid_role_permission',
            'granted_by',
            'granted_at',
            'expires_at',
            'metadata'
        ])->withTimestamps();
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'role_uuid', 'uuid_role');
    }

    /**
     * Vérifier si le rôle a une permission
     */
    public function hasPermission(string $permissionCode): bool
    {
        if ($this->is_super_admin) {
            return true;
        }
        return $this->permissions()->where('code', $permissionCode)->exists();
    }
    
    /**
     * Vérifier si le rôle a toutes les permissions
     */
    public function hasAllPermissions(array $permissionCodes): bool
    {
        if ($this->is_super_admin) {
            return true;
        }
        $count = $this->permissions()->whereIn('code', $permissionCodes)->count();
        return $count === count($permissionCodes);
    }
    
    /**
     * Vérifier si le rôle a une des permissions
     */
    public function hasAnyPermission(array $permissionCodes): bool
    {
        if ($this->is_super_admin) {
            return true;
        }
        return $this->permissions()->whereIn('code', $permissionCodes)->exists();
    }
    
    /**
     * Invalider le cache des permissions pour tous les utilisateurs ayant ce rôle
     */
    public function invalidateCache(): void
    {
        $users = $this->users()->get();
        foreach ($users as $user) {
            Cache::forget("user_permissions_{$user->uuid_user}");
        }
    }
    
    /**
     * Scope pour les rôles actifs
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'actif');
    }
    
    /**
     * Scope pour les rôles non système
     */
    public function scopeNotSystem($query)
    {
        return $query->where('is_system', false);
    }
}

// namespace App\Models\Api\Ynov\parameter;

// use App\Models\Api\Ynov\parameter\Permission;
// use App\Models\Api\Ynov\parameter\User;
// use App\Services\Api\Ynov\CacheService;
// use Illuminate\Database\Eloquent\Model;
// use Illuminate\Database\Eloquent\Relations\BelongsToMany;
// use Illuminate\Database\Eloquent\Relations\HasMany;
// use Illuminate\Database\Eloquent\SoftDeletes;
// use Illuminate\Support\Str;

// class Role extends Model
// {
//     use SoftDeletes;

//     protected $table = 'roles';

//     protected $fillable = [
//         'uuid_role',
//         'code',
//         'libelle',
//         'description',
//         'is_system',
//         'is_super_admin',
//         'is_default',
//         'level',
//         'priority',
//         'status',
//         'created_by',
//         'updated_by',
//         'deleted_by',
//     ];

//     protected $casts = [
//         'is_system' => 'boolean',
//         'is_super_admin' => 'boolean',
//         'is_default' => 'boolean',
//         'level' => 'integer',
//         'priority' => 'integer',
//     ];

//     protected static function booted(): void
//     {
//         static::creating(function (self $role) {
//             $role->uuid_role ??= (string) Str::uuid();
//             $role->code ??= Str::slug($role->libelle, '_');
//         });

//         static::updating(function (self $role) {
//             if ($role->getOriginal('is_system') && $role->isDirty(['code', 'is_super_admin', 'is_system'])) {
//                 throw new \RuntimeException('Rôle système protégé : modification interdite sur ces champs.');
//             }
//         });

//         static::deleting(function (self $role) {
//             if ($role->is_system) {
//                 throw new \RuntimeException('Rôle système protégé : suppression interdite.');
//             }
//         });

//         static::saved(function ($role) {
//             $role->invalidateCache();
//         });

//         static::deleted(function ($role) {
//             $role->invalidateCache();
//         });
//     }

//     /**
//      * ================================================================
//      * CORRECTION #11 : Filtrage des permissions expirées
//      * ================================================================
//      * La relation permissions() filtre maintenant les permissions
//      * dont la date d'expiration est dépassée.
//      */
//     public function permissions(): BelongsToMany
//     {
//         return $this->belongsToMany(
//             Permission::class,
//             'role_permissions',
//             'role_uuid',
//             'permission_uuid',
//             'uuid_role',
//             'uuid_permission'
//         )->withPivot([
//             'uuid_role_permission',
//             'granted_by',
//             'granted_at',
//             'expires_at',
//             'metadata'
//         ])->withTimestamps();
//             // // ================================================================
//             // // FILTRE D'EXPIRATION : Seules les permissions non expirées sont chargées
//             // // ================================================================
//             // $query->whereNull('expires_at')
//             //       ->orWhere('expires_at', '>', now());

//         //     'uuid_role_permission',
//         //     'granted_by',
//         //     'granted_at',
//         //     'expires_at',
//         //     'metadata'
//         // })
//         // ->withTimestamps();
//     }

//     public function users(): HasMany
//     {
//         return $this->hasMany(User::class, 'role_uuid', 'uuid_role');
//     }

//     /**
//      * Vérifier si le rôle a une permission
//      * ================================================================
//      * CORRECTION #11 : Filtrage des permissions expirées dans la vérification
//      * ================================================================
//      */
//     public function hasPermission(string $permissionCode): bool
//     {
//         if ($this->is_super_admin) {
//             return true;
//         }

//         return $this->permissions()
//             ->where('code', $permissionCode)
//             ->exists();
//     }

//     /**
//      * Vérifier si le rôle a toutes les permissions
//      */
//     public function hasAllPermissions(array $permissionCodes): bool
//     {
//         if ($this->is_super_admin) {
//             return true;
//         }

//         $count = $this->permissions()
//             ->whereIn('code', $permissionCodes)
//             ->count();

//         return $count === count($permissionCodes);
//     }

//     /**
//      * Vérifier si le rôle a une des permissions
//      */
//     public function hasAnyPermission(array $permissionCodes): bool
//     {
//         if ($this->is_super_admin) {
//             return true;
//         }

//         return $this->permissions()
//             ->whereIn('code', $permissionCodes)
//             ->exists();
//     }

//     /**
//      * Invalider le cache des permissions pour tous les utilisateurs ayant ce rôle
//      */
//     public function invalidateCache(): void
//     {
//         // $users = $this->users()->get();
//         // foreach ($users as $user) {
//         //     Cache::forget("user_permissions_{$user->uuid_user}");
//         // }

//         app(CacheService::class)->invalidateRolePermissions($this);
//     }

//     /**
//      * Scope pour les rôles actifs
//      */
//     public function scopeActive($query)
//     {
//         return $query->where('status', 'actif');
//     }

//     /**
//      * Scope pour les rôles non système
//      */
//     public function scopeNotSystem($query)
//     {
//         return $query->where('is_system', false);
//     }
// }