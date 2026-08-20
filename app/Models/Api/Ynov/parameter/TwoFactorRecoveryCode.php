<?php
// app/Models/Api/Ynov/parameter/TwoFactorRecoveryCode.php
namespace App\Models\Api\Ynov\parameter;

use App\Models\Api\Ynov\parameter\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class TwoFactorRecoveryCode extends Model
{
    
    protected $table = 'two_factor_recovery_codes';
    
    protected $fillable = [
        'user_uuid',
        'code',
        'is_used',
        'used_at',
        'expires_at',
        'metadata',
    ];
    
    protected $casts = [
        'is_used' => 'boolean',
        'used_at' => 'datetime',
        'expires_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(fn (self $model) => $model->uuid ??= (string) Str::uuid());
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_uuid', 'uuid_user');
    }
    
    /**
     * Vérifier si le code est valide (non utilisé, non expiré)
     */
    public function isValid(): bool
    {
        return !$this->is_used && (!$this->expires_at || $this->expires_at->isFuture());
    }
    
    /**
     * Vérifier si le code est expiré
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
    
    /**
     * Marquer le code comme utilisé
     */
    public function markAsUsed(): self
    {
        $this->is_used = true;
        $this->used_at = now();
        $this->save();
        return $this;
    }
    
    /**
     * Générer des codes de récupération pour un utilisateur
     */
    public static function generateForUser(string $userUuid, int $count = 8): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $code = Str::random(10);
            $codes[] = $code;
            
            self::create([
                'user_uuid' => $userUuid,
                'code' => bcrypt($code),
                'expires_at' => now()->addMonths(6),
            ]);
        }
        return $codes;
    }
    
    /**
     * Valider un code de récupération
     */
    public static function validateCode(string $userUuid, string $code): bool
    {
        $recoveryCode = self::where('user_uuid', $userUuid)
                            ->where('is_used', false)
                            ->where(function ($q) {
                                $q->whereNull('expires_at')
                                  ->orWhere('expires_at', '>', now());
                            })
                            ->first();
        
        if (!$recoveryCode) {
            return false;
        }
        
        if (password_verify($code, $recoveryCode->code)) {
            $recoveryCode->markAsUsed();
            return true;
        }
        
        return false;
    }
    
    /**
     * Scope pour les codes valides
     */
    public function scopeValid($query)
    {
        return $query->where('is_used', false)
                     ->where(function ($q) {
                         $q->whereNull('expires_at')
                           ->orWhere('expires_at', '>', now());
                     });
    }
    
    /**
     * Scope pour les codes utilisés
     */
    public function scopeUsed($query)
    {
        return $query->where('is_used', true);
    }
}