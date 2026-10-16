<?php

namespace App\Models\Api\Ynov\parameter;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BanqueAgence extends Model
{
    use HasFactory;

    protected $table = 'banque_agences';

    protected $primaryKey = 'uuid_banque';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'uuid_banque',
        'code_banque',
        'code_bceao',
        'code_guichet',
        'sigle',
        'libelle',
        'localisation',
        'etat',
    ];


}
