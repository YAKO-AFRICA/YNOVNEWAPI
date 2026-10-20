<?php

namespace App\Models\Api\Ynov\Esouscription;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssurerGarantie extends Model
{
    use HasFactory;

    protected $table = 'assurer_garanties';

    protected $connection = 'mysql';

    protected $primaryKey = 'uuid';

    public $incrementing = false;

    protected $fillable = [
        'uuid',
        'code',
        'uuid_garantie',
        'uuid_assurer',
        'uuid_contrat',
        'libelle',
        'capital',
        'prime',
        'type',
    ];
}

