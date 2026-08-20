<?php
// app/Models/Api/Ynov/parameter/Session.php
namespace App\Models\Api\Ynov\parameter;

use App\Models\Api\Ynov\parameter\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Session extends Model
{
    
    protected $table = 'sessions';
    
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'user_uuid',
        'ip_address',
        'user_agent',
        'payload',
        'last_activity',
        'device_info',
        'location',
        'expires_at',
        'is_active',
    ];

    protected $casts = [
        'last_activity' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
        'device_info' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->id)) {
                $model->id = Str::random(40);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_uuid', 'uuid_user');
    }
    
    /**
     * Vérifier si la session est active
     */
    public function isActive(): bool
    {
        return $this->is_active && (!$this->expires_at || $this->expires_at->isFuture());
    }
    
    /**
     * Vérifier si la session est expirée
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
    
    /**
     * Marquer la session comme inactive
     */
    public function markAsInactive(): self
    {
        $this->is_active = false;
        $this->save();
        return $this;
    }
    
    /**
     * Récupérer le temps restant avant expiration
     */
    public function getRemainingSecondsAttribute(): int
    {
        if (!$this->expires_at) {
            return PHP_INT_MAX;
        }
        if ($this->isExpired()) {
            return 0;
        }
        return now()->diffInSeconds($this->expires_at);
    }
    
    /**
     * Scope pour les sessions actives
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                     ->where(function ($q) {
                         $q->whereNull('expires_at')
                           ->orWhere('expires_at', '>', now());
                     });
    }
    
    /**
     * Scope pour les sessions expirées
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }
    
    /**
     * Scope pour un utilisateur spécifique
     */
    public function scopeForUser($query, string $userUuid)
    {
        return $query->where('user_uuid', $userUuid);
    }
}