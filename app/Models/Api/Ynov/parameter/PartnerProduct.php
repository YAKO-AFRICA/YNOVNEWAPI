<?php

namespace App\Models\Api\Ynov\parameter;

use App\Models\Api\Ynov\parameter\Partner;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PartnerProduct extends Model
{
    use HasFactory;

    protected $table = 'partner_products';

    protected $connection = 'mysql';

    protected $primaryKey = 'uuid';

    public $incrementing = false;

    protected $fillable = [
        'uuid',
        'partner_uuid',
        'product_uuid',
        'formule_uuid',
        'formule_key',
        'frais_adhesion',
        'tag',
        'popular',
        'display_name',
        'description',
        'avantage',
        'etat',
    ];

    protected $casts = [
        'popular' => 'boolean',
    ];

    public function partner()
    {
        return $this->belongsTo(Partner::class, 'partner_uuid', 'uuid_partner');
    }
}
