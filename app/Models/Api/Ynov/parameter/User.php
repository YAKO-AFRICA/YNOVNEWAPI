<?php
// app/Models/Api/Ynov/parameter/User.php

namespace App\Models\Api\Ynov\parameter;

use App\Models\Api\Ynov\parameter\AccountFreeze;
use App\Models\Api\Ynov\parameter\ActivityLog;
use App\Models\Api\Ynov\parameter\Agence;
use App\Models\Api\Ynov\parameter\GroupNotif;
use App\Models\Api\Ynov\parameter\LoginAttempt;
use App\Models\Api\Ynov\parameter\OtpCode;
use App\Models\Api\Ynov\parameter\Partner;
use App\Models\Api\Ynov\parameter\PasswordHistory;
use App\Models\Api\Ynov\parameter\Reseau;
use App\Models\Api\Ynov\parameter\Role;
use App\Models\Api\Ynov\parameter\TwoFactorRecoveryCode;
use App\Models\Api\Ynov\parameter\UserDetails;
use App\Models\Api\Ynov\parameter\UserDevice;
use App\Models\Api\Ynov\UserContrat;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;


    protected $table = 'users';

    protected $fillable = [
        'uuid_user',
        'login',
        'email',
        'password',
        'role_uuid',
        'user_type',
        'partner_uuid',
        'reseau_uuid',
        'status',
        'is_first_login',
        'is_online',
        'is_locked',
        'password_changed_at',
        'password_expires_at',
        'last_login_at',
        'last_activity_at',
        'email_verified_at',
        'failed_login_count',
        'freeze_level',
        'frozen_until',
        'freeze_count',
        'blocked_reason',
        'blocked_by',
        'blocked_at',
        'two_factor_secret',
        'two_factor_enabled',
        'two_factor_recovery_codes',
        'preferences',
        'metadata',
        'remember_token',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password_changed_at' => 'datetime',
        'password_expires_at' => 'datetime',
        'last_login_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'frozen_until' => 'datetime',
        'blocked_at' => 'datetime',
        'password' => 'hashed',
        'is_first_login' => 'boolean',
        'is_online' => 'boolean',
        'is_locked' => 'boolean',
        'two_factor_enabled' => 'boolean',
        'preferences' => 'array',
        'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(fn(self $user) => $user->uuid_user ??= (string) Str::uuid());

        static::creating(function (self $user) {
            if (empty($user->password_expires_at)) {
                $user->password_expires_at = now()->addDays(90);
            }
        });

        // Invalider le cache des permissions quand l'utilisateur est mis à jour
        static::saved(function ($user) {
            $user->invalidatePermissionCache();
        });

        static::deleted(function ($user) {
            $user->invalidatePermissionCache();
        });
    }

    // Relations principales
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_uuid', 'uuid_role');
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'partner_uuid', 'uuid_partner');
    }

    public function reseau(): BelongsTo
    {
        return $this->belongsTo(Reseau::class, 'reseau_uuid', 'uuid_reseau');
    }

    // Relations Many-to-Many
    public function agences(): BelongsToMany
    {
        return $this->belongsToMany(
            Agence::class,
            'user_agences',
            'user_uuid',
            'agence_uuid',
            'uuid_user',
            'uuid_agence'
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

    public function userContrats(): HasMany
    {
        return $this->hasMany(UserContrat::class, 'user_uuid', 'uuid_user');
    }

    public function groupNotifs(): BelongsToMany
    {
        return $this->belongsToMany(
            GroupNotif::class,
            'user_group_notifs',
            'user_uuid',
            'group_notif_uuid',
            'uuid_user',
            'uuid_group_notif'
        )->withPivot([
            'is_primary',
            'is_active',
            'assigned_at',
            'assigned_by',
            'metadata'
        ])->withTimestamps();
    }

    // Relations One-to-Many
    public function details(): HasOne
    {
        return $this->hasOne(UserDetails::class, 'user_uuid', 'uuid_user');
    }

    public function blockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'blocked_by', 'uuid_user');
    }

    public function loginAttempts(): HasMany
    {
        return $this->hasMany(LoginAttempt::class, 'user_uuid', 'uuid_user');
    }

    public function accountFreezes(): HasMany
    {
        return $this->hasMany(AccountFreeze::class, 'user_uuid', 'uuid_user');
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class, 'user_uuid', 'uuid_user');
    }

    public function passwordHistories(): HasMany
    {
        return $this->hasMany(PasswordHistory::class, 'user_uuid', 'uuid_user');
    }

    public function devices(): HasMany
    {
        return $this->hasMany(UserDevice::class, 'user_uuid', 'uuid_user');
    }

    public function otpCodes(): HasMany
    {
        return $this->hasMany(OtpCode::class, 'user_uuid', 'uuid_user');
    }

    public function twoFactorRecoveryCodes(): HasMany
    {
        return $this->hasMany(TwoFactorRecoveryCode::class, 'user_uuid', 'uuid_user');
    }

    // Fonctions utilitaires
    public function hasPermission(string $permissionCode): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $permissions = $this->getPermissions();
        return in_array($permissionCode, $permissions);
    }

    /**
     * Obtenir les permissions de l'utilisateur
     */
    public function getPermissions(): array
    {
        $cacheKey = "user_permissions_{$this->uuid_user}";

        return Cache::remember($cacheKey, 3600, function () {
            if (!$this->role) {
                return [];
            }

            return $this->role->permissions()
                ->where('status', 'actif')
                ->pluck('code')
                ->toArray();
        });
    }

    /**
     * Invalider le cache des permissions
     */
    public function invalidatePermissionCache(): void
    {
        Cache::forget("user_permissions_{$this->uuid_user}");
    }

    /**
     * Rafraîchir les permissions de l'utilisateur
     */
    public function refreshPermissions(): array
    {
        $this->invalidatePermissionCache();
        return $this->getPermissions();
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

    public function getGroupedPermissions(): array
    {
        if (!$this->role) {
            return [];
        }

        if ($this->role->is_super_admin) {
            return ['**'];
        }

        return $this->role->permissions()
            ->where('status', 'actif')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->with('group')
            ->get()
            ->groupBy('group.libelle')
            ->map(function ($permissions) {
                return $permissions->pluck('code')->toArray();
            })
            ->toArray();
    }

    /**
     * Permissions groupées (accesseur)
     */
    public function getPermissionsGroupedAttribute(): array
    {
        return $this->getGroupedPermissions();
    }

    /**
     * Vérifier si l'utilisateur est un super admin
     */
    public function isSuperAdmin(): bool
    {
        return (bool) ($this->role?->is_super_admin);
    }

    /**
     * Vérifier si l'utilisateur est actif
     */
    public function isActive(): bool
    {
        return $this->status === 'actif';
    }


    /**
     * Vérifier si l'utilisateur est inactif
     */
    public function isInactive(): bool
    {
        return $this->status === 'inactif';
    }

    /**
     * Vérifier si l'utilisateur est bloqué
     */
    public function isBlocked(): bool
    {
        return $this->status === 'bloque';
    }

    /**
     * Vérifier si l'utilisateur est gelé
     */
    public function isFrozen(): bool
    {
        return $this->status === 'gele' || ($this->frozen_until && $this->frozen_until->isFuture()) ;
    }

    /**
     * Vérifier si l'utilisateur est dégelé
     */
    public function isUnfrozen(): bool
    {
        return $this->status === 'actif' ||
            ($this->frozen_until && $this->frozen_until->isPast());
    }

    /**
     * Obtenir le temps restant de gel en secondes
     */
    public function getFrozenRemainingSeconds(): int
    {
        if (!$this->isFrozen()) {
            return 0;
        }
        return (int) now()->diffInSeconds($this->frozen_until);
    }

    /**
     * Obtenir le temps restant de gel formaté
     */
    public function getFrozenRemainingFormatted(): string
    {
        $seconds = $this->getFrozenRemainingSeconds();
        if ($seconds <= 0) {
            return 'Dégelé';
        }

        $minutes = floor($seconds / 60);
        $secs = $seconds % 60;

        if ($minutes > 0) {
            return "{$minutes}m {$secs}s";
        }
        return "{$secs}s";
    }

    /**
     * Vérifier si l'utilisateur peut être gelé manuellement
     */
    public function canBeFrozenManually(): bool
    {
        return !$this->isFrozen() && 
            $this->status !== 'bloque' && 
            $this->status !== 'inactif';
    }

    /**
     * Obtenir le niveau de gel avec libellé
     */
    public function getFreezeLevelLabel(): string
    {
        $levels = [
            0 => 'Aucun',
            1 => 'Léger (3 tentatives)',
            2 => 'Modéré (4 tentatives)',
            3 => 'Sévère (5 tentatives)',
            4 => 'Manuel (Administrateur)',
        ];
        return $levels[$this->freeze_level] ?? 'Inconnu';
    }

    /**
     * Vérifier si le compte peut être dégelé manuellement
     */
    public function canBeUnfrozenManually(): bool
    {
        return $this->status === 'gele' ||
            ($this->freeze_level > 0 &&
            $this->frozen_until &&
            $this->frozen_until->isFuture());
    }


    public function isPasswordExpired(): bool
    {
        return $this->password_expires_at && $this->password_expires_at->isPast();
    }

    public function canLogin(): bool
    {
        if (!$this->isActive() || $this->isBlocked() || $this->isPasswordExpired() || $this->isInactive()) {
            return false;
        }

        if ($this->isFrozen()) {
            return false;
        }

        if ($this->is_locked) {
            return false;
        }

        return true;
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'actif');
    }

    public function scopeInactive($query)
    {
        return $query->where('status', 'inactif');
    }

    public function scopeNotBlocked($query)
    {
        return $query->where('status', '!=', 'bloque');
    }

    public function scopeWithExpiredPassword($query)
    {
        return $query->where('password_expires_at', '<=', now());
    }
}

