<?php
// app/Models/Api/Ynov/parameter/UserGroupNotif.php
namespace App\Models\Api\Ynov\parameter;

use App\Models\Api\Ynov\parameter\GroupNotif;
use App\Models\Api\Ynov\parameter\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class UserGroupNotif extends Model
{
    
    protected $table = 'user_group_notifs';
    
    protected $fillable = [
        'uuid_user_group_notif',
        'user_uuid',
        'group_notif_uuid',
        'is_primary',
        'is_active',
        'assigned_at',
        'assigned_by',
        'metadata',
    ];
    
    protected $casts = [
        'is_primary' => 'boolean',
        'is_active' => 'boolean',
        'assigned_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(fn (self $model) => $model->uuid_user_group_notif ??= (string) Str::uuid());
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_uuid', 'uuid_user');
    }

    public function groupNotif(): BelongsTo
    {
        return $this->belongsTo(GroupNotif::class, 'group_notif_uuid', 'uuid_group_notif');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by', 'uuid_user');
    }
}