<?php

namespace App\Models\Api\Ynov;

use App\Models\Api\Ynov\parameter\Agence;
use App\Models\Api\Ynov\parameter\MotifTraitement;
use App\Models\Api\Ynov\parameter\TypePrestation;
use App\Models\Api\Ynov\parameter\User;
use App\Models\Api\Ynov\Prestation;
use App\Models\Api\Ynov\UserContrat;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Rdv extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'rdvs';

    protected $fillable = [
        'uuid_rdvs',
        'code',
        'client_uuid',
        'id_contrat',
        'motif_rdv',
        'demandeur',
        'date_rdv_souhaiter',
        'date_rdv_effective',
        'agence_souhaiter_uuid',
        'agence_effective_uuid',
        'date_transmission',
        'transmis_par',
        'gestionnaire_uuid',
        'date_traitement',
        'motif_traitement',
        'observation',
        'status',
        'is_permitted',
        'is_present',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'date_rdv' => 'date',
        'date_rdv_souhaiter' => 'datetime',
        'date_rdv_effective' => 'datetime',
        'date_transmission' => 'datetime',
        'date_traitement' => 'datetime',
        'motif_traitement' => 'array',
        'is_permitted' => 'boolean',
        'is_present' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Statuts disponibles
    public const STATUS = [
        'en_attente' => 'En attente',
        'transmis' => 'Transmis',
        'traite' => 'Traité',
        'annule' => 'Annulé',
        'rejete' => 'Rejeté',
        'reporte' => 'Reporté',
        'expire' => 'Expiré',
    ];

    // Statuts qui ne permettent pas l'assignation automatique
    public const STATUS_NON_ASSIGNABLE = ['annule', 'rejete', 'expire', 'traite'];
    
    // Statuts qui ne permettent pas le traitement
    public const STATUS_NON_TRAITABLE = ['annule', 'rejete', 'expire', 'traite'];


    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->uuid_rdvs ??= (string) Str::uuid();
        });
    }

    /**
     * Relation avec le client
     */
    public function client()
    {
        return $this->belongsTo(User::class, 'client_uuid', 'uuid_user');
    }

    public function contrat()
    {
        return $this->belongsTo(UserContrat::class, 'id_contrat', 'contrat_id');
    }

    /**
     * Relation avec le motif (type de prestation)
     */
    public function motif()
    {
        return $this->belongsTo(TypePrestation::class, 'motif_rdv', 'uuid_type_prestation');
    }

    /**
     * Récupérer les motifs de traitement détaillés (avec les objets MotifTraitement)
     * Gère à la fois les UUIDs et les motifs automatiques (strings)
     */
    public function getMotifsTraitementDetails(): array
    {
        $result = [];
        
        $motifs = $this->getMotifsTraitement();
        
        foreach ($motifs as $type => $values) {
            if (!is_array($values)) {
                continue;
            }

            $motifsDetails = [];
            foreach ($values as $value) {
                // Si c'est un UUID valide, rechercher dans la base
                if ($this->isValidUuid($value)) {
                    $motif = MotifTraitement::where('uuid_motif_traitements', $value)
                        ->where('status', 'actif')
                        ->first();
                    
                    if ($motif) {
                        $motifsDetails[] = [
                            'uuid_motif_traitements' => $motif->uuid_motif_traitements,
                            'libelle' => $motif->libelle,
                            'type' => $motif->type,
                            'status' => $motif->status,
                            'module' => $motif->module,
                            'is_automatic' => false,
                        ];
                    }
                } else {
                    // Si c'est une string (motif automatique), créer un objet temporaire
                    $motifsDetails[] = [
                        'uuid_motif_traitements' => null,
                        'libelle' => $value,
                        'type' => [$type],
                        'status' => 'actif',
                        'module' => ['rdvs'],
                        'is_automatic' => true,
                    ];
                }
            }

            if (!empty($motifsDetails)) {
                $result[$type] = $motifsDetails;
            }
        }

        return $result;
    }

    public function prestation()
    {
        return $this->hasOne(Prestation::class, 'rdv_uuid', 'uuid_rdvs');
    }

    /**
     * Vérifier si une chaîne est un UUID valide
     */
    private function isValidUuid(string $value): bool
    {
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value) === 1;
    }

    /**
     * Relation avec l'agence souhaitée
     */
    public function agenceSouhaitee()
    {
        return $this->belongsTo(Agence::class, 'agence_souhaiter_uuid', 'uuid_agence');
    }

    /**
     * Relation avec l'agence effective
     */
    public function agenceEffective()
    {
        return $this->belongsTo(Agence::class, 'agence_effective_uuid', 'uuid_agence');
    }

    /**
     * Relation avec le gestionnaire
     */
    public function gestionnaire()
    {
        return $this->belongsTo(User::class, 'gestionnaire_uuid', 'uuid_user');
    }

    /**
     * Un rendez-vous transmis par qui est un utilisateur ou système
     */
    public function transmisParUser()
    {
        // Si transmis_par est 'system', retourner null pour éviter les erreurs
        if ($this->transmis_par === 'system') {
            return null;
        }
        return $this->belongsTo(User::class, 'transmis_par', 'uuid_user');
    }

    /**
     * Un rendez-vous a au plus un détail de bordereau
     */
    public function detailBordereau()
    {
        return $this->hasOne(DetailBordereauRdv::class, 'rdv_uuid', 'uuid_rdvs');
    }

    /**
     * Récupérer les motifs de traitement du RDV
     * @return array Les motifs de traitement organisés par type
     */
    public function getMotifsTraitement(): array
    {
        $motifs = $this->motif_traitement ?? [];
        
        // Si motif_traitement est une chaîne (JSON mal formé), essayer de la décoder
        if (is_string($motifs)) {
            try {
                $decoded = json_decode($motifs, true);
                if (is_array($decoded)) {
                    $motifs = $decoded;
                } else {
                    $motifs = [];
                }
            } catch (\Exception $e) {
                $motifs = [];
            }
        }
        
        // S'assurer que c'est un tableau
        if (!is_array($motifs)) {
            $motifs = [];
        }
        
        return $motifs;
    }

    /**
     * Récupérer les motifs de traitement d'un type spécifique
     * @param string $type Type de motif (traitement, report, rejet, annulation, expiration, reassignation)
     * @return array Les UUID des motifs du type spécifié
     */
    public function getMotifsByType(string $type): array
    {
        $motifs = $this->motif_traitement ?? [];
        
        // Si motif_traitement est une chaîne (JSON mal formé), essayer de la décoder
        if (is_string($motifs)) {
            try {
                $decoded = json_decode($motifs, true);
                if (is_array($decoded)) {
                    $motifs = $decoded;
                } else {
                    $motifs = [];
                }
            } catch (\Exception $e) {
                $motifs = [];
            }
        }
        
        // S'assurer que c'est un tableau
        if (!is_array($motifs)) {
            $motifs = [];
        }
        
        // Récupérer les motifs du type spécifique
        $typeMotifs = $motifs[$type] ?? [];
        
        // S'assurer que c'est un tableau
        if (!is_array($typeMotifs)) {
            $typeMotifs = [];
        }
        
        return $typeMotifs;
    }

    /**
     * Vérifier si le RDV a des motifs de traitement d'un type spécifique
     * @param string $type Type de motif à vérifier
     * @return bool
     */
    public function hasMotifsType(string $type): bool
    {
        return !empty($this->motif_traitement[$type] ?? []);
    }

    /**
     * Récupérer tous les UUID de motifs de traitement (tous types confondus)
     * @return array
     */
    public function getAllMotifUuids(): array
    {
        $allMotifs = [];
        foreach (($this->motif_traitement ?? []) as $type => $uuids) {
            if (is_array($uuids)) {
                $allMotifs = array_merge($allMotifs, $uuids);
            }
        }
        return array_unique($allMotifs);
    }

    /**
     * Vérifier si le rendez-vous est transmis
     */
    public function isTransmitted(): bool
    {
        return $this->status === 'transmis';
    }

    /**
     * Vérifier si le rendez-vous est en attente
     */
    public function isPending(): bool
    {
        return $this->status === 'en_attente';
    }

    /**
     * Vérifier si le rendez-vous est annulé
     */
    public function isCancelled(): bool
    {
        return $this->status === 'annule';
    }

    /**
     * Vérifier si le rendez-vous est rejeté
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejete';
    }

    /**
     * Vérifier si le rendez-vous a expiré avant son traitement
    */
    public function isExpired(): bool
    {
        return $this->status === 'expire' && $this->date_rdv_effective->isPast();
    }

    /**
     * Vérifier si le rendez-vous peut être pris en compte
     */
    public function isValidForNewRdv(): bool
    {
        return in_array($this->status, ['rejete', 'annule', 'traite']);
    }

    /**
     * Confirmer le rendez-vous
     */
    public function confirm(): self
    {
        $this->update([
            'status' => 'transmis',
        ]);
        return $this;
    }

    /**
     * Rejeter le rendez-vous
     */
    public function reject(string $motif = null): self
    {
        $this->update([
            'status' => 'rejete',
            'is_permitted' => false,
            'motif_traitement' => ['rejet' => $motif],
        ]);
        return $this;
    }

    /**
     * Annuler le rendez-vous
     */
    public function cancel(string $motif = null): self
    {
        $this->update([
            'status' => 'annule',
            'motif_traitement' => ['annulation' => $motif],
        ]);
        return $this;
    }

    /**
     * Scope pour les rendez-vous actifs
     */
    public function scopeActive($query)
    {
        return $query->whereNotIn('status', ['annule', 'rejete', 'traite']);
    }

    /**
     * Scope pour les rendez-vous confirmés
     */
    public function scopeConfirmed($query)
    {
        return $query->where('status', 'transmis');
    }

    /**
     * Scope pour un client
     */
    public function scopeForClient($query, string $clientUuid)
    {
        return $query->where('client_uuid', $clientUuid);
    }

    /**
     * Scope pour un contrat
     */
    public function scopeForContrat($query, int $contratId)
    {
        return $query->where('id_contrat', $contratId);
    }

    /**
     * Scope pour une date
     */
    public function scopeForDate($query, $date)
    {
        return $query->whereDate('date_rdv_souhaiter', $date);
    }

    /**
     * Scope pour une agence
     */
    public function scopeForAgence($query, string $agenceUuid)
    {
        return $query->where('agence_souhaiter_uuid', $agenceUuid);
    }

    /**
     * Scope pour les rendez-vous récents (30 jours)
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope pour les rendez-vous non traités (annulés/rejetés)
     */
    public function scopeNotInvalid($query)
    {
        return $query->whereNotIn('status', ['annule', 'rejete']);
    }
}
