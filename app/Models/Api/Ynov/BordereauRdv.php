<?php
// app/Models/Api/Ynov/parameter/BordereauRdv.php

namespace App\Models\Api\Ynov;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class BordereauRdv extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'bordereau_rdvs';

    protected $fillable = [
        'uuid_bordereau_rdv',
        'reference',
        'periode_1',
        'periode_2',
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

    /**
     * Vérifier si une date est dans une période clôturée
     */
    public static function isDateCloturee($date): bool
    {
        return static::where('periode_1', '<=', $date)
            ->where('periode_2', '>=', $date)
            ->exists();
    }

    /**
     * Vérifier si une période est clôturée
     */
    public static function isPeriodeCloturee($dateDebut, $dateFin): bool
    {
        return static::where(function ($query) use ($dateDebut, $dateFin) {
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

    /**
     * Scope pour les bordereaux valides
     */
    public function scopeValide($query)
    {
        return $query->where('status', 'valide');
    }
}