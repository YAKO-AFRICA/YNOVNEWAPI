<?php

namespace App\Models\Api\Ynov\Esouscription;

use App\Models\Api\Ynov\Prestation;
use App\Models\Api\Ynov\parameter\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Document extends Model
{
    use HasUuids, SoftDeletes;

    /**
     * Le nom de la table associée au modèle.
     *
     * @var string
     */
    
    protected $connection = 'mysql';
    
    protected $table = 'documents';
    /**
     * La clé primaire du modèle.
     *
     * @var string
     */
    protected $primaryKey = 'uuid_document';

    /**
     * Le type de la clé primaire.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indique si l'ID est auto-incrémenté.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * Les attributs qui sont assignables en masse.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        // Informations du document
        'uuid_document',
        'reference_uuid',
        'nom_fichier',
        'libelle',
        'source',
        'chemin',
        
        // Métadonnées
        'type_document',
        'taille_fichier',
        'mime_type',
        'statut',
        
        // Traçabilité
        'created_by',
        'update_by',
        'deleted_by',
        'updated_at',
        'deleted_at',
    ];

    /**
     * Les attributs qui doivent être typés.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'taille_fichier' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function prestation()
    {
        return $this->belongsTo(Prestation::class, 'reference_uuid', 'uuid_prestation');
    }

}
