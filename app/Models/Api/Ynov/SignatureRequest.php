<?php

namespace App\Models\Api\Ynov;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modèle SignatureRequest
 * 
 * Ce modèle stocke les MÉTADONNÉES des demandes de signature uniquement.
 * IMPORTANT : Il ne stocke PAS le document ni la signature finale.
 * 
 * Rôle de ce modèle :
 * - Stocker les références aux documents (URL uniquement)
 * - Gérer les tokens Sanctum pour les liens de signature
 * - Traçabilité des demandes de signature (UUID, dates, statut)
 * - Stockage temporaire de la signature en base64 pour audit uniquement
 * 
 * La signature finale est gérée par l'application hôte via le webhook.
 */
class SignatureRequest extends Model
{
    use HasFactory;

    protected $table = 'signature_requests';
    
    protected $fillable = [
        'uuid_signature_request',      // UUID unique de la demande
        'token',                       // Token Sanctum (lien de signature)
        'document_url',                // URL du document (pas le contenu)
        'document_description',        // Description du document
        'webhook_url',                 // URL du webhook de l'app hôte
        'api_key',                     // Secret partagé pour auth webhook
        'expires_at',                  // Date d'expiration du token
        'signed_at',                   // Date de signature
        'is_used',                     // Statut d'utilisation (usage unique)
        'signature_data',              // Signature en base64 (audit uniquement)
        'metadata',                    // Métadonnées additionnelles
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'signed_at' => 'datetime',
        'is_used' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Vérifier si la requête de signature est expirée
     * 
     * @return bool True si le token est expiré
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Vérifier si la requête de signature est valide
     * Valide si : non utilisée ET non expirée
     * 
     * @return bool True si la requête est valide
     */
    public function isValid(): bool
    {
        return !$this->is_used && !$this->isExpired();
    }

    /**
     * Marquer la requête comme utilisée
     * Appelé après signature réussie
     * 
     * @param string|null $signatureData Signature en base64 (pour audit)
     * @return bool True si la mise à jour a réussi
     */
    public function markAsUsed(string $signatureData = null): bool
    {
        $this->is_used = true;
        $this->signed_at = now();
        // if ($signatureData) {
        //     // Stockage pour audit uniquement
        //     // La signature finale est gérée par l'app hôte
        //     $this->signature_data = $signatureData;
        // }
        return $this->save();
    }
}