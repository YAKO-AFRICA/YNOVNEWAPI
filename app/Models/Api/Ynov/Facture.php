<?php
// app/Models/Api/Ynov/parameter/Facture.php

namespace App\Models\Api\Ynov;

use App\Models\Api\Ynov\Paiement;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Facture extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'factures';

    protected $fillable = [
        'uuid_facture',
        'id_presentaion',
        'payment_uuid',
        'amount',
        'type_facture',
        'status',
        'paid_at',
        'cancelled_at',
        'migrated_at',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'migrated_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Types de facture
    public const TYPES = [
        'N' => 'Prime normal',
        'F' => "Frais d'adhésion",
        'P' => 'Partielle (Reste à payer)',
        'U' => 'Unique',
        'B' => 'Participation aux Bénéfices',
        'E' => 'Exceptionnelle',
        'A' => 'Avance (Remboursement de prêts)',
    ];

    // Statuts
    public const STATUS = [
        'pending' => 'En attente',
        'paid' => 'Payée',
        'error' => 'Erreur',
        'cancelled' => 'Annulée',
        'migrate' => 'Migrée',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->uuid_facture ??= (string) Str::uuid();
        });
    }

    /**
     * Relation avec le paiement
     */
    public function paiement()
    {
        return $this->belongsTo(Paiement::class, 'payment_uuid', 'uuid_paiement');
    }

    /**
     * Obtenir le libellé du type
     */
    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type_facture] ?? $this->type_facture;
    }

    /**
     * Obtenir le libellé du statut
     */
    public function getStatusLabelAttribute(): string
    {
        return self::STATUS[$this->status] ?? $this->status;
    }

    /**
     * Vérifier si la facture est payée
     */
    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    /**
     * Marquer comme payée
     */
    public function markAsPaid(): self
    {
        $this->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        return $this;
    }

    /**
     * Scope pour les factures payées
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    /**
     * Scope pour les factures en attente
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope pour un paiement spécifique
     */
    public function scopeForPayment($query, string $paymentUuid)
    {
        return $query->where('payment_uuid', $paymentUuid);
    }
}