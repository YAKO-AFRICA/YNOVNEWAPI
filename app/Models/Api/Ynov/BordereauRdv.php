<?php
// app/Models/Api/Ynov/parameter/BordereauRdv.php

namespace App\Models\Api\Ynov;

use App\Models\Api\Ynov\parameter\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class BordereauRdv extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'bordereau_rdvs';

    protected $primaryKey = 'uuid_bordereau_rdv';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'uuid_bordereau_rdv',
        'reference',
        'periode_1', // Date de début de la période
        'periode_2', // Date de fin de la période
        'observation',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'periode_1' => 'date',
        'periode_2' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->uuid_bordereau_rdv ??= (string) Str::uuid();
        });
    }

    public function details(): HasMany
    {
        return $this->hasMany(DetailBordereauRdv::class, 'bordereau_rdv_uuid', 'uuid_bordereau_rdv');
    }

    public function createur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'uuid_user');
    }

    public function modificateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by', 'uuid_user');
    }

    public function isDisponiblePourRdv(Rdv $rdv): bool
    {
        return $this->details()
            ->where('rdv_uuid', $rdv->uuid_rdvs)
            ->where('status', 'traite')
            ->exists()
            && $this->status === 'cloture';
    }

    /**
     * Vérifier si une date est dans une période clôturée
     */
    public static function isDateCloturee($date): bool
    {
        return static::where('status', 'transfere')->where('periode_1', '<=', $date)
            ->where('periode_2', '>=', $date)
            ->exists();
    }

    /**
     * Vérifier si une période est clôturée
     */
    public static function isPeriodeCloturee($dateDebut, $dateFin): bool
    {
        return static::where('status', 'transfere')->where(function ($query) use ($dateDebut, $dateFin) {
                $query->whereBetween('periode_1', [$dateDebut, $dateFin])
                      ->orWhereBetween('periode_2', [$dateDebut, $dateFin])
                      ->orWhere(function ($q) use ($dateDebut, $dateFin) {
                          $q->where('periode_1', '<=', $dateDebut)
                            ->where('periode_2', '>=', $dateFin);
                      });
            })
            ->exists();
    }

    /**
     * Scope pour les bordereaux clôturés
     */
    public function scopeCloture($query)
    {
        return $query->where('status', 'cloture');
    }

    public function scopeValide($query)
    {
        return $query->where('status', 'transfere');
    }
}