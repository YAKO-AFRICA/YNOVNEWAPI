<?php

namespace App\Models\Api\Ynov\Esouscription;

use App\Models\Api\Ynov\Esouscription\Acteur;
use App\Models\Api\Ynov\parameter\Agence;
use App\Models\Api\Ynov\parameter\Partner;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User;

class Contrat extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Le nom de la table associée au modèle.
     *
     * @var string
     */
    protected $table = 'contrats';

    /**
     * La clé primaire associée à la table.
     *
     * @var string
     */

    public $incrementing = false; 
    protected $primaryKey = 'uuid_contrat';

    /**
     * Indique si la clé primaire est auto-incrémentée.
     *
     * @var bool
     */
    protected $keyType = 'string';

    /**
     * Le type de la clé primaire.
     *
     * @var string
     */
    // protected $keyType = 'int';

    /**
     * Les attributs qui sont assignables en masse.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        // Identifiant unique UUID du contrat
        'uuid_contrat',
        'id_contrat',

        // Informations générales du contrat
        'date_effet',
        'mode_paiement',
        'organisme',
        'duree',
        'code_periodicite',

        // Montants financiers du contrat
        'prime',
        'prime_principale',
        'sur_prime',
        'capital',
        'frais_adhesion',

        // Informations liées à la rente
        'montant_rente',
        'periodicite_rente',
        'duree_rente',

        // Informations bancaires
        'code_banque',
        'code_guichet',
        'rib',
        'numero_compte',
        'numecompte_complet',

        // Agence responsable de la souscription
        'agence_uuid',

        // Informations produit
        'code_produit',
        'libelle_produit',
        'formule_produit_code',

        // Etat d'avancement de la souscription
        'etape',

        // Migration vers le système cible
        'is_migrated',
        'migration_date',

        // Personnes ressources
        'contact_personne_nom',
        'contact_personne_mobile',
        'contact_personne_nom_2',
        'contact_personne_mobile_2',

        // Références contrat
        'id_proposition',
        'code_proposition',
        'numero_police',
        'branch_code',

        // Partenaire associé au contrat
        'partner_uuid',

        // Auteurs
        'conseiller_uuid',

        // Transmission
        'transmis_par',
        'date_transmission',

        // Annulation
        'annuler_par',
        'date_annulation',

        // Rejet
        'rejeter_par',
        'date_rejet',
        'motif_rejet',

        // Acceptation
        'acceptance_date',
        'accepted_by',

        // Paiement
        'is_paid',

        // Informations complémentaires
        'observation',
        'bulletin_num',
        'formule',
        'integration_key',

        // Traçabilité
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    /**
     * Les attributs qui doivent être castés.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date_effet'        => 'date',
        'migration_date'    => 'date',
        'date_transmission' => 'datetime',
        'date_annulation'   => 'datetime',
        'date_rejet'        => 'datetime',
        'acceptance_date'   => 'datetime',
        'prime'             => 'float',
        'prime_principale'  => 'float',
        'sur_prime'         => 'float',
        'capital'           => 'float',
        'frais_adhesion'    => 'float',
        'montant_rente'     => 'float',
        'duree'             => 'integer',
        'duree_rente'       => 'integer',
        'etape'             => 'integer',
        'is_migrated'       => 'integer',
        'is_paid'           => 'integer',
        'id_proposition'    => 'integer',
        'created_at'        => 'datetime',
        'updated_at'        => 'datetime',
        'deleted_at'        => 'datetime',
    ];

    /**
     * Les attributs qui doivent être cachés pour les tableaux.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        // les champs sensibles si nécessaire
    ];

    // =====================================================
    // RELATIONS
    // =====================================================

    /**
     * Relation avec l'agence responsable de la souscription.
     */
    public function agence()
    {
        return $this->belongsTo(Agence::class, 'agence_uuid', 'uuid_agence');
    }

    /**
     * Relation avec le partenaire associé au contrat.
     */
    public function partner()
    {
        return $this->belongsTo(Partner::class, 'partner_uuid', 'uuid_partner');
    }


    // Dans Contrat
    public function contratActeurs()
    {
        return $this->hasMany(ContratActeur::class, 'contrat_uuid', 'uuid_contrat')
                    ->with('acteur');   // eager loading imbriqué
    }

    // =====================================================
    // SCOPES
    // =====================================================

    /**
     * Scope pour les contrats non migrés.
     */
    public function scopeNonMigres($query)
    {
        return $query->where('is_migrated', 0);
    }

    /**
     * Scope pour les contrats migrés.
     */
    public function scopeMigres($query)
    {
        return $query->where('is_migrated', 1);
    }

    /**
     * Scope pour les contrats payés.
     */
    public function scopePayes($query)
    {
        return $query->where('is_paid', 1);
    }

    /**
     * Scope pour les contrats par étape.
     */
    public function scopeEtape($query, int $etape)
    {
        return $query->where('etape', $etape);
    }
}
