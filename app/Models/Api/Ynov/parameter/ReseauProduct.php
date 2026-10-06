<?php

namespace App\Models\Api\Ynov\parameter;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReseauProduct extends Model
{
    use HasFactory;

    protected $table = 'reseau_products';

    protected $connection = 'mysql';

    protected $primaryKey = 'uuid';

    public $incrementing = false;

    protected $fillable = [
        'uuid',
        'reseau_uuid',
        'product_uuid',
        'formule_uuid',
        'etat',
    ];

    public function reseau()
    {
        return $this->belongsTo(Reseau::class, 'reseau_uuid', 'uuid_reseau');
    }
    public function produit()
    {
        return $this->belongsTo(Produit::class, 'product_uuid', 'uuid_produit');
    }
}