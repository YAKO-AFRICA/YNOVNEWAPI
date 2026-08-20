<?php
// app/Models/Api/Ynov/parameter/AccountFreeze.php
namespace App\Models\Api\Ynov\parameter;

use App\Models\Api\Ynov\parameter\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountFreeze extends Model
{
    protected $table = 'account_freezes';

    protected $fillable = [
        'user_uuid',
        'freeze_level',
        'failed_attempts_count',
        'frozen_at',
        'unfrozen_at',
        'unfrozen_by',
    ];

    protected $casts = [
        'frozen_at' => 'datetime',
        'unfrozen_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_uuid', 'uuid_user');
    }

    public function unfrozenBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'unfrozen_by', 'uuid_user');
    }

    /**
     * Vérifier si le gel est encore actif.
     *
     * Un enregistrement de gel est actif tant qu'il n'a pas été dégelé,
     * c'est-à-dire tant que `unfrozen_at` est null.
     * (Corrigé : l'ancienne version testait `unfrozen_at->isFuture()`,
     * ce qui n'a pas de sens puisque `unfrozen_at` est renseigné au
     * moment du dégel effectif, jamais programmé dans le futur.)
     */
    public function isActive(): bool
    {
        return $this->unfrozen_at === null;
    }

    /**
     * Vérifier si le gel a été levé (dégelé, manuellement ou automatiquement)
     */
    public function isExpired(): bool
    {
        return $this->unfrozen_at !== null;
    }

    /**
     * Temps restant avant dégel, si ce gel est toujours actif.
     * La source de vérité reste User::getFrozenRemainingSeconds() (frozen_until),
     * cet accesseur ne fait que refléter la même donnée au niveau de l'enregistrement.
     */
    public function getRemainingSecondsAttribute(): int
    {
        if (!$this->isActive() || !$this->user?->frozen_until) {
            return 0;
        }

        return max(0, (int) now()->diffInSeconds($this->user->frozen_until, false));
    }
}