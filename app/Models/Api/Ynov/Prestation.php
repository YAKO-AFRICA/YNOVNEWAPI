<?php

namespace App\Models\Api\Ynov;

use App\Models\Api\Ynov\Esouscription\Document;
use App\Models\Api\Ynov\parameter\Partner;
use App\Models\Api\Ynov\parameter\TypePrestation;
use App\Models\Api\Ynov\parameter\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Prestation extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'prestations';

    protected $primaryKey = 'uuid_prestation';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'uuid_prestation',
        'client_uuid',
        'code',
        'id_contrat',
        'type_prestation_uuid',
        'rdv_uuid',
        'notes',
        'montant',
        'mode_paiement',
        'operateur_mobile',
        'tel_paiement_1',
        'tel_paiement_2',
        'code_banque',
        'code_guichet',
        'numero_compte',
        'cle_rib',
        'ville_declaration',
        'partner_uuid',
        'gestionnaire_uuid',
        'date_transmission',
        'traiter_par',
        'date_traitement',
        'status',
        'is_migrated',
        'migration_date',
        'motif_traitement',
        'observation',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'id_contrat' => 'integer',
        'montant' => 'float',
        'date_transmission' => 'datetime',
        'date_traitement' => 'datetime',
        'migration_date' => 'datetime',
        'is_migrated' => 'boolean',
        'motif_traitement' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $prestation) {
            $prestation->uuid_prestation ??= (string) Str::uuid();
        });
    }

     public function getPrestationStatusLabel(): ?string
    {
        return $this->status ? match ($this->status) {
                'inacheve' => 'Inachevée',
                'en_attente' => 'En attente',
                'transmis' => 'Transmise',
                'accepte' => 'Acceptée',
                'rejete' => 'Rejetée',
                'annule' => 'Annulée',
                default => $this->status,
            }
            : null;
    }
    public function client()
    {
        return $this->belongsTo(User::class, 'client_uuid', 'uuid_user');
    }

    public function typePrestation()
    {
        return $this->belongsTo(TypePrestation::class, 'type_prestation_uuid', 'uuid_type_prestation');
    }

    public function rdv()
    {
        return $this->belongsTo(Rdv::class, 'rdv_uuid', 'uuid_rdvs');
    }

    public function partner()
    {
        return $this->belongsTo(Partner::class, 'partner_uuid', 'uuid_partner');
    }

    public function gestionnaire()
    {
        return $this->belongsTo(User::class, 'gestionnaire_uuid', 'uuid_user');
    }

    public function traiterPar()
    {
        return $this->belongsTo(User::class, 'traiter_par', 'uuid_user');
    }

    public function documents()
    {
        return $this->hasMany(Document::class, 'reference_uuid', 'uuid_prestation')
            ->where('source', 'E-PRESTATION')
            ->orderByDesc('created_at');
    }

    public function scopeActive($query)
    {
        return $query->where('status', '!=', 'annule');
    }

    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('code', 'like', "%{$search}%")
                ->orWhere('notes', 'like', "%{$search}%")
                ->orWhere('observation', 'like', "%{$search}%")
                ->orWhere('ville_declaration', 'like', "%{$search}%");
        });
    }
}
