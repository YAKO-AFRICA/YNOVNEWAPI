<?php

namespace App\Models\Api\Ynov;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Métadonnées d'une demande de signature.
 *
 * Ne contient NI le document NI la signature — uniquement l'URL du document
 * et l'état du token. La colonne signature_data de la table d'origine a été
 * supprimée par la migration d'adaptation : elle allait à l'encontre de la
 * règle "aucune persistance de la signature".
 */
class SignatureRequest extends Model
{
    use HasFactory;

    public const DELIVERY_PENDING   = 'pending';
    public const DELIVERY_DELIVERED = 'delivered';
    public const DELIVERY_FAILED    = 'failed';

    protected $table = 'signature_requests';

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

    /**
     * api_key ne doit jamais partir dans une réponse JSON par inadvertance.
     */
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
     * Consomme le token.
     *
     * N'accepte volontairement AUCUN argument : la signature ne doit jamais
     * être écrite en base, même « pour traçabilité ».
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