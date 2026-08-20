<?php
// app/Models/Api/Ynov/parameter/EmailVerificationToken.php
namespace App\Models\Api\Ynov\parameter;

use App\Models\Api\Ynov\parameter\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class EmailVerificationToken extends Model
{
    
    protected $table = 'email_verification_tokens';
    
    protected $fillable = [
        'user_uuid',
        'token',
        'expires_at',
        'ip_address',
        'user_agent',
        'metadata',
    ];
    
    protected $casts = [
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        
        static::creating(function (self $model) {
            if (empty($model->expires_at)) {
                $model->expires_at = now()->addHours(24);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_uuid', 'uuid_user');
    }
    
    /**
     * Vérifier si le token est valide
     */
    public function isValid(): bool
    {
        return !$this->expires_at->isPast();
    }
    
    /**
     * Vérifier si le token est expiré
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
    
    /**
     * Récupérer le temps restant avant expiration
     */
    public function getRemainingSecondsAttribute(): int
    {
        if ($this->isExpired()) {
            return 0;
        }
        return now()->diffInSeconds($this->expires_at);
    }
    
    /**
     * Scope pour les tokens valides
     */
    public function scopeValid($query)
    {
        return $query->where('expires_at', '>', now());
    }
    
    /**
     * Scope pour les tokens expirés
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }
}