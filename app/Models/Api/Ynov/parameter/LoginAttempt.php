<?php
// app/Models/Api/Ynov/parameter/LoginAttempt.php
namespace App\Models\Api\Ynov\parameter;

use App\Models\Api\Ynov\parameter\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class LoginAttempt extends Model
{
    
    protected $table = 'login_attempts';
    
    protected $fillable = [
        'user_uuid',
        'login_attempted',
        'ip_address',
        'user_agent',
        'location',
        'is_successful',
        'failure_reason',
        'attempted_at',
        'metadata',
    ];
    
    protected $casts = [
        'is_successful' => 'boolean',
        'attempted_at' => 'datetime',
        'created_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_uuid', 'uuid_user');
    }
    
    /**
     * Vérifier si la tentative est récente (moins de X minutes)
     */
    public function isRecent(int $minutes = 5): bool
    {
        return $this->attempted_at->diffInMinutes(now()) <= $minutes;
    }
    
    /**
     * Marquer la tentative comme réussie
     */
    public function markAsSuccessful(): self
    {
        $this->is_successful = true;
        $this->failure_reason = null;
        $this->save();
        return $this;
    }
    
    /**
     * Marquer la tentative comme échouée
     */
    public function markAsFailed(string $reason = null): self
    {
        $this->is_successful = false;
        $this->failure_reason = $reason;
        $this->save();
        return $this;
    }
    
    /**
     * Scope pour les tentatives échouées
     */
    public function scopeFailed($query)
    {
        return $query->where('is_successful', false);
    }
    
    /**
     * Scope pour les tentatives réussies
     */
    public function scopeSuccessful($query)
    {
        return $query->where('is_successful', true);
    }
    
    /**
     * Scope pour les tentatives d'un utilisateur spécifique
     */
    public function scopeForUser($query, string $userUuid)
    {
        return $query->where('user_uuid', $userUuid);
    }
    
    /**
     * Scope pour les tentatives d'une IP spécifique
     */
    public function scopeFromIp($query, string $ip)
    {
        return $query->where('ip_address', $ip);
    }
    
    /**
     * Scope pour les tentatives des dernières X minutes
     */
    public function scopeRecent($query, int $minutes = 5)
    {
        return $query->where('attempted_at', '>=', now()->subMinutes($minutes));
    }
}