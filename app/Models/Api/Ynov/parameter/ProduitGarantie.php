<?php

namespace App\Models\Api\Ynov\parameter;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ProduitGarantie extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'produit_garanties';

    protected $fillable = [
        'uuid_produit_garantie',
        'produit_uuid',
        'code_produit',
        'code_produit_garantie',
        'libelle',
        'est_obligatoire',
        'nature_garantie',
        'type',
        'age_min',
        'age_max',
        'duree_cotisation_min',
        'duree_cotisation_max',
        'duree_contrat_min',
        'duree_contrat_max',
        'branche',
        'description',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'est_obligatoire' => 'boolean',
        'age_min' => 'integer',
        'age_max' => 'integer',
        'duree_cotisation_min' => 'integer',
        'duree_cotisation_max' => 'integer',
        'duree_contrat_min' => 'integer',
        'duree_contrat_max' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->uuid_produit_garantie ??= (string) Str::uuid();
        });
    }

    public function produit()
    {
        return $this->belongsTo(Produit::class, 'produit_uuid', 'uuid_produit');
    }

    public function createur()
    {
        return $this->belongsTo(User::class, 'created_by', 'uuid_user');
    }

    public function modificateur()
    {
        return $this->belongsTo(User::class, 'updated_by', 'uuid_user');
    }
}
