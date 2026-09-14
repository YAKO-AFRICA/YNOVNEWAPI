<?php

namespace App\Models\Api\Ynov;

use App\Models\Api\Ynov\parameter\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class DetailBordereauRdv extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'detail_bordereau_rdvs';

    protected $fillable = [
        'uuid_detail_bordereau_rdv',
        'bordereau_rdv_uuid',
        'rdv_uuid',
        'date_effet',
        'date_echeance',
        'duree_contrat',
        'type_operation',
        'produit',
        'cumul_rachats_partiels',
        'cumul_avances',
        'provision_nette',
        'valeur_rachat',
        'valeur_max_rachat',
        'valeur_max_avance',
        'montant_transformation',
        'garantie_surete',
        'conservation_capital',
        'observation',
        'soumis_a_gestionnaire_prestation_uuid',
        'status', // en attente, soumis, traite
        'created_by',
    ];

    protected $casts = [
        'date_effet' => 'date',
        'date_echeance' => 'date',
        'cumul_rachats_partiels' => 'decimal:2',
        'cumul_avances' => 'decimal:2',
        'provision_nette' => 'decimal:2',
        'valeur_rachat' => 'decimal:2',
        'valeur_max_rachat' => 'decimal:2',
        'valeur_max_avance' => 'decimal:2',
        'montant_transformation' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->uuid_detail_bordereau_rdv ??= (string) Str::uuid();
        });
    }

    public function bordereauRdv(): BelongsTo
    {
        return $this->belongsTo(BordereauRdv::class, 'bordereau_rdv_uuid', 'uuid_bordereau_rdv');
    }

    public function rdv(): BelongsTo
    {
        return $this->belongsTo(Rdv::class, 'rdv_uuid', 'uuid_rdvs');
    }

    public function createur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'uuid_user');
    }

    public function soumisAgestionnairePrestation(): BelongsTo
    {
        return $this->belongsTo(User::class, 'soumis_a_gestionnaire_prestation_uuid', 'uuid_user');
    }


}
