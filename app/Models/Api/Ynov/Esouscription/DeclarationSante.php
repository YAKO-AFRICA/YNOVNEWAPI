<?php

namespace App\Models\Api\Ynov\Esouscription;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DeclarationSante extends Model
{
    use HasFactory,HasUuids, SoftDeletes;

    /**
     * Le nom de la table associée au modèle.
     *
     * @var string
     */

    protected $connection = 'mysql';
    protected $table = 'declaration_santes';

    /**
     * La clé primaire du modèle.
     *
     * @var string
     */
    protected $primaryKey = 'uuid_declaration_santes';

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
        // Clés étrangères
        'contrat_uuid',
        'assure_uuid',
        
        // Données de santé
        'taille',
        'poids',
        'tension_min',
        'tension_max',
        
        // Habitudes de vie
        'tabagisme',
        'alcool',
        'sport',
        
        // Antécédents médicaux
        'accident',
        'traitement',
        'transfusion_sanguine',
        'intervention_chirurgicale',
        'prochaine_intervention_chirurgicale',
        
        // Maladies chroniques
        'diabete',
        'hypertension',
        'drepanocytose',
        'cirrhose_foie',
        'maladie_pulmonaire',
        'cancer',
        'anemie',
        'insuffisance_renale',
        'avc',
        
        // Traçabilité
        'created_by',
        'update_by',
        'deleted_by',
        'deleted_at',
        'created_at',
        'updated_at',
    ];

    /**
     * Les attributs qui doivent être typés.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'taille' => 'integer',
        'poids' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];
}


