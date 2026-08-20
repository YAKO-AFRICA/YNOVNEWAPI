<?php
// app/Models/Api/Ynov/parameter/GroupNotif.php
namespace App\Models\Api\Ynov\parameter;

use App\Models\Api\Ynov\parameter\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class GroupNotif extends Model
{
    use SoftDeletes, HasFactory;
    
    protected $table = 'group_notifs';
    
    protected $fillable = [
        'uuid_group_notif',
        'code',
        'libelle',
        'description',
        'channels',
        'preferences',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];
    
    protected $casts = [
        'channels' => 'array',
        'preferences' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(fn (self $model) => $model->uuid_group_notif ??= (string) Str::uuid());
    }

    /**
     * Relation Many-to-Many avec les utilisateurs
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'user_group_notifs',
            'group_notif_uuid',
            'user_uuid',
            'uuid_group_notif',
            'uuid_user'
        )->withPivot([
            'is_primary',
            'is_active',
            'assigned_at',
            'assigned_by',
            'metadata'
        ])->withTimestamps();
    }
    
    /**
     * Récupérer les utilisateurs actifs du groupe
     */
    public function activeUsers()
    {
        return $this->users()->wherePivot('is_active', true);
    }
    
    /**
     * Scope pour les groupes actifs
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'actif');
    }
    
    /**
     * Scope pour un canal spécifique
     */
    public function scopeWithChannel($query, string $channel)
    {
        return $query->whereJsonContains('channels', $channel);
    }
}