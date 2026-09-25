<?php

namespace App\Models\Api\Ynov\Esouscription;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContratActeur extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $connection = 'mysql';

    protected $table = 'contrat_acteurs';

    public $timestamps = true;

    protected $primaryKey = 'uuid_contrat_acteur';

    protected $fillable = [
        'uuid_contrat_acteur',
        'contrat_uuid',
        'acteur_uuid',
        'type_acteur',
        'type_beneficiaire',
        'integration_key',
        'created_by',
        'updated_by',
        'deleted_by',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];
}
