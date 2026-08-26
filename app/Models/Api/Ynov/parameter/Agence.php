<?php
// app/Models/Api/Ynov/parameter/Agence.php
namespace App\Models\Api\Ynov\parameter;

use App\Models\Api\Ynov\parameter\AgenceHoraire;
use App\Models\Api\Ynov\parameter\Reseau;
use App\Models\Api\Ynov\parameter\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Agence extends Model
{
    use SoftDeletes, HasFactory;
    
    protected $table = 'agences';
    
    protected $fillable = [
        'uuid_agence',
        'code',
        'libelle',
        'description',
        'reseau_uuid',
        'email',
        'telephone',
        'telephone_2',
        'adresse',
        'ville',
        'quartier',
        'code_postal',
        'pays',
        'latitude',
        'longitude',
        'photo',
        'photos',
        'responsable',
        'site_web',
        'config',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];
    
    protected $casts = [
        'config' => 'array',
        'photos' => 'array',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    protected static function booted(): void
    {
        static::creating(fn (self $model) => $model->uuid_agence ??= (string) Str::uuid());
    }

    /**
     * Relation avec le réseau
     */
    public function reseau(): BelongsTo
    {
        return $this->belongsTo(Reseau::class, 'reseau_uuid', 'uuid_reseau');
    }

    /**
     * Relation avec les horaires d'ouverture
     */
    public function horaires(): HasMany
    {
        return $this->hasMany(AgenceHoraire::class, 'agence_uuid', 'uuid_agence');
    }

    /**
     * Relation Many-to-Many avec les utilisateurs
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'user_agences',
            'agence_uuid',
            'user_uuid',
            'uuid_agence',
            'uuid_user'
        )->withPivot([
            'is_primary',
            'is_active',
            'assigned_at',
            'assigned_by',
            'role_uuid',
            'custom_permissions',
            'metadata'
        ])->withTimestamps();
    }
    
    /**
     * Récupérer les horaires formatés par jour
     */
    public function getHorairesFormatted(): array
    {
        $jours = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche'];
        $result = [];
        
        // Initialiser avec des valeurs par défaut
        foreach ($jours as $jour) {
            $result[$jour] = [
                'jour' => $jour,
                'heure_ouverture' => null,
                'heure_fermeture' => null,
                'heure_ouverture_midi' => null,
                'heure_fermeture_midi' => null,
                'ferme' => false,
                'commentaire' => null,
            ];
        }
        
        // Remplir avec les données de la base
        foreach ($this->horaires as $horaire) {
            $result[$horaire->jour] = [
                'jour' => $horaire->jour,
                'heure_ouverture' => $horaire->heure_ouverture ? $horaire->heure_ouverture->format('H:i') : null,
                'heure_fermeture' => $horaire->heure_fermeture ? $horaire->heure_fermeture->format('H:i') : null,
                'heure_ouverture_midi' => $horaire->heure_ouverture_midi ? $horaire->heure_ouverture_midi->format('H:i') : null,
                'heure_fermeture_midi' => $horaire->heure_fermeture_midi ? $horaire->heure_fermeture_midi->format('H:i') : null,
                'ferme' => $horaire->ferme,
                'commentaire' => $horaire->commentaire,
            ];
        }
        
        return $result;
    }

    /**
     * Vérifier si l'agence est ouverte actuellement
     */
    public function isOpen(): bool
    {
        $now = now();
        $jourActuel = strtolower($now->locale('fr')->dayName);
        
        // Récupérer l'horaire du jour
        $horaire = $this->horaires()->where('jour', $jourActuel)->first();
        
        if (!$horaire) {
            return false;
        }
        
        // Si fermé ce jour
        if ($horaire->ferme) {
            return false;
        }
        
        $heureActuelle = $now->format('H:i');
        
        // Vérifier les horaires (avec pause midi si présente)
        $estOuvert = false;
        
        // Horaire du matin
        if ($horaire->heure_ouverture && $horaire->heure_fermeture) {
            $ouverture = $horaire->heure_ouverture->format('H:i');
            $fermeture = $horaire->heure_fermeture->format('H:i');
            
            // Si pas de pause midi définie
            if (!$horaire->heure_ouverture_midi && !$horaire->heure_fermeture_midi) {
                $estOuvert = $heureActuelle >= $ouverture && $heureActuelle <= $fermeture;
            } else {
                // Avec pause midi
                $ouvertureMidi = $horaire->heure_ouverture_midi->format('H:i');
                $fermetureMidi = $horaire->heure_fermeture_midi->format('H:i');
                
                $estOuvert = ($heureActuelle >= $ouverture && $heureActuelle < $ouvertureMidi) ||
                            ($heureActuelle >= $fermetureMidi && $heureActuelle <= $fermeture);
            }
        }
        
        return $estOuvert;
    }

    /**
     * Obtenir les horaires d'ouverture pour un jour spécifique
     */
    public function getHorairesByJour(string $jour): ?AgenceHoraire
    {
        return $this->horaires()->where('jour', $jour)->first();
    }

    /**
     * Obtenir les horaires d'ouverture pour aujourd'hui
     */
    public function getHorairesAujourdhui(): ?AgenceHoraire
    {
        $jourActuel = strtolower(now()->locale('fr')->dayName);
        return $this->getHorairesByJour($jourActuel);
    }
    
    /**
     * Récupérer les utilisateurs actifs de l'agence
     */
    public function activeUsers()
    {
        return $this->users()->wherePivot('is_active', true);
    }
    
    /**
     * Récupérer les utilisateurs principaux de l'agence
     */
    public function primaryUsers()
    {
        return $this->users()->wherePivot('is_primary', true);
    }
    
    /**
     * Obtenir l'adresse complète
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->adresse,
            $this->quartier,
            $this->ville,
            $this->code_postal,
            $this->pays,
        ]);
        
        return implode(', ', $parts);
    }
    
    /**
     * Scope pour les agences actives
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'actif');
    }
    
    /**
     * Scope pour les agences d'un réseau
     */
    public function scopeInReseau($query, string $reseauUuid)
    {
        return $query->where('reseau_uuid', $reseauUuid);
    }
    
    /**
     * Scope pour les agences d'une ville
     */
    public function scopeInVille($query, string $ville)
    {
        return $query->where('ville', 'LIKE', "%{$ville}%");
    }
    
    /**
     * Scope pour les agences d'un quartier
     */
    public function scopeInQuartier($query, string $quartier)
    {
        return $query->where('quartier', 'LIKE', "%{$quartier}%");
    }
    
    /**
     * Scope pour les agences ouvertes actuellement
     */
    public function scopeOpenNow($query)
    {
        $now = now();
        $jour = strtolower($now->locale('fr')->dayName);
        $heure = $now->format('H:i');
        
        return $query->whereHas('horaires', function ($q) use ($jour, $heure) {
            $q->where('jour', $jour)
              ->where('ferme', false)
              ->where(function ($sub) use ($heure) {
                  // Cas sans pause midi
                  $sub->where(function ($q2) use ($heure) {
                      $q2->whereNull('heure_ouverture_midi')
                         ->whereNull('heure_fermeture_midi')
                         ->where('heure_ouverture', '<=', $heure)
                         ->where('heure_fermeture', '>=', $heure);
                  })
                  // Cas avec pause midi
                  ->orWhere(function ($q2) use ($heure) {
                      $q2->whereNotNull('heure_ouverture_midi')
                         ->whereNotNull('heure_fermeture_midi')
                         ->where(function ($q3) use ($heure) {
                             // Matin
                             $q3->where('heure_ouverture', '<=', $heure)
                                ->where('heure_ouverture_midi', '>', $heure);
                         })
                         ->orWhere(function ($q3) use ($heure) {
                             // Après-midi
                             $q3->where('heure_fermeture_midi', '<=', $heure)
                                ->where('heure_fermeture', '>=', $heure);
                         });
                  });
              });
        })->where('status', 'actif');
    }
}