// namespace App\Models\Api\Ynov\parameter;

// use App\Models\Api\Ynov\parameter\AccountFreeze;
// use App\Models\Api\Ynov\parameter\ActivityLog;
// use App\Models\Api\Ynov\parameter\Agence;
// use App\Models\Api\Ynov\parameter\GroupNotif;
// use App\Models\Api\Ynov\parameter\LoginAttempt;
// use App\Models\Api\Ynov\parameter\OtpCode;
// use App\Models\Api\Ynov\parameter\Partner;
// use App\Models\Api\Ynov\parameter\PasswordHistory;
// use App\Models\Api\Ynov\parameter\Reseau;
// use App\Models\Api\Ynov\parameter\Role;
// use App\Models\Api\Ynov\parameter\TwoFactorRecoveryCode;
// use App\Models\Api\Ynov\parameter\UserDetails;
// use App\Models\Api\Ynov\parameter\UserDevice;
// use App\Services\Api\Ynov\CacheService;
// use App\Services\Api\Ynov\PermissionService;
// use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Illuminate\Database\Eloquent\Relations\BelongsTo;
// use Illuminate\Database\Eloquent\Relations\BelongsToMany;
// use Illuminate\Database\Eloquent\Relations\HasMany;
// use Illuminate\Database\Eloquent\Relations\HasOne;
// use Illuminate\Database\Eloquent\SoftDeletes;
// use Illuminate\Foundation\Auth\User as Authenticatable;
// use Illuminate\Notifications\Notifiable;
// use Illuminate\Support\Str;
// use Laravel\Sanctum\HasApiTokens;

