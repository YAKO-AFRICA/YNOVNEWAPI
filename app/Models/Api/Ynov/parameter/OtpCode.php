<?php
// app/Models/Api/Ynov/parameter/OtpCode.php
namespace App\Models\Api\Ynov\parameter;

use App\Models\Api\Ynov\parameter\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class OtpCode extends Model
{
    
    protected $table = 'otp_codes';
    
    protected $fillable = [
        'uuid_otp_code',
        'user_uuid',
        'code', // Hash du code OTP
        'code_plain', // Code en clair (pour SMS/Email)
        'channel',
        'purpose',
        'length',
        'expires_at',
        'resend_count',
        'last_resend_at',
        'is_used',
        'is_valid',
        'attempts',
        'ip_address',
        'user_agent',
        'location',
        'used_at',
        'metadata',
    ];
    
    protected $casts = [
        'is_used' => 'boolean',
        'is_valid' => 'boolean',
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
        'last_resend_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(fn (self $model) => $model->uuid_otp_code ??= (string) Str::uuid());
        
        static::creating(function (self $model) {
            if (empty($model->length)) {
                $model->length = 6;
            }
            if (empty($model->expires_at)) {
                $model->expires_at = now()->addMinutes(5);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_uuid', 'uuid_user');
    }
    
    /**
     * Vérifier si l'OTP est valide (non utilisé, non expiré)
     */
    public function isValid(): bool
    {
        return $this->is_valid && !$this->is_used && !$this->isExpired();
    }
    
    /**
     * Vérifier si l'OTP est expiré
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
    
    /**
     * Vérifier si l'OTP peut être renvoyé
     */
    public function canResend(int $maxResends = 3, int $cooldownMinutes = 1): bool
    {
        if ($this->resend_count >= $maxResends) {
            return false;
        }
        
        if ($this->last_resend_at && $this->last_resend_at->diffInMinutes(now()) < $cooldownMinutes) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Marquer l'OTP comme utilisé
     */
    public function markAsUsed(): self
    {
        $this->is_used = true;
        $this->is_valid = false;
        $this->used_at = now();
        $this->save();
        return $this;
    }
    
    /**
     * Incrémenter le compteur de tentatives
     */
    public function incrementAttempts(): self
    {
        $this->attempts++;
        if ($this->attempts >= 3) {
            $this->is_valid = false;
        }
        $this->save();
        return $this;
    }
    
    /**
     * Incrémenter le compteur de renvois
     */
    public function incrementResendCount(): self
    {
        $this->resend_count++;
        $this->last_resend_at = now();
        $this->save();
        return $this;
    }
    
    /**
     * Scope pour les OTP valides
     */
    public function scopeValid($query)
    {
        return $query->where('is_valid', true)
                     ->where('is_used', false)
                     ->where('expires_at', '>', now());
    }
    
    /**
     * Scope pour les OTP d'un canal spécifique
     */
    public function scopeChannel($query, string $channel)
    {
        return $query->where('channel', $channel);
    }
    
    /**
     * Scope pour les OTP d'un usage spécifique
     */
    public function scopePurpose($query, string $purpose)
    {
        return $query->where('purpose', $purpose);
    }
    
    /**
     * Scope pour les OTP d'un utilisateur spécifique
     */
    public function scopeForUser($query, string $userUuid)
    {
        return $query->where('user_uuid', $userUuid);
    }
}