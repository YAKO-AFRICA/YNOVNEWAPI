<?php

namespace App\Models\Api\Ynov;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Métadonnées d'une demande de signature.
 *
 * Ne contient NI le document NI la signature — uniquement l'URL du document
 * et l'état du token.
 */
class SignatureRequest extends Model
{
    use HasFactory;

    public const DELIVERY_PENDING   = 'pending';
    public const DELIVERY_DELIVERED = 'delivered';
    public const DELIVERY_FAILED    = 'failed';

    protected $table = 'signature_requests';

    protected $primaryKey = 'uuid_signature_request';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'uuid_signature_request',
        'token',
        'document_url',
        'document_description',
        'webhook_url',
        'api_key',
        'expires_at',
        'is_used',
        'signed_at',
        'delivery_status',
        'delivered_at',
        'metadata',
    ];

    protected $hidden = [
        'api_key',
        'webhook_url',
        'token',
    ];

    protected $casts = [
        'expires_at'   => 'datetime',
        'signed_at'    => 'datetime',
        'delivered_at' => 'datetime',
        'is_used'      => 'boolean',
        'metadata'     => 'array',
        // Chiffré au repos avec APP_KEY (voir la migration pour les lignes existantes)
        'api_key'      => 'encrypted',
    ];

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isValid(): bool
    {
        return !$this->is_used && !$this->isExpired();
    }

    /**
     * Consomme (verrouille) le token le temps du traitement.
     * N'accepte AUCUN argument : la signature n'est jamais écrite en base.
     */
    public function markAsUsed(): self
    {
        $this->forceFill([
            'is_used'   => true,
            'signed_at' => now(),
        ])->save();

        return $this;
    }
}