// class User extends Authenticatable
// {
//     use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

//     protected $table = 'users';

//     protected $fillable = [
//         'uuid_user',
//         'login',
//         'email',
//         'password',
//         'role_uuid',
//         'user_type',
//         'partner_uuid',
//         'reseau_uuid',
//         'status',
//         'is_first_login',
//         'is_online',
//         'is_locked',
//         'password_changed_at',
//         'password_expires_at',
//         'last_login_at',
//         'last_activity_at',
//         'email_verified_at',
//         'failed_login_count',
//         'freeze_level',
//         'frozen_until',
//         'freeze_count',
//         'blocked_reason',
//         'blocked_by',
//         'blocked_at',
//         'two_factor_secret',
//         'two_factor_enabled',
//         'two_factor_recovery_codes',
//         'preferences',
//         'metadata',
//         'remember_token',
//     ];

//     protected $hidden = [
//         'password',
//         'remember_token',
//         'two_factor_secret',
//         'two_factor_recovery_codes',
//     ];

//     protected $casts = [
//         'email_verified_at' => 'datetime',
//         'password_changed_at' => 'datetime',
//         'password_expires_at' => 'datetime',
//         'last_login_at' => 'datetime',
//         'last_activity_at' => 'datetime',
//         'frozen_until' => 'datetime',
//         'blocked_at' => 'datetime',
//         // ================================================================
//         // CORRECTION #9 : Suppression du cast 'hashed' pour éviter le double hash
//         // Le Hash::make() est déjà appelé manuellement dans les Services
//         // ================================================================
//         // 'password' => 'hashed',  // SUPPRIMÉ
//         'is_first_login' => 'boolean',
//         'is_online' => 'boolean',
//         'is_locked' => 'boolean',
//         'two_factor_enabled' => 'boolean',
//         'preferences' => 'array',
//         'metadata' => 'array',
//     ];

