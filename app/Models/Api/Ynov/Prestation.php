<?php

namespace App\Models\Api\Ynov;

use App\Models\Api\Ynov\Esouscription\Document;
use App\Models\Api\Ynov\parameter\MotifTraitement;
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
        $search = trim($search);

        if ($search === '') {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            // Champs directs de la prestation
            $q->where('code', 'like', "%{$search}%")
                ->orWhere('notes', 'like', "%{$search}%")
                ->orWhere('observation', 'like', "%{$search}%")
                ->orWhere('ville_declaration', 'like', "%{$search}%")
                ->orWhere('id_contrat', 'like', "%{$search}%")
                ->orWhere('montant', 'like', "%{$search}%")
                ->orWhere('mode_paiement', 'like', "%{$search}%")
                ->orWhere('operateur_mobile', 'like', "%{$search}%")
                ->orWhere('tel_paiement_1', 'like', "%{$search}%")
                ->orWhere('tel_paiement_2', 'like', "%{$search}%")
                ->orWhere('code_banque', 'like', "%{$search}%")
                ->orWhere('code_guichet', 'like', "%{$search}%")
                ->orWhere('numero_compte', 'like', "%{$search}%")
                ->orWhere('cle_rib', 'like', "%{$search}%");

            // Recherche dans le client
            $q->orWhereHas('client', function ($clientQuery) use ($search) {
                $clientQuery->where('email', 'like', "%{$search}%")
                    ->orWhere('login', 'like', "%{$search}%")
                    ->orWhereHas('details', function ($detailsQuery) use ($search) {
                        $detailsQuery->where('nom', 'like', "%{$search}%")
                            ->orWhere('prenoms', 'like', "%{$search}%")
                            ->orWhere('numero_client', 'like', "%{$search}%")
                            ->orWhere('mobile_1', 'like', "%{$search}%")
                            ->orWhere('mobile_2', 'like', "%{$search}%")
                            ->orWhere('adresse_complete', 'like', "%{$search}%")
                            ->orWhere('lieu_residence', 'like', "%{$search}%");
                    });
            });

            // Recherche dans le type de prestation
            $q->orWhereHas('typePrestation', function ($typeQuery) use ($search) {
                $typeQuery->where('code', 'like', "%{$search}%")
                    ->orWhere('libelle', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });

            // Recherche dans le gestionnaire
            $q->orWhereHas('gestionnaire', function ($gestionnaireQuery) use ($search) {
                $gestionnaireQuery->where('email', 'like', "%{$search}%")
                    ->orWhere('login', 'like', "%{$search}%")
                    ->orWhereHas('details', function ($detailsQuery) use ($search) {
                        $detailsQuery->where('nom', 'like', "%{$search}%")
                            ->orWhere('prenoms', 'like', "%{$search}%");
                    });
            });

            // Recherche dans le partenaire
            $q->orWhereHas('partner', function ($partnerQuery) use ($search) {
                $partnerQuery->where('code', 'like', "%{$search}%")
                    ->orWhere('designation', 'like', "%{$search}%")
                    ->orWhere('sigle', 'like', "%{$search}%");
            });

            // Recherche dans le RDV associé
            $q->orWhereHas('rdv', function ($rdvQuery) use ($search) {
                $rdvQuery->where('code', 'like', "%{$search}%");
            });
        });
    }

    /**
     * Récupérer les motifs de traitement de la prestation
     * @return array Les motifs de traitement organisés par type
     */
    public function getMotifsTraitement(): array
    {
        $motifs = $this->motif_traitement ?? [];

        // Si motif_traitement est une chaîne (JSON mal formé), essayer de la décoder
        if (is_string($motifs)) {
            try {
                $decoded = json_decode($motifs, true);
                if (is_array($decoded)) {
                    $motifs = $decoded;
                } else {
                    $motifs = [];
                }
            } catch (\Exception $e) {
                $motifs = [];
            }
        }

        // S'assurer que c'est un tableau
        if (!is_array($motifs)) {
            $motifs = [];
        }

        return $motifs;
    }

    /**
     * Récupérer les motifs de traitement d'un type spécifique
     * @param string $type Type de motif (traitement, rejet, annulation)
     * @return array Les UUID des motifs du type spécifié
     */
    public function getMotifsByType(string $type): array
    {
        $motifs = $this->motif_traitement ?? [];

        // Si motif_traitement est une chaîne (JSON mal formé), essayer de la décoder
        if (is_string($motifs)) {
            try {
                $decoded = json_decode($motifs, true);
                if (is_array($decoded)) {
                    $motifs = $decoded;
                } else {
                    $motifs = [];
                }
            } catch (\Exception $e) {
                $motifs = [];
            }
        }

        // S'assurer que c'est un tableau
        if (!is_array($motifs)) {
            $motifs = [];
        }

        // Récupérer les motifs du type spécifique
        $typeMotifs = $motifs[$type] ?? [];

        // S'assurer que c'est un tableau
        if (!is_array($typeMotifs)) {
            $typeMotifs = [];
        }

        return $typeMotifs;
    }

    /**
     * Vérifier si la prestation a des motifs de traitement d'un type spécifique
     * @param string $type Type de motif à vérifier
     * @return bool
     */
    public function hasMotifsType(string $type): bool
    {
        return !empty($this->motif_traitement[$type] ?? []);
    }

    /**
     * Récupérer les motifs de traitement détaillés (avec les objets MotifTraitement)
     * Gère à la fois les UUIDs et les motifs automatiques (strings)
     */
    public function getMotifsTraitementDetails(): array
    {
        $result = [];

        $motifs = $this->getMotifsTraitement();

        foreach ($motifs as $type => $values) {
            if (!is_array($values)) {
                continue;
            }

            $motifsDetails = [];
            foreach ($values as $value) {
                // Si c'est un UUID valide, rechercher dans la base
                if ($this->isValidUuid($value)) {
                    $motif = MotifTraitement::where('uuid_motif_traitements', $value)
                        ->where('status', 'actif')
                        ->first();

                    if ($motif) {
                        $motifsDetails[] = [
                            'uuid_motif_traitements' => $motif->uuid_motif_traitements,
                            'libelle' => $motif->libelle,
                            'type' => $motif->type,
                            'status' => $motif->status,
                            'module' => $motif->module,
                            'is_automatic' => false,
                        ];
                    }
                } else {
                    // Si c'est une string (motif automatique), créer un objet temporaire
                    $motifsDetails[] = [
                        'uuid_motif_traitements' => null,
                        'libelle' => $value,
                        'type' => [$type],
                        'status' => 'actif',
                        'module' => ['prestations'],
                        'is_automatic' => true,
                    ];
                }
            }

            if (!empty($motifsDetails)) {
                $result[$type] = $motifsDetails;
            }
        }

        return $result;
    }

    /**
     * Vérifier si une chaîne est un UUID valide
     */
    private function isValidUuid(string $value): bool
    {
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value) === 1;
    }
}
