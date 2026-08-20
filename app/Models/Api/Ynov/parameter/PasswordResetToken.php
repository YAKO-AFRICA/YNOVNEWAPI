<?php
// app/Models/Api/Ynov/parameter/PasswordResetToken.php
namespace App\Models\Api\Ynov\parameter;

use App\Models\Api\Ynov\parameter\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PasswordResetToken extends Model
{
    
    protected $table = 'password_reset_tokens';
    
    protected $primaryKey = 'token';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'login',
        'token',
        'ip_address',
        'user_agent',
        'expires_at',
    ];
    
    protected $casts = [
        'created_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->expires_at)) {
                $model->expires_at = now()->addHours(24);
            }
        });
    }
    
    /**
     * Récupérer l'utilisateur associé
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'login', 'login');
    }
    
    /**
     * Vérifier si le token est valide
     */
    public function isValid(): bool
    {
        return $this->expires_at && $this->expires_at->isFuture();
    }
    
    /**
     * Vérifier si le token est expiré
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
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