//     protected static function booted(): void
//     {
//         static::creating(fn(self $user) => $user->uuid_user ??= (string) Str::uuid());

//         static::creating(function (self $user) {
//             if (empty($user->password_expires_at)) {
//                 $user->password_expires_at = now()->addDays(90);
//             }
//         });

//         static::saved(function ($user) {
//             $user->invalidatePermissionCache();
//         });

//         static::deleted(function ($user) {
//             $user->invalidatePermissionCache();
//         });
//     }

//     // Relations principales
//     public function role(): BelongsTo
//     {
//         return $this->belongsTo(Role::class, 'role_uuid', 'uuid_role');
//     }

//     public function partner(): BelongsTo
//     {
//         return $this->belongsTo(Partner::class, 'partner_uuid', 'uuid_partner');
//     }

//     public function reseau(): BelongsTo
//     {
//         return $this->belongsTo(Reseau::class, 'reseau_uuid', 'uuid_reseau');
//     }

//     // Relations Many-to-Many
//     public function agences(): BelongsToMany
//     {
//         return $this->belongsToMany(
//             Agence::class,
//             'user_agences',
//             'user_uuid',
//             'agence_uuid',
//             'uuid_user',
//             'uuid_agence'
//         )->withPivot([
//             'is_primary',
//             'is_active',
//             'assigned_at',
//             'assigned_by',
//             'role_uuid',
//             'custom_permissions',
//             'metadata'
//         ])->withTimestamps();
//     }

//     public function groupNotifs(): BelongsToMany
//     {
//         return $this->belongsToMany(
//             GroupNotif::class,
//             'user_group_notifs',
//             'user_uuid',
//             'group_notif_uuid',
//             'uuid_user',
//             'uuid_group_notif'
//         )->withPivot([
//             'is_primary',
//             'is_active',
//             'assigned_at',
//             'assigned_by',
//             'metadata'
//         ])->withTimestamps();
//     }

//     // Relations One-to-Many
//     public function details(): HasOne
//     {
//         return $this->hasOne(UserDetails::class, 'user_uuid', 'uuid_user');
//     }

//     public function blockedBy(): BelongsTo
//     {
//         return $this->belongsTo(User::class, 'blocked_by', 'uuid_user');
//     }

//     public function loginAttempts(): HasMany
//     {
//         return $this->hasMany(LoginAttempt::class, 'user_uuid', 'uuid_user');
//     }

//     public function accountFreezes(): HasMany
//     {
//         return $this->hasMany(AccountFreeze::class, 'user_uuid', 'uuid_user');
//     }

//     public function activityLogs(): HasMany
//     {
//         return $this->hasMany(ActivityLog::class, 'user_uuid', 'uuid_user');
//     }

//     public function passwordHistories(): HasMany
//     {
//         return $this->hasMany(PasswordHistory::class, 'user_uuid', 'uuid_user');
//     }

//     public function devices(): HasMany
//     {
//         return $this->hasMany(UserDevice::class, 'user_uuid', 'uuid_user');
//     }

//     public function otpCodes(): HasMany
//     {
//         return $this->hasMany(OtpCode::class, 'user_uuid', 'uuid_user');
//     }

//     public function twoFactorRecoveryCodes(): HasMany
//     {
//         return $this->hasMany(TwoFactorRecoveryCode::class, 'user_uuid', 'uuid_user');
//     }

//     // ================================================================
//     // CORRECTION #12 : Délégation à PermissionService pour éviter la duplication
//     // ================================================================
    
//     /**
//      * Vérifier si l'utilisateur a une permission spécifique
//      * Utilise PermissionService comme source unique de vérité
//      */
//     public function hasPermission(string $permissionCode): bool
//     {
//         if ($this->isSuperAdmin()) {
//             return true;
//         }

//         $permissions = $this->getPermissions();
//         return in_array($permissionCode, $permissions);
//     }

//     /**
//      * Obtenir les permissions de l'utilisateur
//      * ================================================================
//      * CORRECTION #12 : Délégation au service pour éviter la duplication
//      * ================================================================
//      */
//     public function getPermissions(): array
//     {
//         // Délégation au service centralisé
//         return app(PermissionService::class)->getUserPermissions($this);
//     }

