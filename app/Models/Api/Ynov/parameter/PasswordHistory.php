<?php
// app/Models/Api/Ynov/parameter/PasswordHistory.php
namespace App\Models\Api\Ynov\parameter;

use App\Models\Api\Ynov\parameter\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PasswordHistory extends Model
{
    
    protected $table = 'password_histories';
    
    protected $fillable = [
        'user_uuid',
        'password_hash',
        'changed_at',
        'ip_address',
        'user_agent',
        'metadata',
    ];
    
    protected $casts = [
        'changed_at' => 'datetime',
        'created_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected static function booted(): void
    {        
        static::creating(function (self $model) {
            if (empty($model->changed_at)) {
                $model->changed_at = now();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_uuid', 'uuid_user');
    }
    
    /**
     * Vérifier si un mot de passe a déjà été utilisé
     */
    public static function isPasswordReused(string $userUuid, string $password, int $limit = 5): bool
    {
        $histories = self::where('user_uuid', $userUuid)
                         ->orderBy('changed_at', 'desc')
                         ->limit($limit)
                         ->get();
        
        foreach ($histories as $history) {
            if (password_verify($password, $history->password_hash)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Récupérer l'historique des mots de passe d'un utilisateur
     */
    public static function getUserHistory(string $userUuid, int $limit = 5)
    {
        return self::where('user_uuid', $userUuid)
                   ->orderBy('changed_at', 'desc')
                   ->limit($limit)
                   ->get();
    }
    
    /**
     * Scope pour un utilisateur spécifique
     */
    public function scopeForUser($query, string $userUuid)
    {
        return $query->where('user_uuid', $userUuid);
    }
    
    /**
     * Scope pour les historiques récents
     */
    public function scopeRecent($query, int $days = 90)
    {
        return $query->where('changed_at', '>=', now()->subDays($days));
    }
}