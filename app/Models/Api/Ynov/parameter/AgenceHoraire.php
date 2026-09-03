<?php
// app/Models/Api/Ynov/parameter/AgenceHoraire.php
namespace App\Models\Api\Ynov\parameter;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

// class AgenceHoraire extends Model
// {
//     protected $table = 'agence_horaires';
    
//     protected $fillable = [
//         'uuid_horaire',
//         'agence_uuid',
//         'jour',
//         'heure_ouverture',
//         'heure_fermeture',
//         'heure_ouverture_midi',
//         'heure_fermeture_midi',
//         'ferme',
//         'commentaire',
//         'created_by',
//         'updated_by',
//     ];
    
//     protected $casts = [
//         'ferme' => 'boolean',
//         'heure_ouverture' => 'datetime:H:i',
//         'heure_fermeture' => 'datetime:H:i',
//         'heure_ouverture_midi' => 'datetime:H:i',
//         'heure_fermeture_midi' => 'datetime:H:i',
//     ];
    
//     protected static function booted(): void
//     {
//         static::creating(fn (self $model) => $model->uuid_horaire ??= (string) Str::uuid());
//     }
    
//     /**
//      * Relation avec l'agence
//      */
//     public function agence(): BelongsTo
//     {
//         return $this->belongsTo(Agence::class, 'agence_uuid', 'uuid_agence');
//     }
    
//     /**
//      * Vérifier si l'agence est ouverte à une heure donnée
//      */
//     public function isOpenAt(string $time = null): bool
//     {
//         if ($this->ferme) {
//             return false;
//         }
        
//         $time = $time ?? now()->format('H:i');
//         $heure = \Carbon\Carbon::parse($time);
//         $ouverture = \Carbon\Carbon::parse($this->heure_ouverture);
//         $fermeture = \Carbon\Carbon::parse($this->heure_fermeture);
        
//         // Si pas de pause midi
//         if (!$this->heure_ouverture_midi || !$this->heure_fermeture_midi) {
//             return $heure->between($ouverture, $fermeture);
//         }
        
//         // Avec pause midi
//         $ouvertureMidi = \Carbon\Carbon::parse($this->heure_ouverture_midi);
//         $fermetureMidi = \Carbon\Carbon::parse($this->heure_fermeture_midi);
        
//         return ($heure->between($ouverture, $ouvertureMidi) || 
//                 $heure->between($fermetureMidi, $fermeture));
//     }
    
//     /**
//      * Obtenir la plage horaire formatée
//      */
//     public function getPlageHoraireAttribute(): string
//     {
//         if ($this->ferme) {
//             return 'Fermé';
//         }
        
//         $ouverture = $this->heure_ouverture?->format('H:i');
//         $fermeture = $this->heure_fermeture?->format('H:i');
        
//         if (!$ouverture || !$fermeture) {
//             return 'Horaires non définis';
//         }
        
//         // Avec pause midi
//         if ($this->heure_ouverture_midi && $this->heure_fermeture_midi) {
//             $ouvertureMidi = $this->heure_ouverture_midi->format('H:i');
//             $fermetureMidi = $this->heure_fermeture_midi->format('H:i');
//             return "{$ouverture} - {$ouvertureMidi} / {$fermetureMidi} - {$fermeture}";
//         }
        
//         return "{$ouverture} - {$fermeture}";
//     }
// }

class AgenceHoraire extends Model
{
    protected $table = 'agence_horaires';
    
    protected $fillable = [
        'uuid_horaire',
        'agence_uuid',
        'jour',
        'heure_ouverture',
        'heure_fermeture',
        'heure_ouverture_midi',
        'heure_fermeture_midi',
        'ferme',
        'commentaire',
        'rendez_vous_actif',
        'capacite_rendez_vous',
        // 'heure_debut_rdv',
        // 'heure_fin_rdv',
        // 'duree_rdv_minutes',
        // 'plages_rdv',
        'created_by',
        'updated_by',
    ];
    
