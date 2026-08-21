<?php

namespace App\Models\Api\Ynov;

use App\Models\Api\Ynov\parameter\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserContrat extends Model
{
    use HasFactory;

    protected $table = 'user_contrat';
     protected $fillable = [
        'uuid_user_contrat',
        'user_uuid',
        'contrat_id',
        'client_number',
        'code_produit',
        'libelle_produit',
        'code_produit_formule',
        'libelle_produit_formule',
     ];

     public function user()
     {
         return $this->belongsTo(User::class, 'user_uuid', 'uuid_user');
     }
}
