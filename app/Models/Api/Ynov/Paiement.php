<?php

namespace App\Models\Api\Ynov;

use App\Models\Api\Ynov\Facture;
use App\Models\Api\Ynov\parameter\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Paiement extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'paiements';

    protected $fillable = [
        'uuid_paiement',
        'payment_code',
        'command_number',
        'amount',
        'payment_phone',
        'payment_mode',
        'payment_token',
        'payment_status',
        'payment_validation_date',
        'paid_sum',
        'paid_amount',
        'status',
        'payment_type',
        'reglement_source',
        'id_contrat',
        'facture_count',
        'paid_at',
        'cancelled_at',
        'migrated_at',
        'payer_email',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_sum' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'payment_validation_date' => 'datetime',
        'paid_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'migrated_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Types de paiement
    public const TYPES = [
        'firstPayment' => 'Premier paiement',
        'earlyPayment' => 'Paiement anticipé',
        'recoveryPrime' => 'Régularisation de primes',
    ];

    // Statuts internes
    public const STATUS = [
        'pending' => 'En attente',
        'paid' => 'Payé',
        'error' => 'Erreur',
        'cancelled' => 'Annulé',
        'migrate' => 'Migré',
    ];

    // Statuts de paiement (API)
    public const PAYMENT_STATUS = [
        'pending' => 'En attente',
        'success' => 'Succès',
        'error' => 'Erreur',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->uuid_paiement ??= (string) Str::uuid();
        });
    }

    /**
     * Relation avec les factures
     */
    public function factures()
    {
        return $this->hasMany(Facture::class, 'payment_uuid', 'uuid_paiement');
    }

    /**
     * Relation avec le contrat
     */
    // public function contrat()
    // {
    //     return $this->belongsTo(Contrat::class, 'id_contrat', 'id');
    // }

    /**
     * Relation avec l'utilisateur créateur
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by', 'uuid_user');
    }

    /**
     * Vérifier si le paiement est validé
     */
    public function isPaid(): bool
    {
        return $this->status === 'paid' && $this->payment_status === 'success';
    }

    /**
     * Vérifier si le paiement est en attente
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Vérifier si le paiement est annulé
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Marquer le paiement comme payé
     */
    public function markAsPaid(array $data = []): self
    {
        $this->update([
            'status' => 'paid',
            'payment_status' => 'success',
            'paid_at' => now(),
            'paid_amount' => $data['paid_amount'] ?? $this->amount,
            'paid_sum' => $data['paid_sum'] ?? $this->amount,
            'payment_validation_date' => now(),
            ...$data,
        ]);

        return $this;
    }

    /**
     * Marquer le paiement comme annulé
     */
    public function markAsCancelled(string $reason = null): self
    {
        $this->update([
            'status' => 'cancelled',
            'payment_status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        return $this;
    }

    /**
     * Obtenir le libellé du type
     */
    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->payment_type] ?? $this->payment_type;
    }

    /**
     * Obtenir le libellé du statut
     */
    public function getStatusLabelAttribute(): string
    {
        return self::STATUS[$this->status] ?? $this->status;
    }

    /**
     * Scope pour les paiements payés
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    /**
     * Scope pour les paiements en attente
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope pour un type spécifique
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('payment_type', $type);
    }

    /**
     * Scope pour un contrat spécifique
     */
    public function scopeForContrat($query, int $contratId)
    {
        return $query->where('id_contrat', $contratId);
    }
}