//     /**
//      * Invalider le cache des permissions
//      */
//     public function invalidatePermissionCache(): void
//     {
//         // Cache::forget("user_permissions_{$this->uuid_user}");
//         app(CacheService::class)->invalidateUserPermissions($this);
//     }

//     /**
//      * Rafraîchir les permissions de l'utilisateur
//      */
//     public function refreshPermissions(): array
//     {
//         $this->invalidatePermissionCache();
//         return $this->getPermissions();
//     }

//     /**
//      * Vérifier si l'utilisateur est un super admin
//      */
//     public function isSuperAdmin(): bool
//     {
//         return (bool) ($this->role?->is_super_admin);
//     }

//     /**
//      * Vérifier si l'utilisateur est actif
//      */
//     public function isActive(): bool
//     {
//         return $this->status === 'actif';
//     }

//     /**
//      * Vérifier si l'utilisateur est inactif
//      */
//     public function isInactive(): bool
//     {
//         return $this->status === 'inactif';
//     }

//     /**
//      * Vérifier si l'utilisateur est bloqué
//      */
//     public function isBlocked(): bool
//     {
//         return $this->status === 'bloque';
//     }

//     /**
//      * Vérifier si l'utilisateur est gelé
//      */
//     public function isFrozen(): bool
//     {
//         return $this->status === 'gele' || ($this->frozen_until && $this->frozen_until->isFuture());
//     }

//     /**
//      * Vérifier si l'utilisateur est dégelé
//      */
//     public function isUnfrozen(): bool
//     {
//         return $this->status === 'actif' ||
//             ($this->frozen_until && $this->frozen_until->isPast());
//     }

//     /**
//      * Obtenir le temps restant de gel en secondes
//      */
//     public function getFrozenRemainingSeconds(): int
//     {
//         if (!$this->isFrozen()) {
//             return 0;
//         }
//         return (int) now()->diffInSeconds($this->frozen_until);
//     }

//     /**
//      * Obtenir le temps restant de gel formaté
//      */
//     public function getFrozenRemainingFormatted(): string
//     {
//         $seconds = $this->getFrozenRemainingSeconds();
//         if ($seconds <= 0) {
//             return 'Dégelé';
//         }

//         $minutes = floor($seconds / 60);
//         $secs = $seconds % 60;

//         if ($minutes > 0) {
//             return "{$minutes}m {$secs}s";
//         }
//         return "{$secs}s";
//     }

//     /**
//      * Vérifier si l'utilisateur peut être gelé manuellement
//      */
//     public function canBeFrozenManually(): bool
//     {
//         return !$this->isFrozen() &&
//             $this->status !== 'bloque' &&
//             $this->status !== 'inactif';
//     }

//     /**
//      * Obtenir le niveau de gel avec libellé
//      */
//     public function getFreezeLevelLabel(): string
//     {
//         $levels = [
//             0 => 'Aucun',
//             1 => 'Léger (3 tentatives)',
//             2 => 'Modéré (4 tentatives)',
//             3 => 'Sévère (5 tentatives)',
//             4 => 'Manuel (Administrateur)',
//         ];
//         return $levels[$this->freeze_level] ?? 'Inconnu';
//     }

//     /**
//      * Vérifier si le compte peut être dégelé manuellement
//      */
//     public function canBeUnfrozenManually(): bool
//     {
//         return $this->status === 'gele' ||
//             ($this->freeze_level > 0 &&
//             $this->frozen_until &&
//             $this->frozen_until->isFuture());
//     }

//     public function isPasswordExpired(): bool
//     {
//         return $this->password_expires_at && $this->password_expires_at->isPast();
//     }

//     public function canLogin(): bool
//     {
//         if (!$this->isActive() || $this->isBlocked() || $this->isPasswordExpired() || $this->isInactive()) {
//             return false;
//         }

//         if ($this->isFrozen()) {
//             return false;
//         }

//         if ($this->is_locked) {
//             return false;
//         }

//         return true;
//     }

//     // Scopes
//     public function scopeActive($query)
//     {
//         return $query->where('status', 'actif');
//     }

//     public function scopeInactive($query)
//     {
//         return $query->where('status', 'inactif');
//     }

//     public function scopeNotBlocked($query)
//     {
//         return $query->where('status', '!=', 'bloque');
//     }

//     public function scopeWithExpiredPassword($query)
//     {
//         return $query->where('password_expires_at', '<=', now());
//     }
// }
