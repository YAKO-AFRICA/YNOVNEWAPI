<?php
// app/Models/Api/Ynov/parameter/UserDevice.php
namespace App\Models\Api\Ynov\parameter;

use App\Models\Api\Ynov\parameter\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class UserDevice extends Model
{
    
    protected $table = 'user_devices';
    
    protected $fillable = [
        'uuid_device',
        'user_uuid',
        'fingerprint',
        'device_id',
        'device_name',
        'device_type',
        'os',
        'browser',
        'ip_address',
        'user_agent',
        'location',
        'is_trusted',
        'is_active',
        'trusted_at',
        'last_used_at',
        'metadata',
    ];
    
    protected $casts = [
        'is_trusted' => 'boolean',
        'is_active' => 'boolean',
        'trusted_at' => 'datetime',
        'last_used_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(fn (self $model) => $model->uuid_device ??= (string) Str::uuid());
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_uuid', 'uuid_user');
    }
    
    /**
     * Vérifier si l'appareil est actif
     */
    public function isActive(): bool
    {
        return $this->is_active && $this->last_used_at && $this->last_used_at->diffInDays(now()) <= 30;
    }
    
    /**
     * Marquer l'appareil comme utilisé
     */
    public function markAsUsed(): self
    {
        $this->last_used_at = now();
        $this->save();
        return $this;
    }
    
    /**
     * Marquer l'appareil comme de confiance
     */
    public function markAsTrusted(): self
    {
        $this->is_trusted = true;
        $this->trusted_at = now();
        $this->save();
        return $this;
    }
    
    /**
     * Scope pour les appareils de confiance
     */
    public function scopeTrusted($query)
    {
        return $query->where('is_trusted', true);
    }
    
    /**
     * Scope pour les appareils actifs
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}