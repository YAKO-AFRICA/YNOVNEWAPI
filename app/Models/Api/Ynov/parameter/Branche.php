<?php

namespace App\Models\Api\Ynov\parameter;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branche extends Model
{
    use HasFactory;

    protected $table = 'branches';

    protected $connection = 'mysql';

    protected $primaryKey = 'uuid_branche';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'uuid_branche',
        'IdCanalDistribution',
        'CodeCanalDistribution',
        'MonLibelle',
        'etat',
        'created_at',
        'updated_at',
    ];
}