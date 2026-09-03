<?php

namespace App\Models\Api\Ynov;

use App\Models\Api\Ynov\parameter\Agence;
use App\Models\Api\Ynov\parameter\TypePrestation;
use App\Models\Api\Ynov\parameter\User;
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
        'confirme' => 'Confirmé',
        'annule' => 'Annulé',
        'rejete' => 'Rejeté',
        'traite' => 'Traité',
        'termine' => 'Terminé',
        'reporte' => 'Reporté',
    ];

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

    /**
     * Relation avec le motif (type de prestation)
     */
    public function motif()
    {
        return $this->belongsTo(TypePrestation::class, 'motif_rdv', 'uuid_type_prestation');
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
     * Vérifier si le rendez-vous est confirmé
     */
    public function isConfirmed(): bool
    {
        return $this->status === 'confirme';
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
     * Vérifier si le rendez-vous peut être pris en compte
     */
    public function isValidForNewRdv(): bool
    {
        return in_array($this->status, ['rejete', 'annule', 'traite', 'termine']);
    }

    /**
     * Confirmer le rendez-vous
     */
    public function confirm(): self
    {
        $this->update([
            'status' => 'confirme',
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
        return $query->whereNotIn('status', ['annule', 'rejete', 'traite', 'termine']);
    }

    /**
     * Scope pour les rendez-vous confirmés
     */
    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirme');
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
