<?php
// database/seeders/ProduitSeeder.php

namespace Database\Seeders;

use App\Models\Api\Ynov\parameter\Produit;
use App\Models\Api\Ynov\parameter\TypeProduit;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProduitSeeder extends Seeder
{
    /**
     * Mapping des types de contrat vers les codes TypeProduit
     */
    private const TYPE_CONTRAT_MAPPING = [
        'KVIE' => 'KVIE',
        'KDEC' => 'KDEC',
        'MIXTE' => 'MIXTE',
        'EPA' => 'EPA',
        'CAPI' => 'CAPI',
        'COMP' => 'COMP',
    ];

    /**
     * Liste complète des produits
     */
    private const PRODUITS = [
        // ============================================================
        // 1. PERFORMA INDIVIDUEL
        // ============================================================
        [
            'code' => 'PFA_IND',
            'libelle' => 'PERFORMA INDIVIDUEL',
            'date_creation' => '2012-03-09',
            'code_branche' => '1',
            'code_produit_nature' => 'INDIV',
            'statut' => 'COM',
            'code_groupe_assure' => 'PRF',
            'code_groupe_profil' => 'SOLO',
            'age_mini_adh' => 12,
            'age_maxi_adh' => 99,
            'table_tarification' => 'TDM100',
            'table_reglementaire' => 'TDV100',
            'table_fiscale' => 'TDM100',
            'table_comptable' => 'TDV100',
            'code_contractant' => 'DNA',
            'num_seq' => '8177',
            'delai_carrence' => 12,
            'capital_assure_pmok' => 1,
            'capital_assure_vers_excp_ok' => 0,
            'code_branche_deux' => 'IND',
            'type_contrat' => 'EPA',
            'capital' => 0,
            'code_produit_court' => 'PFIND',
            'duree_souscription_annee' => null,
            'duree_souscription_mois' => null,
            'vie_entiere' => 0,
            'duree_cotisation_ans' => null,
            'duree_cotisation_mois' => null,
            'code_marque' => null,
        ],

        // ============================================================
        // 2. YAKO Eternité 2008
        // ============================================================
        [
            'code' => 'YKE_2008',
            'libelle' => 'YAKO Eternité 2008',
            'date_creation' => '2012-04-17',
            'code_branche' => '5',
            'code_produit_nature' => 'INDIV',
            'statut' => 'COM',
            'code_groupe_assure' => 'YKE2008',
            'code_groupe_profil' => 'DUO',
            'age_mini_adh' => 12,
            'age_maxi_adh' => 99,
            'table_tarification' => 'TDM100',
            'table_reglementaire' => 'TDM100',
            'table_fiscale' => 'TDM100',
            'table_comptable' => 'TDM100',
            'code_contractant' => 'DNA',
            'num_seq' => '57670',
            'delai_carrence' => 12,
            'capital_assure_pmok' => -1,
            'capital_assure_vers_excp_ok' => -1,
            'code_branche_deux' => 'IND',
            'type_contrat' => 'MIXTE',
            'capital' => 1,
            'code_produit_court' => 'YKE',
            'duree_souscription_annee' => 99,
            'duree_souscription_mois' => null,
            'vie_entiere' => 1,
            'duree_cotisation_ans' => 5,
            'duree_cotisation_mois' => null,
            'code_marque' => 'YAKO',
        ],

        // ============================================================
        // 3. YAKO Famille 2008
        // ============================================================
        [
            'code' => 'YKF_2008',
            'libelle' => 'YAKO Famille 2008',
            'date_creation' => '2012-04-17',
            'code_branche' => '5',
            'code_produit_nature' => 'INDIV',
            'statut' => 'COM',
            'code_groupe_assure' => 'YKE2008',
            'code_groupe_profil' => 'FAM',
            'age_mini_adh' => 18,
            'age_maxi_adh' => 99,
            'table_tarification' => 'TDM100',
            'table_reglementaire' => 'TDM100',
            'table_fiscale' => 'TDM100',
            'table_comptable' => 'TDM100',
            'code_contractant' => 'DNA',
            'num_seq' => '12383',
            'delai_carrence' => 12,
            'capital_assure_pmok' => 0,
            'capital_assure_vers_excp_ok' => 0,
            'code_branche_deux' => 'IND',
            'type_contrat' => 'KDEC',
            'capital' => 1,
            'code_produit_court' => 'YKFAM',
            'duree_souscription_annee' => null,
            'duree_souscription_mois' => null,
            'vie_entiere' => 0,
            'duree_cotisation_ans' => null,
            'duree_cotisation_mois' => null,
            'code_marque' => 'YAKO',
        ],

        // ============================================================
        // 4. YAKO Solo 2008
        // ============================================================
        [
            'code' => 'YKS_2008',
            'libelle' => 'YAKO Solo 2008',
            'date_creation' => '2012-04-17',
            'code_branche' => '5',
            'code_produit_nature' => 'INDIV',
            'statut' => 'COM',
            'code_groupe_assure' => 'YKS2008',
            'code_groupe_profil' => 'DUO',
            'age_mini_adh' => 12,
            'age_maxi_adh' => 99,
            'table_tarification' => 'TDM100',
            'table_reglementaire' => 'TDM100',
            'table_fiscale' => 'TDM100',
            'table_comptable' => 'TDM100',
            'code_contractant' => 'DNA',
            'num_seq' => '13781',
            'delai_carrence' => 12,
            'capital_assure_pmok' => -1,
            'capital_assure_vers_excp_ok' => -1,
            'code_branche_deux' => 'IND',
            'type_contrat' => 'KDEC',
            'capital' => 1,
            'code_produit_court' => 'YKSOL',
            'duree_souscription_annee' => 10,
            'duree_souscription_mois' => 120,
            'vie_entiere' => 0,
            'duree_cotisation_ans' => 10,
            'duree_cotisation_mois' => 120,
            'code_marque' => null,
        ],

        // ============================================================
        // 5. CADENCE
        // ============================================================
        [
            'code' => 'CADENCE',
            'libelle' => 'CADENCE',
            'date_creation' => '2012-04-17',
            'code_branche' => null,
            'code_produit_nature' => 'INDIV',
            'statut' => 'COM',
            'code_groupe_assure' => 'YKE2008',
            'code_groupe_profil' => 'DUO',
            'age_mini_adh' => 12,
            'age_maxi_adh' => 99,
            'table_tarification' => 'TDM100',
            'table_reglementaire' => 'TDM100',
            'table_fiscale' => 'TDM100',
            'table_comptable' => 'TDM100',
            'code_contractant' => 'DNA',
            'num_seq' => '24323',
            'delai_carrence' => 12,
            'capital_assure_pmok' => 1,
            'capital_assure_vers_excp_ok' => 1,
            'code_branche_deux' => 'IND',
            'type_contrat' => 'EPA',
            'capital' => 0,
            'code_produit_court' => 'CADEN',
            'duree_souscription_annee' => null,
            'duree_souscription_mois' => null,
            'vie_entiere' => 0,
            'duree_cotisation_ans' => null,
            'duree_cotisation_mois' => null,
            'code_marque' => 'CAD',
        ],

        // ============================================================
        // 6. Plan Vert Retraite
        // ============================================================
        [
            'code' => 'PVRBNI',
            'libelle' => 'Plan Vert Retraite',
            'date_creation' => '2000-01-01',
            'code_branche' => '6',
            'code_produit_nature' => 'INDIV',
            'statut' => 'COM',
            'code_groupe_assure' => null,
            'code_groupe_profil' => null,
            'age_mini_adh' => 18,
            'age_maxi_adh' => 99,
            'table_tarification' => null,
            'table_reglementaire' => null,
            'table_fiscale' => null,
            'table_comptable' => null,
            'code_contractant' => 'LLVIE',
            'num_seq' => '46076',
            'delai_carrence' => 0,
            'capital_assure_pmok' => -1,
            'capital_assure_vers_excp_ok' => -1,
            'code_branche_deux' => 'COL',
            'type_contrat' => 'EPA',
            'capital' => 0,
            'code_produit_court' => 'PVR',
            'duree_souscription_annee' => null,
            'duree_souscription_mois' => null,
            'vie_entiere' => 0,
            'duree_cotisation_ans' => null,
            'duree_cotisation_mois' => null,
            'code_marque' => null,
        ],

        // ============================================================
        // 7. Yako Alliance 2004
        // ============================================================
        [
            'code' => 'YKL_2004',
            'libelle' => 'Yako Alliance 2004',
            'date_creation' => '2000-01-01',
            'code_branche' => '6',
            'code_produit_nature' => 'COLLF',
            'statut' => 'COM',
            'code_groupe_assure' => null,
            'code_groupe_profil' => null,
            'age_mini_adh' => 12,
            'age_maxi_adh' => 60,
            'table_tarification' => 'TDM100',
            'table_reglementaire' => 'TDM100',
            'table_fiscale' => 'TDM100',
            'table_comptable' => 'TDM100',
            'code_contractant' => null,
            'num_seq' => '2277',
            'delai_carrence' => 3,
            'capital_assure_pmok' => 1,
            'capital_assure_vers_excp_ok' => 0,
            'code_branche_deux' => 'COL',
            'type_contrat' => 'KDEC',
            'capital' => 1,
            'code_produit_court' => 'YKALL',
            'duree_souscription_annee' => null,
            'duree_souscription_mois' => null,
            'vie_entiere' => 0,
            'duree_cotisation_ans' => null,
            'duree_cotisation_mois' => null,
            'code_marque' => null,
        ],

        // ============================================================
        // 8. Yako Famille Bancassurance 2004
        // ============================================================
        [
            'code' => 'YKF_2004',
            'libelle' => 'Yako Famille Bancassurance 2004',
            'date_creation' => '2000-01-01',
            'code_branche' => '6',
            'code_produit_nature' => 'COLLF',
            'statut' => 'COM',
            'code_groupe_assure' => null,
            'code_groupe_profil' => null,
            'age_mini_adh' => 17,
            'age_maxi_adh' => 60,
            'table_tarification' => 'TDM100',
            'table_reglementaire' => 'TDM100',
            'table_fiscale' => 'TDM100',
            'table_comptable' => 'TDM100',
            'code_contractant' => null,
            'num_seq' => '1982',
            'delai_carrence' => 3,
            'capital_assure_pmok' => -1,
            'capital_assure_vers_excp_ok' => -1,
            'code_branche_deux' => 'COL',
            'type_contrat' => 'KDEC',
            'capital' => 1,
            'code_produit_court' => 'YKFMB',
            'duree_souscription_annee' => null,
            'duree_souscription_mois' => null,
            'vie_entiere' => 0,
            'duree_cotisation_ans' => null,
            'duree_cotisation_mois' => null,
            'code_marque' => null,
        ],

        // ============================================================
        // 9. Ticket Tontine
        // ============================================================
        [
            'code' => 'TTT_1995',
            'libelle' => 'Ticket Tontine',
            'date_creation' => '2000-01-01',
            'code_branche' => '5',
            'code_produit_nature' => 'INDIV',
            'statut' => 'AGREE',
            'code_groupe_assure' => null,
            'code_groupe_profil' => null,
            'age_mini_adh' => 18,
            'age_maxi_adh' => 99,
            'table_tarification' => 'TDM100',
            'table_reglementaire' => 'TDM100',
            'table_fiscale' => 'TDM100',
            'table_comptable' => 'TDM100',
            'code_contractant' => 'DNA',
            'num_seq' => '2312',
            'delai_carrence' => 0,
            'capital_assure_pmok' => -1,
            'capital_assure_vers_excp_ok' => -1,
            'code_branche_deux' => 'IND',
            'type_contrat' => 'EPA',
            'capital' => 0,
            'code_produit_court' => 'TICKT',
            'duree_souscription_annee' => null,
            'duree_souscription_mois' => null,
            'vie_entiere' => 0,
            'duree_cotisation_ans' => null,
            'duree_cotisation_mois' => null,
            'code_marque' => null,
        ],

        // ============================================================
        // 10. Yako Collectif
        // ============================================================
        [
            'code' => 'YKC_2006',
            'libelle' => 'Yako Collectif',
            'date_creation' => '2000-01-01',
            'code_branche' => '6',
            'code_produit_nature' => 'COLLF',
            'statut' => 'COM',
            'code_groupe_assure' => null,
            'code_groupe_profil' => null,
            'age_mini_adh' => 18,
            'age_maxi_adh' => 64,
            'table_tarification' => 'TDM100',
            'table_reglementaire' => 'TDM100',
            'table_fiscale' => 'TDM100',
            'table_comptable' => 'TDM100',
            'code_contractant' => null,
            'num_seq' => '1630',
            'delai_carrence' => 6,
            'capital_assure_pmok' => 0,
            'capital_assure_vers_excp_ok' => 0,
            'code_branche_deux' => 'COL',
            'type_contrat' => 'KDEC',
            'capital' => 0,
            'code_produit_court' => 'YKCOL',
            'duree_souscription_annee' => null,
            'duree_souscription_mois' => null,
            'vie_entiere' => 0,
            'duree_cotisation_ans' => null,
            'duree_cotisation_mois' => null,
            'code_marque' => null,
        ],

        // ============================================================
        // 11. PRE Retraite
        // ============================================================
        [
            'code' => 'PRR_1997',
            'libelle' => 'PRE Retraite',
            'date_creation' => '2000-01-01',
            'code_branche' => '5',
            'code_produit_nature' => 'INDIV',
            'statut' => 'AGREE',
            'code_groupe_assure' => null,
            'code_groupe_profil' => null,
            'age_mini_adh' => 18,
            'age_maxi_adh' => 99,
            'table_tarification' => 'TDM100',
            'table_reglementaire' => 'TDM100',
            'table_fiscale' => 'TDM100',
            'table_comptable' => 'TDM100',
            'code_contractant' => 'DNA',
            'num_seq' => '2911',
            'delai_carrence' => 0,
            'capital_assure_pmok' => -1,
            'capital_assure_vers_excp_ok' => -1,
            'code_branche_deux' => 'IND',
            'type_contrat' => 'EPA',
            'capital' => 0,
            'code_produit_court' => 'PRRET',
            'duree_souscription_annee' => null,
            'duree_souscription_mois' => null,
            'vie_entiere' => 0,
            'duree_cotisation_ans' => null,
            'duree_cotisation_mois' => null,
            'code_marque' => null,
        ],

        // ============================================================
        // 12. YAKO Avantage 2004
        // ============================================================
        [
            'code' => 'YKV_2004',
            'libelle' => 'YAKO Avantage 2004',
            'date_creation' => '2000-01-01',
            'code_branche' => null,
            'code_produit_nature' => 'COLLF',
            'statut' => 'COM',
            'code_groupe_assure' => null,
            'code_groupe_profil' => null,
            'age_mini_adh' => 18,
            'age_maxi_adh' => 60,
            'table_tarification' => 'TDM100',
            'table_reglementaire' => 'TDM100',
            'table_fiscale' => 'TDM100',
            'table_comptable' => 'TDM100',
            'code_contractant' => null,
            'num_seq' => '16832',
            'delai_carrence' => 3,
            'capital_assure_pmok' => -1,
            'capital_assure_vers_excp_ok' => -1,
            'code_branche_deux' => 'COL',
            'type_contrat' => 'KDEC',
            'capital' => 1,
            'code_produit_court' => 'YKAVT',
            'duree_souscription_annee' => null,
            'duree_souscription_mois' => null,
            'vie_entiere' => 0,
            'duree_cotisation_ans' => null,
            'duree_cotisation_mois' => null,
            'code_marque' => null,
        ],

        // ============================================================
        // 13. ASSUR COMPTE
        // ============================================================
        [
            'code' => 'ASSCPTBNI',
            'libelle' => 'ASSUR COMPTE',
            'date_creation' => '2000-01-01',
            'code_branche' => '6',
            'code_produit_nature' => 'COLLF',
            'statut' => 'COM',
            'code_groupe_assure' => null,
            'code_groupe_profil' => null,
            'age_mini_adh' => 18,
            'age_maxi_adh' => 65,
            'table_tarification' => null,
            'table_reglementaire' => null,
            'table_fiscale' => null,
            'table_comptable' => null,
            'code_contractant' => 'DNA',
            'num_seq' => '41285',
            'delai_carrence' => 0,
            'capital_assure_pmok' => 1,
            'capital_assure_vers_excp_ok' => 0,
            'code_branche_deux' => 'COL',
            'type_contrat' => 'KDEC',
            'capital' => 0,
            'code_produit_court' => 'ACBNI',
            'duree_souscription_annee' => null,
            'duree_souscription_mois' => null,
            'vie_entiere' => 0,
            'duree_cotisation_ans' => null,
            'duree_cotisation_mois' => null,
            'code_marque' => 'YAKO',
        ],

        // ============================================================
        // 14. Temporaire Décès à capital constant
        // ============================================================
        [
            'code' => 'TDICONST',
            'libelle' => 'Temporaire Décès à capital constant',
            'date_creation' => '2000-01-01',
            'code_branche' => '5',
            'code_produit_nature' => 'INDIV',
            'statut' => 'COM',
            'code_groupe_assure' => null,
            'code_groupe_profil' => null,
            'age_mini_adh' => 12,
            'age_maxi_adh' => 70,
            'table_tarification' => 'TDM100',
            'table_reglementaire' => 'TDM100',
            'table_fiscale' => 'TDM100',
            'table_comptable' => 'TDM100',
            'code_contractant' => 'DNA',
            'num_seq' => '7413',
            'delai_carrence' => 0,
            'capital_assure_pmok' => -1,
            'capital_assure_vers_excp_ok' => -1,
            'code_branche_deux' => 'IND',
            'type_contrat' => 'KDEC',
            'capital' => 1,
            'code_produit_court' => 'TDC',
            'duree_souscription_annee' => null,
            'duree_souscription_mois' => null,
            'vie_entiere' => 0,
            'duree_cotisation_ans' => null,
            'duree_cotisation_mois' => null,
            'code_marque' => null,
        ],

        // ============================================================
        // 15. Super Mixte
        // ============================================================
        [
            'code' => 'SUPMIXTE',
            'libelle' => 'Super Mixte',
            'date_creation' => null,
            'code_branche' => '5',
            'code_produit_nature' => 'INDIV',
            'statut' => 'COM',
            'code_groupe_assure' => null,
            'code_groupe_profil' => null,
            'age_mini_adh' => 18,
            'age_maxi_adh' => 60,
            'table_tarification' => 'TDM100',
            'table_reglementaire' => 'TDM100',
            'table_fiscale' => 'TDM100',
            'table_comptable' => 'TDM100',
            'code_contractant' => 'DNA',
            'num_seq' => null,
            'delai_carrence' => 6,
            'capital_assure_pmok' => 1,
            'capital_assure_vers_excp_ok' => 0,
            'code_branche_deux' => 'IND',
            'type_contrat' => 'EPA',
            'capital' => 0,
            'code_produit_court' => 'SUPMX',
            'duree_souscription_annee' => null,
            'duree_souscription_mois' => null,
            'vie_entiere' => 0,
            'duree_cotisation_ans' => null,
            'duree_cotisation_mois' => null,
            'code_marque' => null,
        ],

        // ============================================================
        // 16. LOYALE PENSION
        // ============================================================
        [
            'code' => 'LPENSION',
            'libelle' => 'LOYALE PENSION',
            'date_creation' => null,
            'code_branche' => '6',
            'code_produit_nature' => 'COLLO',
            'statut' => 'COM',
            'code_groupe_assure' => null,
            'code_groupe_profil' => null,
            'age_mini_adh' => 0,
            'age_maxi_adh' => 99,
            'table_tarification' => 'TDM100',
            'table_reglementaire' => 'TDM100',
            'table_fiscale' => 'TDM100',
            'table_comptable' => 'TDM100',
            'code_contractant' => null,
            'num_seq' => '2746',
            'delai_carrence' => 0,
            'capital_assure_pmok' => 0,
            'capital_assure_vers_excp_ok' => 0,
            'code_branche_deux' => 'COL',
            'type_contrat' => 'EPA',
            'capital' => 0,
            'code_produit_court' => 'LPS',
            'duree_souscription_annee' => null,
            'duree_souscription_mois' => null,
            'vie_entiere' => 0,
            'duree_cotisation_ans' => null,
            'duree_cotisation_mois' => null,
            'code_marque' => 'EMB',
        ],

        // ============================================================
        // 17. YAKO FRAIS FUNERAIRES
        // ============================================================
        [
            'code' => 'LFFUN',
            'libelle' => 'YAKO FRAIS FUNERAIRES',
            'date_creation' => null,
            'code_branche' => '6',
            'code_produit_nature' => 'COLLO',
            'statut' => 'COM',
            'code_groupe_assure' => null,
            'code_groupe_profil' => null,
            'age_mini_adh' => 18,
            'age_maxi_adh' => 65,
            'table_tarification' => 'TDM100',
            'table_reglementaire' => 'TDM100',
            'table_fiscale' => 'TDM100',
            'table_comptable' => 'TDM100',
            'code_contractant' => null,
            'num_seq' => '5046',
            'delai_carrence' => 0,
            'capital_assure_pmok' => 0,
            'capital_assure_vers_excp_ok' => 0,
            'code_branche_deux' => 'COL',
            'type_contrat' => 'KDEC',
            'capital' => 0,
            'code_produit_court' => null,
            'duree_souscription_annee' => null,
            'duree_souscription_mois' => null,
            'vie_entiere' => 0,
            'duree_cotisation_ans' => null,
            'duree_cotisation_mois' => null,
            'code_marque' => 'EMB',
        ],

        // ============================================================
        // 18. YAKO PREVOYANCE
        // ============================================================
        [
            'code' => 'LPREVO',
            'libelle' => 'YAKO PREVOYANCE',
            'date_creation' => null,
            'code_branche' => '6',
            'code_produit_nature' => 'COLLO',
            'statut' => 'COM',
            'code_groupe_assure' => null,
            'code_groupe_profil' => null,
            'age_mini_adh' => 18,
            'age_maxi_adh' => 65,
            'table_tarification' => 'TDM100',
            'table_reglementaire' => 'TDM100',
            'table_fiscale' => 'TDM100',
            'table_comptable' => 'TDM100',
            'code_contractant' => null,
            'num_seq' => '4219',
            'delai_carrence' => 0,
            'capital_assure_pmok' => 0,
            'capital_assure_vers_excp_ok' => 0,
            'code_branche_deux' => 'COL',
            'type_contrat' => 'KDEC',
            'capital' => 0,
            'code_produit_court' => null,
            'duree_souscription_annee' => null,
            'duree_souscription_mois' => null,
            'vie_entiere' => 0,
            'duree_cotisation_ans' => null,
            'duree_cotisation_mois' => null,
            'code_marque' => 'EMB',
        ],

        // ============================================================
        // 19. PREVOYANCE U-CARE
        // ============================================================
        [
            'code' => 'PREV UCARE',
            'libelle' => 'PREVOYANCE U-CARE',
            'date_creation' => null,
            'code_branche' => '6',
            'code_produit_nature' => 'COLLF',
            'statut' => 'COM',
            'code_groupe_assure' => null,
            'code_groupe_profil' => null,
            'age_mini_adh' => 18,
            'age_maxi_adh' => 99,
            'table_tarification' => 'TDM100',
            'table_reglementaire' => 'TDM100',
            'table_fiscale' => 'TDM100',
            'table_comptable' => 'TDM100',
            'code_contractant' => 'DNA',
            'num_seq' => '8643',
            'delai_carrence' => 6,
            'capital_assure_pmok' => 0,
            'capital_assure_vers_excp_ok' => 0,
            'code_branche_deux' => 'COL',
            'type_contrat' => 'KDEC',
            'capital' => 0,
            'code_produit_court' => null,
            'duree_souscription_annee' => null,
            'duree_souscription_mois' => null,
            'vie_entiere' => 0,
            'duree_cotisation_ans' => null,
            'duree_cotisation_mois' => null,
            'code_marque' => null,
        ],

        // ============================================================
        // 20. PERFORMA BNI
        // ============================================================
        [
            'code' => 'PFA_BNI',
            'libelle' => 'PERFORMA BNI',
            'date_creation' => null,
            'code_branche' => '6',
            'code_produit_nature' => 'COLLF',
            'statut' => 'COM',
            'code_groupe_assure' => null,
            'code_groupe_profil' => null,
            'age_mini_adh' => 21,
            'age_maxi_adh' => 99,
            'table_tarification' => 'TDM100',
            'table_reglementaire' => 'TDM100',
            'table_fiscale' => 'TDM100',
            'table_comptable' => 'TDM100',
            'code_contractant' => 'DNA',
            'num_seq' => '828',
            'delai_carrence' => 12,
            'capital_assure_pmok' => 1,
            'capital_assure_vers_excp_ok' => 0,
            'code_branche_deux' => 'COL',
            'type_contrat' => 'EPA',
            'capital' => 0,
            'code_produit_court' => 'PFBNI',
            'duree_souscription_annee' => null,
            'duree_souscription_mois' => null,
            'vie_entiere' => 0,
            'duree_cotisation_ans' => null,
            'duree_cotisation_mois' => null,
            'code_marque' => null,
        ],

        // ============================================================
        // 21. PERFORMA COLLECTIF
        // ============================================================
        [
            'code' => 'PFA_COL',
            'libelle' => 'PERFORMA COLLECTIF',
            'date_creation' => null,
            'code_branche' => '6',
            'code_produit_nature' => 'COLLF',
            'statut' => 'COM',
            'code_groupe_assure' => null,
            'code_groupe_profil' => null,
            'age_mini_adh' => 18,
            'age_maxi_adh' => 65,
            'table_tarification' => 'TDM100',
            'table_reglementaire' => 'TDM100',
            'table_fiscale' => 'TDM100',
            'table_comptable' => 'TDM100',
            'code_contractant' => 'DNA',
            'num_seq' => '105',
            'delai_carrence' => 12,
            'capital_assure_pmok' => 1,
            'capital_assure_vers_excp_ok' => 0,
            'code_branche_deux' => 'COL',
            'type_contrat' => 'EPA',
            'capital' => 0,
            'code_produit_court' => 'PFCOL',
            'duree_souscription_annee' => null,
            'duree_souscription_mois' => null,
            'vie_entiere' => 0,
            'duree_cotisation_ans' => null,
            'duree_cotisation_mois' => null,
            'code_marque' => null,
        ],

        // ============================================================
        // 22. LOYALEMPRUNTEUR
        // ============================================================
        [
            'code' => 'LOYEMP',
            'libelle' => 'LOYALEMPRUNTEUR',
            'date_creation' => null,
            'code_branche' => '6',
            'code_produit_nature' => 'COLLF',
            'statut' => 'COM',
            'code_groupe_assure' => null,
            'code_groupe_profil' => null,
            'age_mini_adh' => 18,
            'age_maxi_adh' => 99,
            'table_tarification' => 'TDM100',
            'table_reglementaire' => 'TDM100',
            'table_fiscale' => 'TDM100',
            'table_comptable' => 'TDM100',
            'code_contractant' => 'DNA',
            'num_seq' => '3380',
            'delai_carrence' => 0,
            'capital_assure_pmok' => 0,
            'capital_assure_vers_excp_ok' => 0,
            'code_branche_deux' => 'COL',
            'type_contrat' => 'KDEC',
            'capital' => 0,
            'code_produit_court' => 'LEMP',
            'duree_souscription_annee' => null,
            'duree_souscription_mois' => null,
            'vie_entiere' => 0,
            'duree_cotisation_ans' => null,
            'duree_cotisation_mois' => null,
            'code_marque' => 'YAKO',
        ],

        // ============================================================
        // 23. LIBRAVOUS
        // ============================================================
        [
            'code' => 'LIBRAVOUS',
            'libelle' => 'LIBRAVOUS',
            'date_creation' => '1993-01-01',
            'code_branche' => '5',
            'code_produit_nature' => 'INDIV',
            'statut' => 'COM',
            'code_groupe_assure' => null,
            'code_groupe_profil' => null,
            'age_mini_adh' => 18,
            'age_maxi_adh' => 99,
            'table_tarification' => 'TDM100',
            'table_reglementaire' => 'TDM100',
            'table_fiscale' => 'TDM100',
            'table_comptable' => 'TDM100',
            'code_contractant' => 'DNA',
            'num_seq' => '2',
            'delai_carrence' => 0,
            'capital_assure_pmok' => -1,
            'capital_assure_vers_excp_ok' => 1,
            'code_branche_deux' => 'IND',
            'type_contrat' => 'EPA',
            'capital' => 0,
            'code_produit_court' => 'LIBVS',
            'duree_souscription_annee' => null,
            'duree_souscription_mois' => null,
            'vie_entiere' => 0,
            'duree_cotisation_ans' => null,
            'duree_cotisation_mois' => null,
            'code_marque' => 'CAD',
        ],

        // ============================================================
        // 24. Stratégie IFC
        // ============================================================
        [
            'code' => 'IFC',
            'libelle' => 'Stratégie IFC',
            'date_creation' => null,
            'code_branche' => null,
            'code_produit_nature' => 'COLLO',
            'statut' => 'COM',
            'code_groupe_assure' => null,
            'code_groupe_profil' => null,
            'age_mini_adh' => 18,
            'age_maxi_adh' => 65,
            'table_tarification' => 'TDM100',
            'table_reglementaire' => 'TDM100',
            'table_fiscale' => 'TDM100',
            'table_comptable' => 'TDM100',
            'code_contractant' => 'DNA',
            'num_seq' => '8',
            'delai_carrence' => 0,
            'capital_assure_pmok' => -1,
            'capital_assure_vers_excp_ok' => 0,
            'code_branche_deux' => 'COL',
            'type_contrat' => 'EPA',
            'capital' => 0,
            'code_produit_court' => null,
            'duree_souscription_annee' => null,
            'duree_souscription_mois' => null,
            'vie_entiere' => 0,
            'duree_cotisation_ans' => null,
            'duree_cotisation_mois' => null,
            'code_marque' => 'EMB',
        ],

        // ============================================================
        // 25. Temporaire Décès à Capital Décroissant
        // ============================================================
        [
            'code' => 'TDIDECROIS',
            'libelle' => 'Temporaire Décès à Capital Décroissant',
            'date_creation' => '2000-01-01',
            'code_branche' => '5',
            'code_produit_nature' => null,
            'statut' => 'COM',
            'code_groupe_assure' => null,
            'code_groupe_profil' => null,
            'age_mini_adh' => 12,
            'age_maxi_adh' => 70,
            'table_tarification' => null,
            'table_reglementaire' => null,
            'table_fiscale' => null,
            'table_comptable' => null,
            'code_contractant' => 'DNA',
            'num_seq' => null,
            'delai_carrence' => 0,
            'capital_assure_pmok' => -1,
            'capital_assure_vers_excp_ok' => -1,
            'code_branche_deux' => 'IND',
            'type_contrat' => 'KDEC',
            'capital' => 0,
            'code_produit_court' => 'TDD',
            'duree_souscription_annee' => null,
            'duree_souscription_mois' => null,
            'vie_entiere' => 0,
            'duree_cotisation_ans' => null,
            'duree_cotisation_mois' => null,
            'code_marque' => null,
        ],

        // ============================================================
        // 26. SECURICOMPTE
        // ============================================================
        [
            'code' => 'SECURICPTE',
            'libelle' => 'SECURICOMPTE',
            'date_creation' => '2017-07-25',
            'code_branche' => '6',
            'code_produit_nature' => 'COLLF',
            'statut' => 'COM',
            'code_groupe_assure' => null,
            'code_groupe_profil' => null,
            'age_mini_adh' => 18,
            'age_maxi_adh' => 70,
            'table_tarification' => null,
            'table_reglementaire' => null,
            'table_fiscale' => null,
            'table_comptable' => null,
            'code_contractant' => null,
            'num_seq' => '1051',
            'delai_carrence' => 0,
            'capital_assure_pmok' => 0,
            'capital_assure_vers_excp_ok' => 0,
            'code_branche_deux' => 'COL',
            'type_contrat' => 'KDEC',
            'capital' => 0,
            'code_produit_court' => 'SCPTE',
            'duree_souscription_annee' => null,
            'duree_souscription_mois' => null,
            'vie_entiere' => 0,
            'duree_cotisation_ans' => null,
            'duree_cotisation_mois' => null,
            'code_marque' => null,
        ],

        // ============================================================
        // 27. CARTE BANCAIRE BNI
        // ============================================================
        [
            'code' => 'CRTBANKBNI',
            'libelle' => 'CARTE BANCAIRE BNI',
            'date_creation' => null,
            'code_branche' => '6',
            'code_produit_nature' => 'COLLF',
            'statut' => 'COM',
            'code_groupe_assure' => null,
            'code_groupe_profil' => null,
            'age_mini_adh' => 18,
            'age_maxi_adh' => 99,
            'table_tarification' => null,
            'table_reglementaire' => null,
            'table_fiscale' => null,
            'table_comptable' => null,
            'code_contractant' => null,
            'num_seq' => null,
            'delai_carrence' => 0,
            'capital_assure_pmok' => 0,
            'capital_assure_vers_excp_ok' => 0,
            'code_branche_deux' => 'COL',
            'type_contrat' => 'KDEC',
            'capital' => 0,
            'code_produit_court' => 'CBBNI',
            'duree_souscription_annee' => null,
            'duree_souscription_mois' => null,
            'vie_entiere' => 0,
            'duree_cotisation_ans' => null,
            'duree_cotisation_mois' => null,
            'code_marque' => 'YAKO',
        ],

        // ============================================================
        // 28. CARTE BANCAIRE CNCE
        // ============================================================
        [
            'code' => 'CARTE_BANC_CNCE',
            'libelle' => 'CARTE BANCAIRE CNCE',
            'date_creation' => null,
            'code_branche' => '6',
            'code_produit_nature' => 'COLLF',
            'statut' => 'COM',
            'code_groupe_assure' => null,
            'code_groupe_profil' => null,
            'age_mini_adh' => 0,
            'age_maxi_adh' => 0,
            'table_tarification' => null,
            'table_reglementaire' => null,
            'table_fiscale' => null,
            'table_comptable' => null,
            'code_contractant' => null,
            'num_seq' => null,
            'delai_carrence' => 0,
            'capital_assure_pmok' => 0,
            'capital_assure_vers_excp_ok' => 0,
            'code_branche_deux' => 'COL',
            'type_contrat' => 'KDEC',
            'capital' => 0,
            'code_produit_court' => 'CBCE',
            'duree_souscription_annee' => null,
            'duree_souscription_mois' => null,
            'vie_entiere' => 0,
            'duree_cotisation_ans' => null,
            'duree_cotisation_mois' => null,
            'code_marque' => 'YAKO',
        ],

        // ============================================================
        // 29. DOIHOO
        // ============================================================
        [
            'code' => 'DOIHOO',
            'libelle' => 'DOIHOO',
            'date_creation' => '2020-01-10',
            'code_branche' => null,
            'code_produit_nature' => 'INDIV',
            'statut' => 'COM',
            'code_groupe_assure' => null,
            'code_groupe_profil' => null,
            'age_mini_adh' => 18,
            'age_maxi_adh' => 99,
            'table_tarification' => null,
            'table_reglementaire' => null,
            'table_fiscale' => null,
            'table_comptable' => null,
            'code_contractant' => 'DNA',
            'num_seq' => '5480',
            'delai_carrence' => 0,
            'capital_assure_pmok' => 0,
            'capital_assure_vers_excp_ok' => 0,
            'code_branche_deux' => 'IND',
            'type_contrat' => 'CAPI',
            'capital' => 0,
            'code_produit_court' => 'DOIHO',
            'duree_souscription_annee' => null,
            'duree_souscription_mois' => null,
            'vie_entiere' => 0,
            'duree_cotisation_ans' => null,
            'duree_cotisation_mois' => null,
            'code_marque' => 'DOI',
        ],

        // ============================================================
        // 30. PARRAINAGECOMPTE CAC
        // ============================================================
        [
            'code' => 'PARRAINAGECPTE',
            'libelle' => 'PARRAINAGECOMPTE CAC',
            'date_creation' => null,
            'code_branche' => '6',
            'code_produit_nature' => 'COLLF',
            'statut' => 'COM',
            'code_groupe_assure' => null,
            'code_groupe_profil' => null,
            'age_mini_adh' => 18,
            'age_maxi_adh' => 70,
            'table_tarification' => null,
            'table_reglementaire' => null,
            'table_fiscale' => null,
            'table_comptable' => null,
            'code_contractant' => null,
            'num_seq' => null,
            'delai_carrence' => 0,
            'capital_assure_pmok' => 0,
            'capital_assure_vers_excp_ok' => 0,
            'code_branche_deux' => 'COL',
            'type_contrat' => 'KDEC',
            'capital' => 0,
            'code_produit_court' => 'PCPTE',
            'duree_souscription_annee' => 0,
            'duree_souscription_mois' => 0,
            'vie_entiere' => 0,
            'duree_cotisation_ans' => 0,
            'duree_cotisation_mois' => 0,
            'code_marque' => 'YAKO',
        ],

        // ============================================================
        // 31. INVEST+ INDIVIDUEL
        // ============================================================
        [
            'code' => 'INV_2020',
            'libelle' => 'INVEST+ INDIVIDUEL',
            'date_creation' => '2020-08-10',
            'code_branche' => null,
            'code_produit_nature' => 'INDIV',
            'statut' => 'COM',
            'code_groupe_assure' => null,
            'code_groupe_profil' => null,
            'age_mini_adh' => 12,
            'age_maxi_adh' => 99,
            'table_tarification' => null,
            'table_reglementaire' => null,
            'table_fiscale' => null,
            'table_comptable' => null,
            'code_contractant' => 'DNA',
            'num_seq' => '6889',
            'delai_carrence' => 12,
            'capital_assure_pmok' => 1,
            'capital_assure_vers_excp_ok' => 1,
            'code_branche_deux' => 'IND',
            'type_contrat' => 'EPA',
            'capital' => 0,
            'code_produit_court' => 'INV',
            'duree_souscription_annee' => 5,
            'duree_souscription_mois' => 60,
            'vie_entiere' => 0,
            'duree_cotisation_ans' => 5,
            'duree_cotisation_mois' => 60,
            'code_marque' => 'PERF',
        ],

        // ============================================================
        // 32. YAKO Eternité 2018
        // ============================================================
        [
            'code' => 'YKE_2018',
            'libelle' => 'YAKO Eternité 2018',
            'date_creation' => '2021-02-01',
            'code_branche' => null,
            'code_produit_nature' => 'INDIV',
            'statut' => 'COM',
            'code_groupe_assure' => null,
            'code_groupe_profil' => null,
            'age_mini_adh' => 12,
            'age_maxi_adh' => 99,
            'table_tarification' => null,
            'table_reglementaire' => null,
            'table_fiscale' => null,
            'table_comptable' => null,
            'code_contractant' => 'DNA',
            'num_seq' => '2225',
            'delai_carrence' => 12,
            'capital_assure_pmok' => 1,
            'capital_assure_vers_excp_ok' => 1,
            'code_branche_deux' => 'IND',
            'type_contrat' => 'MIXTE',
            'capital' => 1,
            'code_produit_court' => 'YKE',
            'duree_souscription_annee' => 99,
            'duree_souscription_mois' => 0,
            'vie_entiere' => 0,
            'duree_cotisation_ans' => 1,
            'duree_cotisation_mois' => 5,
            'code_marque' => 'YAKO',
        ],

        // ============================================================
        // 33. CADENCE EDUCATION PLUS
        // ============================================================
        [
            'code' => 'CAD_EDUCPLUS',
            'libelle' => 'CADENCE EDUCATION PLUS',
            'date_creation' => '2020-11-13',
            'code_branche' => '5',
            'code_produit_nature' => 'INDIV',
            'statut' => 'COM',
            'code_groupe_assure' => null,
            'code_groupe_profil' => null,
            'age_mini_adh' => 18,
            'age_maxi_adh' => 55,
            'table_tarification' => null,
            'table_reglementaire' => null,
            'table_fiscale' => null,
            'table_comptable' => null,
            'code_contractant' => 'DNA',
            'num_seq' => '11011',
            'delai_carrence' => 0,
            'capital_assure_pmok' => 1,
            'capital_assure_vers_excp_ok' => 0,
            'code_branche_deux' => 'IND',
            'type_contrat' => 'MIXTE',
            'capital' => 0,
            'code_produit_court' => 'CADEP',
            'duree_souscription_annee' => 0,
            'duree_souscription_mois' => 0,
            'vie_entiere' => 0,
            'duree_cotisation_ans' => 0,
            'duree_cotisation_mois' => 0,
            'code_marque' => 'CAD',
        ],

        // ============================================================
        // 34. YAKO Seniors 2021
        // ============================================================
        [
            'code' => 'YKR_2021',
            'libelle' => 'YAKO Seniors 2021',
            'date_creation' => '2021-06-09',
            'code_branche' => '5',
            'code_produit_nature' => 'INDIV',
            'statut' => 'COM',
            'code_groupe_assure' => null,
            'code_groupe_profil' => null,
            'age_mini_adh' => 12,
            'age_maxi_adh' => 80,
            'table_tarification' => null,
            'table_reglementaire' => null,
            'table_fiscale' => null,
            'table_comptable' => null,
            'code_contractant' => 'DNA',
            'num_seq' => '196',
            'delai_carrence' => 0,
            'capital_assure_pmok' => 0,
            'capital_assure_vers_excp_ok' => 0,
            'code_branche_deux' => 'IND',
            'type_contrat' => 'KVIE',
            'capital' => 0,
            'code_produit_court' => 'YKR',
            'duree_souscription_annee' => 1,
            'duree_souscription_mois' => 0,
            'vie_entiere' => 0,
            'duree_cotisation_ans' => 1,
            'duree_cotisation_mois' => 0,
            'code_marque' => null,
        ],

        // ============================================================
        // 35. AFS PREVOYANCE
        // ============================================================
        [
            'code' => 'AFS_PREVO',
            'libelle' => 'AFS PREVOYANCE',
            'date_creation' => '2023-01-25',
            'code_branche' => '6',
            'code_produit_nature' => 'COLLO',
            'statut' => 'COM',
            'code_groupe_assure' => null,
            'code_groupe_profil' => null,
            'age_mini_adh' => 0,
            'age_maxi_adh' => 0,
            'table_tarification' => null,
            'table_reglementaire' => null,
            'table_fiscale' => null,
            'table_comptable' => null,
            'code_contractant' => null,
            'num_seq' => '179',
            'delai_carrence' => 0,
            'capital_assure_pmok' => 0,
            'capital_assure_vers_excp_ok' => 0,
            'code_branche_deux' => 'COL',
            'type_contrat' => 'KDEC',
            'capital' => 0,
            'code_produit_court' => null,
            'duree_souscription_annee' => 0,
            'duree_souscription_mois' => 0,
            'vie_entiere' => 0,
            'duree_cotisation_ans' => 0,
            'duree_cotisation_mois' => 0,
            'code_marque' => null,
        ],

        // ============================================================
        // 36. AFS PREVOYANCE PLUS
        // ============================================================
        [
            'code' => 'AFS_PREVOPLUS',
            'libelle' => 'AFS PREVOYANCE PLUS',
            'date_creation' => '2023-01-25',
            'code_branche' => '6',
            'code_produit_nature' => 'COLLO',
            'statut' => 'COM',
            'code_groupe_assure' => null,
            'code_groupe_profil' => null,
            'age_mini_adh' => 0,
            'age_maxi_adh' => 0,
            'table_tarification' => null,
            'table_reglementaire' => null,
            'table_fiscale' => null,
            'table_comptable' => null,
            'code_contractant' => null,
            'num_seq' => '101',
            'delai_carrence' => 0,
            'capital_assure_pmok' => 0,
            'capital_assure_vers_excp_ok' => 0,
            'code_branche_deux' => null,
            'type_contrat' => 'MIXTE',
            'capital' => 0,
            'code_produit_court' => null,
            'duree_souscription_annee' => 0,
            'duree_souscription_mois' => 0,
            'vie_entiere' => 0,
            'duree_cotisation_ans' => 0,
            'duree_cotisation_mois' => 0,
            'code_marque' => null,
        ],

        // ============================================================
        // 37. AFS EPARGNE PLUS
        // ============================================================
        [
            'code' => 'AFS_EPGNPLUS',
            'libelle' => 'AFS EPARGNE PLUS',
            'date_creation' => '2023-01-25',
            'code_branche' => '6',
            'code_produit_nature' => 'COLLO',
            'statut' => 'COM',
            'code_groupe_assure' => null,
            'code_groupe_profil' => null,
            'age_mini_adh' => 0,
            'age_maxi_adh' => 0,
            'table_tarification' => null,
            'table_reglementaire' => null,
            'table_fiscale' => null,
            'table_comptable' => null,
            'code_contractant' => null,
            'num_seq' => '1382',
            'delai_carrence' => 0,
            'capital_assure_pmok' => 0,
            'capital_assure_vers_excp_ok' => 0,
            'code_branche_deux' => 'COL',
            'type_contrat' => 'MIXTE',
            'capital' => 0,
            'code_produit_court' => null,
            'duree_souscription_annee' => 0,
            'duree_souscription_mois' => 0,
            'vie_entiere' => 0,
            'duree_cotisation_ans' => 0,
            'duree_cotisation_mois' => 0,
            'code_marque' => null,
        ],

        // ============================================================
        // 38. AFS SANTE
        // ============================================================
        [
            'code' => 'AFS_SANTE',
            'libelle' => 'AFS SANTE',
            'date_creation' => '2023-01-25',
            'code_branche' => '6',
            'code_produit_nature' => 'COLLO',
            'statut' => 'COM',
            'code_groupe_assure' => null,
            'code_groupe_profil' => null,
            'age_mini_adh' => 0,
            'age_maxi_adh' => 0,
            'table_tarification' => null,
            'table_reglementaire' => null,
            'table_fiscale' => null,
            'table_comptable' => null,
            'code_contractant' => null,
            'num_seq' => '85',
            'delai_carrence' => 0,
            'capital_assure_pmok' => 0,
            'capital_assure_vers_excp_ok' => 0,
            'code_branche_deux' => null,
            'type_contrat' => null,
            'capital' => 0,
            'code_produit_court' => null,
            'duree_souscription_annee' => 0,
            'duree_souscription_mois' => 0,
            'vie_entiere' => 0,
            'duree_cotisation_ans' => 0,
            'duree_cotisation_mois' => 0,
            'code_marque' => null,
        ],

        // ============================================================
        // 39. YAKO Solo 2018
        // ============================================================
        [
            'code' => 'YKS_2018',
            'libelle' => 'YAKO Solo 2018',
            'date_creation' => '2023-03-01',
            'code_branche' => '5',
            'code_produit_nature' => 'INDIV',
            'statut' => 'COM',
            'code_groupe_assure' => null,
            'code_groupe_profil' => null,
            'age_mini_adh' => 12,
            'age_maxi_adh' => 99,
            'table_tarification' => null,
            'table_reglementaire' => null,
            'table_fiscale' => null,
            'table_comptable' => null,
            'code_contractant' => null,
            'num_seq' => null,
            'delai_carrence' => 0,
            'capital_assure_pmok' => 0,
            'capital_assure_vers_excp_ok' => 0,
            'code_branche_deux' => null,
            'type_contrat' => null,
            'capital' => 0,
            'code_produit_court' => 'YKSO2',
            'duree_souscription_annee' => 10,
            'duree_souscription_mois' => 120,
            'vie_entiere' => 0,
            'duree_cotisation_ans' => 10,
            'duree_cotisation_mois' => 120,
            'code_marque' => 'YAKO',
        ],

        // ============================================================
        // 40. Plan Vert Retraite Premium
        // ============================================================
        [
            'code' => 'PVRPRE',
            'libelle' => 'Plan Vert Retraite Premium',
            'date_creation' => null,
            'code_branche' => '6',
            'code_produit_nature' => 'INDIV',
            'statut' => 'COM',
            'code_groupe_assure' => null,
            'code_groupe_profil' => null,
            'age_mini_adh' => 0,
            'age_maxi_adh' => 0,
            'table_tarification' => null,
            'table_reglementaire' => null,
            'table_fiscale' => null,
            'table_comptable' => null,
            'code_contractant' => null,
            'num_seq' => '804',
            'delai_carrence' => 0,
            'capital_assure_pmok' => 0,
            'capital_assure_vers_excp_ok' => 0,
            'code_branche_deux' => null,
            'type_contrat' => null,
            'capital' => 0,
            'code_produit_court' => 'PVPRE',
            'duree_souscription_annee' => 0,
            'duree_souscription_mois' => 0,
            'vie_entiere' => 0,
            'duree_cotisation_ans' => 0,
            'duree_cotisation_mois' => 0,
            'code_marque' => 'CAD',
        ],

        // ============================================================
        // 41. YAKO 100% GAGNANT
        // ============================================================
        [
            'code' => 'YKP_2024',
            'libelle' => 'YAKO 100% GAGNANT',
            'date_creation' => '2024-08-21',
            'code_branche' => '5',
            'code_produit_nature' => 'INDIV',
            'statut' => 'COM',
            'code_groupe_assure' => null,
            'code_groupe_profil' => null,
            'age_mini_adh' => 18,
            'age_maxi_adh' => 64,
            'table_tarification' => null,
            'table_reglementaire' => null,
            'table_fiscale' => null,
            'table_comptable' => null,
            'code_contractant' => 'DNA',
            'num_seq' => '3',
            'delai_carrence' => 0,
            'capital_assure_pmok' => 0,
            'capital_assure_vers_excp_ok' => 0,
            'code_branche_deux' => null,
            'type_contrat' => null,
            'capital' => 0,
            'code_produit_court' => 'YKCEN',
            'duree_souscription_annee' => 3,
            'duree_souscription_mois' => 36,
            'vie_entiere' => 0,
            'duree_cotisation_ans' => 0,
            'duree_cotisation_mois' => 0,
            'code_marque' => 'YAKO',
        ],

        // ============================================================
        // 42. INVEST+ ENTREPRISE
        // ============================================================
        [
            'code' => 'INV_COL',
            'libelle' => 'INVEST+ ENTREPRISE',
            'date_creation' => '2024-12-04',
            'code_branche' => '6',
            'code_produit_nature' => 'COLLO',
            'statut' => 'COM',
            'code_groupe_assure' => null,
            'code_groupe_profil' => null,
            'age_mini_adh' => 0,
            'age_maxi_adh' => 0,
            'table_tarification' => null,
            'table_reglementaire' => null,
            'table_fiscale' => null,
            'table_comptable' => null,
            'code_contractant' => null,
            'num_seq' => '107',
            'delai_carrence' => 0,
            'capital_assure_pmok' => 0,
            'capital_assure_vers_excp_ok' => 0,
            'code_branche_deux' => 'COL',
            'type_contrat' => 'EPA',
            'capital' => 0,
            'code_produit_court' => 'INCO',
            'duree_souscription_annee' => 0,
            'duree_souscription_mois' => 0,
            'vie_entiere' => 0,
            'duree_cotisation_ans' => 0,
            'duree_cotisation_mois' => 0,
            'code_marque' => 'PERF',
        ],

        // ============================================================
        // 43. YAKO Eternité 2026
        // ============================================================
        [
            'code' => 'YKE_2026',
            'libelle' => 'YAKO Eternité 2026',
            'date_creation' => '2026-07-16',
            'code_branche' => '5',
            'code_produit_nature' => 'INDIV',
            'statut' => 'COM',
            'code_groupe_assure' => null,
            'code_groupe_profil' => null,
            'age_mini_adh' => 12,
            'age_maxi_adh' => 75,
            'table_tarification' => null,
            'table_reglementaire' => null,
            'table_fiscale' => null,
            'table_comptable' => null,
            'code_contractant' => 'DNA',
            'num_seq' => '44',
            'delai_carrence' => 12,
            'capital_assure_pmok' => 0,
            'capital_assure_vers_excp_ok' => 0,
            'code_branche_deux' => 'IND',
            'type_contrat' => 'MIXTE',
            'capital' => 0,
            'code_produit_court' => 'YKE',
            'duree_souscription_annee' => 99,
            'duree_souscription_mois' => 0,
            'vie_entiere' => 0,
            'duree_cotisation_ans' => 5,
            'duree_cotisation_mois' => 60,
            'code_marque' => 'YAKO',
        ],

        // ============================================================
        // 44. CADENCE EDUCATION PLUS 2026
        // ============================================================
        [
            'code' => 'CAD_EDUCPLUS_2026',
            'libelle' => 'CADENCE EDUCATION PLUS 2026',
            'date_creation' => null,
            'code_branche' => '5',
            'code_produit_nature' => 'INDIV',
            'statut' => 'COM',
            'code_groupe_assure' => null,
            'code_groupe_profil' => null,
            'age_mini_adh' => 18,
            'age_maxi_adh' => 55,
            'table_tarification' => null,
            'table_reglementaire' => null,
            'table_fiscale' => null,
            'table_comptable' => null,
            'code_contractant' => 'DNA',
            'num_seq' => '10851',
            'delai_carrence' => 0,
            'capital_assure_pmok' => 1,
            'capital_assure_vers_excp_ok' => 0,
            'code_branche_deux' => 'IND',
            'type_contrat' => 'MIXTE',
            'capital' => 0,
            'code_produit_court' => 'CADEP',
            'duree_souscription_annee' => 0,
            'duree_souscription_mois' => 0,
            'vie_entiere' => 0,
            'duree_cotisation_ans' => 0,
            'duree_cotisation_mois' => 0,
            'code_marque' => 'CAD',
        ],
    ];

    public function run(): void
    {
        $this->command->info('🚀 Début du seeding des produits...');
        $this->command->newLine();

        // Récupérer les types de produits
        $typeProduits = TypeProduit::all()->keyBy('code');

        if ($typeProduits->isEmpty()) {
            $this->command->warn('⚠️  Aucun type de produit trouvé. Veuillez exécuter TypeProduitSeeder d\'abord.');
            return;
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $total = count(self::PRODUITS);

        $this->command->info("📋 {$total} produits à traiter...");
        $this->command->newLine();

        $progressBar = $this->command->getOutput()->createProgressBar($total);
        $progressBar->start();

        foreach (self::PRODUITS as $produitData) {
            // Déterminer le type de produit
            $typeContrat = $produitData['type_contrat'] ?? null;
            $typeProduitUuid = null;

            if ($typeContrat && isset(self::TYPE_CONTRAT_MAPPING[$typeContrat])) {
                $typeCode = self::TYPE_CONTRAT_MAPPING[$typeContrat];
                $typeProduit = $typeProduits->get($typeCode);
                if ($typeProduit) {
                    $typeProduitUuid = $typeProduit->uuid_type_produit;
                }
            }

            // Nettoyer les données
            $data = $this->prepareData($produitData, $typeProduitUuid);

            // Créer ou mettre à jour
            $result = $this->createOrUpdateProduit($data);
            
            if ($result === 'created') {
                $created++;
            } elseif ($result === 'updated') {
                $updated++;
            } else {
                $skipped++;
            }

            $progressBar->advance();
        }

        $progressBar->finish();

        $this->command->newLine(2);
        $this->command->info('📊 Résumé du seeding des produits :');
        $this->command->info("  ✅ {$created} produits créés");
        $this->command->info("  🔄 {$updated} produits mis à jour");
        $this->command->info("  ⏭️  {$skipped} produits ignorés");
        $this->command->info("  📋 {$total} produits traités");
        $this->command->newLine();
        $this->command->info('✅ Seed des produits terminé !');
    }

    /**
     * Préparer les données du produit
     */
    private function prepareData(array $produitData, ?string $typeProduitUuid): array
    {
        // Nettoyer la date
        $dateCreation = null;
        if (!empty($produitData['date_creation']) && $produitData['date_creation'] !== 'NULL') {
            try {
                $dateCreation = Carbon::parse($produitData['date_creation']);
            } catch (\Exception $e) {
                $dateCreation = null;
            }
        }

        return [
            'uuid_produit' => (string) Str::uuid(),
            'code' => $produitData['code'],
            'libelle' => $produitData['libelle'],
            'date_creation' => $dateCreation,
            'code_branche' => $this->parseString($produitData['code_branche']),
            'code_produit_nature' => $this->parseString($produitData['code_produit_nature']),
            'description' => $produitData['libelle'] . ' - Produit d\'assurance',
            'statut' => $this->parseString($produitData['statut']) ?? 'actif',
            'code_groupe_assure' => $this->parseString($produitData['code_groupe_assure']),
            'code_groupe_profil' => $this->parseString($produitData['code_groupe_profil']),
            'age_mini_adh' => $this->parseInt($produitData['age_mini_adh']),
            'age_maxi_adh' => $this->parseInt($produitData['age_maxi_adh']),
            'table_tarification' => $this->parseString($produitData['table_tarification']),
            'table_reglementaire' => $this->parseString($produitData['table_reglementaire']),
            'table_fiscale' => $this->parseString($produitData['table_fiscale']),
            'table_comptable' => $this->parseString($produitData['table_comptable']),
            'code_contractant' => $this->parseString($produitData['code_contractant']),
            'num_seq' => $this->parseString($produitData['num_seq']),
            'delai_carrence' => $this->parseInt($produitData['delai_carrence']),
            'capital_assure_pmok' => $this->parseInt($produitData['capital_assure_pmok']),
            'capital_assure_vers_excp_ok' => $this->parseInt($produitData['capital_assure_vers_excp_ok']),
            'code_branche_deux' => $this->parseString($produitData['code_branche_deux']),
            'type_produit_uuid' => $typeProduitUuid,
            'capital' => $this->parseInt($produitData['capital']),
            'code_produit_court' => $this->parseString($produitData['code_produit_court']),
            'duree_souscription_annee' => $this->parseInt($produitData['duree_souscription_annee']),
            'duree_souscription_mois' => $this->parseInt($produitData['duree_souscription_mois']),
            'vie_entiere' => $this->parseBool($produitData['vie_entiere']),
            'duree_cotisation_ans' => $this->parseInt($produitData['duree_cotisation_ans']),
            'duree_cotisation_mois' => $this->parseInt($produitData['duree_cotisation_mois']),
            'code_marque' => $this->parseString($produitData['code_marque']),
            'created_by' => null,
            'updated_by' => null,
            'deleted_by' => null,
        ];
    }

    /**
     * Créer ou mettre à jour un produit
     */
    private function createOrUpdateProduit(array $data): string
    {
        $existing = Produit::where('code', $data['code'])->first();

        if ($existing) {
            // Vérifier si des champs importants ont changé
            $fieldsToCheck = ['libelle', 'code_branche', 'age_mini_adh', 'age_maxi_adh', 'statut', 'type_produit_uuid'];
            $hasChanged = false;

            foreach ($fieldsToCheck as $field) {
                if (isset($data[$field]) && $existing->$field != $data[$field]) {
                    $hasChanged = true;
                    break;
                }
            }

            if ($hasChanged) {
                $existing->update($data);
                return 'updated';
            }
            return 'skipped';
        }

        Produit::create($data);
        return 'created';
    }

    /**
     * Parser une valeur en entier
     */
    private function parseInt($value): ?int
    {
        if ($value === null || $value === '' || $value === 'NULL' || $value === 'null') {
            return null;
        }
        if (!is_numeric($value)) {
            return null;
        }
        return (int) $value;
    }

    /**
     * Parser une valeur en booléen
     */
    private function parseBool($value): ?bool
    {
        if ($value === null || $value === '' || $value === 'NULL' || $value === 'null') {
            return null;
        }
        return (bool) $value;
    }

    /**
     * Parser une valeur en string
     */
    private function parseString($value): ?string
    {
        if ($value === null || $value === '' || $value === 'NULL' || $value === 'null') {
            return null;
        }
        return (string) $value;
    }
}