<?php
// app/Models/Api/Ynov/parameter/IpRestriction.php
namespace App\Models\Api\Ynov\parameter;

use App\Models\Api\Ynov\parameter\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class IpRestriction extends Model
{
    
    protected $table = 'ip_restrictions';
    
    protected $fillable = [
        'uuid_restriction',
        'ip_address',
        'type',
        'reason',
        'status',
        'created_by',
        'updated_by',
        'expires_at',
        'metadata',
    ];
    
    protected $casts = [
        'expires_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(fn (self $model) => $model->uuid_restriction ??= (string) Str::uuid());
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'uuid_user');
    }
    
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by', 'uuid_user');
    }
    
    /**
     * Vérifier si la restriction est active
     */
    public function isActive(): bool
    {
        return $this->status === 'actif' && (!$this->expires_at || $this->expires_at->isFuture());
    }
    
    /**
     * Vérifier si la restriction est expirée
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
    
    /**
     * Vérifier si c'est une whitelist
     */
    public function isWhitelist(): bool
    {
        return $this->type === 'whitelist';
    }
    
    /**
     * Vérifier si c'est une blacklist
     */
    public function isBlacklist(): bool
    {
        return $this->type === 'blacklist';
    }
    
    /**
     * Scope pour les restrictions actives
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'actif')
                     ->where(function ($q) {
                         $q->whereNull('expires_at')
                           ->orWhere('expires_at', '>', now());
                     });
    }
    
    /**
     * Scope pour les whitelists
     */
    public function scopeWhitelist($query)
    {
        return $query->where('type', 'whitelist');
    }
    
    /**
     * Scope pour les blacklists
     */
    public function scopeBlacklist($query)
    {
        return $query->where('type', 'blacklist');
    }
    
    /**
     * Vérifier si une IP est restreinte
     */
    public static function isIpRestricted(string $ip, string $type = 'blacklist'): bool
    {
        return self::active()
                   ->where('type', $type)
                   ->where('ip_address', $ip)
                   ->exists();
    }
    
    /**
     * Vérifier si une IP est autorisée (whitelist)
     */
    public static function isIpWhitelisted(string $ip): bool
    {
        return self::isIpRestricted($ip, 'whitelist');
    }
    
    /**
     * Vérifier si une IP est blacklistée
     */
    public static function isIpBlacklisted(string $ip): bool
    {
        return self::isIpRestricted($ip, 'blacklist');
    }
}