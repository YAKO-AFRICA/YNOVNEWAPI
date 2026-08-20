<?php
// app/Models/Api/Ynov/parameter/ActivityLog.php (Ajouts)
namespace App\Models\Api\Ynov\parameter;

use App\Models\Api\Ynov\parameter\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ActivityLog extends Model
{
    use HasFactory;
    
    
    protected $table = 'activity_logs';
    
    public $timestamps = false;
    
    protected $fillable = [
        'uuid_activity_log',
        'user_uuid',
        'action',
        'action_type',
        'module',
        'description',
        'resource_type',
        'resource_id',
        'old_values',
        'new_values',
        'metadata',
        'ip_address',
        'user_agent',
        'location',
        'session_id',
        'level',
    ];
    
    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(fn (self $model) => $model->uuid_activity_log ??= (string) Str::uuid());
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_uuid', 'uuid_user');
    }
    
    /**
     * Créer un log d'activité
     */
    public static function log(array $data): self
    {
        return self::create(array_merge([
            // 'uuid_activity_log' => Str::uuid(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'session_id' => session()->getId(),
        ], $data));
    }
    
    /**
     * Log d'une action de création
     */
    public static function logCreate(string $module, string $resourceType, $resourceId, array $data = []): self
    {
        return self::log([
            'action' => 'create',
            'action_type' => 'crud',
            'module' => $module,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'new_values' => $data,
            'description' => "Création d'un(e) $resourceType",
            'level' => 'info',
        ]);
    }
    
    /**
     * Log d'une action de mise à jour
     */
    public static function logUpdate(string $module, string $resourceType, $resourceId, array $oldValues, array $newValues): self
    {
        return self::log([
            'action' => 'update',
            'action_type' => 'crud',
            'module' => $module,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'description' => "Mise à jour d'un(e) $resourceType",
            'level' => 'info',
        ]);
    }
    
    /**
     * Log d'une action de suppression
     */
    public static function logDelete(string $module, string $resourceType, $resourceId, array $data = []): self
    {
        return self::log([
            'action' => 'delete',
            'action_type' => 'crud',
            'module' => $module,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'old_values' => $data,
            'description' => "Suppression d'un(e) $resourceType",
            'level' => 'warning',
        ]);
    }
    
    /**
     * Log d'une action de connexion
     */
    public static function logLogin(string $userUuid, bool $success, string $reason = null): self
    {
        return self::log([
            'user_uuid' => $userUuid,
            'action' => $success ? 'login_success' : 'login_failed',
            'action_type' => 'authentication',
            'module' => 'auth',
            'description' => $success ? 'Connexion réussie' : 'Tentative de connexion échouée',
            'level' => $success ? 'info' : 'warning',
            'metadata' => ['reason' => $reason],
        ]);
    }
    
    /**
     * Scope pour les logs d'un module spécifique
     */
    public function scopeModule($query, string $module)
    {
        return $query->where('module', $module);
    }
    
    /**
     * Scope pour les logs d'un niveau spécifique
     */
    public function scopeLevel($query, string $level)
    {
        return $query->where('level', $level);
    }
    
    /**
     * Scope pour les logs d'une action spécifique
     */
    public function scopeAction($query, string $action)
    {
        return $query->where('action', $action);
    }
    
    /**
     * Scope pour les logs des 7 derniers jours
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
    
    /**
     * Scope pour les logs d'une ressource spécifique
     */
    public function scopeResource($query, string $type, $id = null)
    {
        $query->where('resource_type', $type);
        if ($id) {
            $query->where('resource_id', $id);
        }
        return $query;
    }
}