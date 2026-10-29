<?php

namespace App\Models\Api\Ynov\parameter;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Filiation extends Model
{
    use HasFactory;

    protected $table = 'filiations';

    protected $fillable = [
        'uuid_filiation',
        'code_filiation',
        'libelle',
        'etat',
        'created_at',
        'updated_at',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

}
