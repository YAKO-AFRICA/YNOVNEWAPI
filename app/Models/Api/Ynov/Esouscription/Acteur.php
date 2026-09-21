<?php

namespace App\Models\Api\Ynov\Esouscription;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Acteur extends Model
{
    use HasFactory;

    use SoftDeletes;

    protected $table = 'acteurs';

    protected $connection = 'mysql';


    protected $primaryKey = 'uuid_acteur';

    public $incrementing = false;

    protected $keyType = 'string';


    protected $fillable = [
        'uuid_acteur',
        'code',

        // Informations personnelles
        'idClient',
        'civilite',
        'genre',
        'nom',
        'prenoms',
        'date_naissance',
        'lieunaissance_code',

        // Contact
        'email',
        'mobile',
        'telephone',

        // Pièce d'identité
        'numero_piece',
        'nni',
        'nature_piece',

        // Situation professionnelle et matrimoniale
        'situation_matrimoniale',
        'profession_code',
        'employeur',

        // Codes géographiques
        'lieuresidence_code',
        'pays_code',

        // Intégration
        'integration_key',

        // Traçabilité
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'date_naissance' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];



}
