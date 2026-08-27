<?php
// app/Models/Api/Ynov/parameter/Notification.php
namespace App\Models\Api\Ynov\parameter;

use App\Models\Api\Ynov\parameter\GroupNotif;
use App\Models\Api\Ynov\parameter\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Notification extends Model
{
    use SoftDeletes;

    protected $table = 'notifications';

    protected $fillable = [
        'uuid_notification',
        'user_uuid',
        'group_notif_uuid',
        'title',
        'body',
        'type',
        'action_url',
        'action_label',
        'metadata',
        'read_at',
        'is_important',
        'important_at',
        'channel',
        'sent_at',
        'delivered_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'metadata' => 'array',
        'read_at' => 'datetime',
        'important_at' => 'datetime',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'is_important' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(fn (self $model) => $model->uuid_notification ??= (string) Str::uuid());
    }

    /**
     * Relation avec l'utilisateur
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_uuid', 'uuid_user');
    }

    /**
     * Relation avec le groupe de notification
     */
    public function groupNotif(): BelongsTo
    {
        return $this->belongsTo(GroupNotif::class, 'group_notif_uuid', 'uuid_group_notif');
    }

    /**
     * Marquer comme lue
     */
    public function markAsRead(): self
    {
        $this->update(['read_at' => now()]);
        return $this;
    }

    /**
     * Marquer comme non lue
     */
    public function markAsUnread(): self
    {
        $this->update(['read_at' => null]);
        return $this;
    }

    /**
     * Marquer comme importante
     */
    public function markAsImportant(): self
    {
        $this->update([
            'is_important' => true,
            'important_at' => now(),
        ]);
        return $this;
    }

    /**
     * Retirer le statut important
     */
    public function unmarkImportant(): self
    {
        $this->update([
            'is_important' => false,
            'important_at' => null,
        ]);
        return $this;
    }

    /**
     * Vérifier si la notification est lue
     */
    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    /**
     * Vérifier si la notification est non lue
     */
    public function isUnread(): bool
    {
        return $this->read_at === null;
    }

    /**
     * Vérifier si la notification est importante
     */
    public function isImportant(): bool
    {
        return $this->is_important;
    }

    /**
     * Scope pour les notifications non lues
     */
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    /**
     * Scope pour les notifications lues
     */
    public function scopeRead($query)
    {
        return $query->whereNotNull('read_at');
    }

    /**
     * Scope pour les notifications importantes
     */
    public function scopeImportant($query)
    {
        return $query->where('is_important', true);
    }

    /**
     * Scope pour un type spécifique
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope pour un groupe spécifique
     */
    public function scopeInGroup($query, string $groupUuid)
    {
        return $query->where('group_notif_uuid', $groupUuid);
    }

    /**
     * Scope pour un canal spécifique
     */
    public function scopeChannel($query, string $channel)
    {
        return $query->where('channel', $channel);
    }

    /**
     * Scope pour les notifications récentes (7 jours)
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}