    protected $casts = [
        'ferme' => 'boolean',
        'rendez_vous_actif' => 'boolean',
        'heure_ouverture' => 'datetime:H:i',
        'heure_fermeture' => 'datetime:H:i',
        'heure_ouverture_midi' => 'datetime:H:i',
        'heure_fermeture_midi' => 'datetime:H:i',
        'capacite_rendez_vous' => 'integer',
        // 'heure_debut_rdv' => 'datetime:H:i',
        // 'heure_fin_rdv' => 'datetime:H:i',
        // 'plages_rdv' => 'array',
        // 'duree_rdv_minutes' => 'integer',
    ];
    
    protected static function booted(): void
    {
        static::creating(fn (self $model) => $model->uuid_horaire ??= (string) Str::uuid());
    }
    
    /**
     * Relation avec l'agence
     */
    public function agence(): BelongsTo
    {
        return $this->belongsTo(Agence::class, 'agence_uuid', 'uuid_agence');
    }
    
    /**
     * Vérifier si l'agence est ouverte à une heure donnée
     */
    public function isOpenAt(string $time = null): bool
    {
        if ($this->ferme) {
            return false;
        }
        
        $time = $time ?? now()->format('H:i');
        $heure = \Carbon\Carbon::parse($time);
        $ouverture = \Carbon\Carbon::parse($this->heure_ouverture);
        $fermeture = \Carbon\Carbon::parse($this->heure_fermeture);
        
        if (!$this->heure_ouverture_midi || !$this->heure_fermeture_midi) {
            return $heure->between($ouverture, $fermeture);
        }
        
        $ouvertureMidi = \Carbon\Carbon::parse($this->heure_ouverture_midi);
        $fermetureMidi = \Carbon\Carbon::parse($this->heure_fermeture_midi);
        
        return ($heure->between($ouverture, $ouvertureMidi) || 
                $heure->between($fermetureMidi, $fermeture));
    }
    
    /**
     * Vérifier si l'agence reçoit sur rendez-vous ce jour
     */
    public function isRdvActif(): bool
    {
        return $this->rendez_vous_actif && !$this->ferme;
    }
    
    /**
     * Obtenir la capacité de rendez-vous pour ce jour
     */
    public function getCapaciteRdv(): int
    {
        return $this->capacite_rendez_vous ?? 0;
    }
    
    /**
     * Obtenir les plages horaires de rendez-vous
     */
    // public function getPlagesRdv(): array
    // {
    //     if ($this->plages_rdv) {
    //         return $this->plages_rdv;
    //     }
        
    //     if ($this->heure_debut_rdv && $this->heure_fin_rdv) {
    //         return [
    //             [
    //                 'debut' => $this->heure_debut_rdv->format('H:i'),
    //                 'fin' => $this->heure_fin_rdv->format('H:i'),
    //             ]
    //         ];
    //     }
        
    //     return [];
    // }
    
    /**
     * Obtenir la plage horaire formatée
     */
    public function getPlageHoraireAttribute(): string
    {
        if ($this->ferme) {
            return 'Fermé';
        }
        
        $ouverture = $this->heure_ouverture?->format('H:i');
        $fermeture = $this->heure_fermeture?->format('H:i');
        
        if (!$ouverture || !$fermeture) {
            return 'Horaires non définis';
        }
        
        if ($this->heure_ouverture_midi && $this->heure_fermeture_midi) {
            $ouvertureMidi = $this->heure_ouverture_midi->format('H:i');
            $fermetureMidi = $this->heure_fermeture_midi->format('H:i');
            return "{$ouverture} - {$ouvertureMidi} / {$fermetureMidi} - {$fermeture}";
        }
        
        return "{$ouverture} - {$fermeture}";
    }
    
    /**
     * Obtenir le libellé du jour
     */
    public function getJourLabelAttribute(): string
    {
        $jours = [
            'lundi' => 'Lundi',
            'mardi' => 'Mardi',
            'mercredi' => 'Mercredi',
            'jeudi' => 'Jeudi',
            'vendredi' => 'Vendredi',
            'samedi' => 'Samedi',
            'dimanche' => 'Dimanche',
        ];
        return $jours[$this->jour] ?? $this->jour;
    }
}