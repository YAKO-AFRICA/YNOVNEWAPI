<?php
// app/Models/Api/Ynov/parameter/JourFerie.php

namespace App\Models\Api\Ynov\parameter;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class JourFerie extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'jour_feries';

    protected $fillable = [
        'uuid_jour_ferie',
        'date',
        'libelle',
        'est_recurrent',
        'code',
        'description',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'date' => 'date',
        'est_recurrent' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->uuid_jour_ferie ??= (string) Str::uuid();
        });
    }

    /**
     * Vérifier si une date est un jour férié
     */
    public static function isFerie($date): bool
    {
        // S'assurer que $date est une instance de Carbon
        if (!$date instanceof Carbon) {
            $date = Carbon::parse($date);
        }
        
        $dateStr = $date->format('Y-m-d');
        $dayOfYear = $date->dayOfYear;
        
        return static::where(function ($query) use ($dateStr, $dayOfYear) {
            // Vérifier la date exacte
            $query->where('date', $dateStr)
                  // Vérifier les dates récurrentes (même jour dans l'année)
                  ->orWhere(function ($q) use ($dayOfYear) {
                      $q->where('est_recurrent', true)
                        ->whereRaw('DAYOFYEAR(date) = ?', [$dayOfYear]);
                  });
        })->exists();
    }

    /**
     * Récupérer tous les jours fériés pour une période
     */
    public static function getFeriesBetween($dateDebut, $dateFin): array
    {
        // S'assurer que les dates sont des instances de Carbon
        if (!$dateDebut instanceof Carbon) {
            $dateDebut = Carbon::parse($dateDebut);
        }
        if (!$dateFin instanceof Carbon) {
            $dateFin = Carbon::parse($dateFin);
        }
        
        $dates = [];
        $current = clone $dateDebut;
        
        while ($current <= $dateFin) {
            if (static::isFerie($current)) {
                $dates[] = $current->format('Y-m-d');
            }
            $current->addDay();
        }
        
        return $dates;
    }

    // /**
    //  * Récupérer tous les jours fériés d'une année
    //  */
    // public static function getFeriesForYear(int $year): array
    // {
    //     return static::where(function ($query) use ($year) {
    //         $query->whereYear('date', $year)
    //               ->orWhere('est_recurrent', true);
    //     })->orderBy('date')->get()->map(function ($ferie) use ($year) {
    //         if ($ferie->est_recurrent) {
    //             $date = Carbon::createFromFormat('Y-m-d', $ferie->date);
    //             $date->year($year);
    //             return [
    //                 'uuid_jour_ferie' => $ferie->uuid_jour_ferie,
    //                 'date' => $date->format('Y-m-d'),
    //                 'libelle' => $ferie->libelle,
    //                 'code' => $ferie->code,
    //                 'est_recurrent' => true,
    //             ];
    //         }
    //         return [
    //             'uuid_jour_ferie' => $ferie->uuid_jour_ferie,
    //             'date' => $ferie->date->format('Y-m-d'),
    //             'libelle' => $ferie->libelle,
    //             'code' => $ferie->code,
    //             'est_recurrent' => false,
    //         ];
    //     })->toArray();
    // }

    /**
     * Récupérer tous les jours fériés d'une année
     */
    public static function getFeriesForYear(int $year): array
    {
        return static::where(function ($query) use ($year) {
            $query->whereYear('date', $year)
                  ->orWhere('est_recurrent', true);
        })->orderBy('date')->get()->map(function ($ferie) use ($year) {
            if ($ferie->est_recurrent) {
                // Utiliser Carbon::parse pour éviter les problèmes de format
                $date = Carbon::parse($ferie->date);
                $date->year($year);
                return [
                    'uuid_jour_ferie' => $ferie->uuid_jour_ferie,
                    'date' => $date->format('Y-m-d'),
                    'libelle' => $ferie->libelle,
                    'code' => $ferie->code,
                    'est_recurrent' => true,
                ];
            }
            return [
                'uuid_jour_ferie' => $ferie->uuid_jour_ferie,
                'date' => $ferie->date->format('Y-m-d'),
                'libelle' => $ferie->libelle,
                'code' => $ferie->code,
                'est_recurrent' => false,
            ];
        })->toArray();
    }

    /**
     * Vérifier si une année a un jour férié récurrent
     */
    public static function isRecurrentFerie($date): bool
    {
        $date = $date instanceof \DateTime ? $date->format('Y-m-d') : $date;
        
        return static::where('est_recurrent', true)
            ->whereRaw('DAYOFYEAR(date) = DAYOFYEAR(?)', [$date])
            ->exists();
    }

    /**
     * Récupérer tous les jours fériés d'une année avec formatage sécurisé
     */
    public static function getFeriesForYearSafe(int $year): array
    {
        $feries = static::where(function ($query) use ($year) {
            $query->whereYear('date', $year)
                  ->orWhere('est_recurrent', true);
        })->orderBy('date')->get();

        $result = [];

        foreach ($feries as $ferie) {
            try {
                if ($ferie->est_recurrent) {
                    // Utiliser Carbon::createFromFormat avec trim pour nettoyer les espaces
                    $dateStr = trim($ferie->date);
                    $date = Carbon::createFromFormat('Y-m-d', $dateStr);
                    
                    if (!$date) {
                        // Fallback: utiliser parse
                        $date = Carbon::parse($dateStr);
                    }
                    
                    $date->year($year);
                    
                    $result[] = [
                        'uuid_jour_ferie' => $ferie->uuid_jour_ferie,
                        'date' => $date->format('Y-m-d'),
                        'libelle' => $ferie->libelle,
                        'code' => $ferie->code,
                        'est_recurrent' => true,
                    ];
                } else {
                    $result[] = [
                        'uuid_jour_ferie' => $ferie->uuid_jour_ferie,
                        'date' => $ferie->date->format('Y-m-d'),
                        'libelle' => $ferie->libelle,
                        'code' => $ferie->code,
                        'est_recurrent' => false,
                    ];
                }
            } catch (\Exception $e) {
                // Log l'erreur et continue
                Log::error('Erreur lors du traitement du jour férié', [
                    'ferie_id' => $ferie->id,
                    'date' => $ferie->date,
                    'error' => $e->getMessage(),
                ]);
                continue;
            }
        }

        return $result;
    }

    /**
     * Scope pour les jours fériés actifs
     */
    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
    }

    /**
     * Scope pour les jours fériés récurrents
     */
    public function scopeRecurrent($query)
    {
        return $query->where('est_recurrent', true);
    }

    /**
     * Scope pour les jours fériés non récurrents
     */
    public function scopeNonRecurrent($query)
    {
        return $query->where('est_recurrent', false);
    }

    /**
     * Scope pour une année donnée
     */
    public function scopeForYear($query, int $year)
    {
        return $query->whereYear('date', $year)
            ->orWhere('est_recurrent', true);
    }

    /**
     * Scope pour une période donnée
     */
    public function scopeBetween($query, $dateDebut, $dateFin)
    {
        // S'assurer que les dates sont des instances de Carbon
        if (!$dateDebut instanceof Carbon) {
            $dateDebut = Carbon::parse($dateDebut);
        }
        if (!$dateFin instanceof Carbon) {
            $dateFin = Carbon::parse($dateFin);
        }
        
        return $query->where(function ($q) use ($dateDebut, $dateFin) {
            $q->whereBetween('date', [$dateDebut, $dateFin])
              ->orWhere('est_recurrent', true);
        });
    }
}