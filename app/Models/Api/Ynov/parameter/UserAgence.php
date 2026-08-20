<?php
// app/Models/Api/Ynov/parameter/UserAgence.php
namespace App\Models\Api\Ynov\parameter;

use App\Models\Api\Ynov\parameter\Agence;
use App\Models\Api\Ynov\parameter\Role;
use App\Models\Api\Ynov\parameter\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class UserAgence extends Model
{
    
    protected $table = 'user_agences';
    
    protected $fillable = [
        'uuid_user_agence',
        'user_uuid',
        'agence_uuid',
        'is_primary',
        'is_active',
        'assigned_at',
        'assigned_by',
        'role_uuid',
        'custom_permissions',
        'metadata',
    ];
    
    protected $casts = [
        'is_primary' => 'boolean',
        'is_active' => 'boolean',
        'assigned_at' => 'datetime',
        'custom_permissions' => 'array',
        'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(fn (self $model) => $model->uuid_user_agence ??= (string) Str::uuid());
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_uuid', 'uuid_user');
    }

    public function agence(): BelongsTo
    {
        return $this->belongsTo(Agence::class, 'agence_uuid', 'uuid_agence');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_uuid', 'uuid_role');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by', 'uuid_user');
    }
}