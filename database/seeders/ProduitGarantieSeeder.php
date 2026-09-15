<?php
// database/seeders/ProduitGarantieSeeder.php

namespace Database\Seeders;

use App\Models\Api\Ynov\parameter\Produit;
use App\Models\Api\Ynov\parameter\ProduitGarantie;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProduitGarantieSeeder extends Seeder
{
    /**
     * Liste complète des garanties produits
     */
    private const GARANTIES = [
        ['code_produit' => 'ASSCPTBNI', 'code_produit_garantie' => 'DECES', 'libelle' => 'Deces', 'est_obligatoire' => 1, 'nature_garantie' => 'Principale Obligatoire', 'type' => 'Deces', 'age_min' => 18, 'age_max' => 65, 'duree_cotisation_min' => 0, 'duree_cotisation_max' => 99, 'duree_contrat_min' => 0, 'duree_contrat_max' => 99, 'branche' => 'BANKASS'],

        ['code_produit' => 'CADENCE', 'code_produit_garantie' => 'LIB', 'libelle' => 'Liberté', 'est_obligatoire' => 1, 'nature_garantie' => 'Principale Obligatoire', 'type' => 'Epargne', 'age_min' => 12, 'age_max' => 90, 'duree_cotisation_min' => 6, 'duree_cotisation_max' => 60, 'duree_contrat_min' => 6, 'duree_contrat_max' => 60, 'branche' => 'IND'],

        ['code_produit' => 'CADENCE', 'code_produit_garantie' => 'DIG', 'libelle' => 'Dignité', 'est_obligatoire' => 1, 'nature_garantie' => 'Complementaire Obligatoire', 'type' => 'Epargne', 'age_min' => 12, 'age_max' => 90, 'duree_cotisation_min' => 6, 'duree_cotisation_max' => 60, 'duree_contrat_min' => 6, 'duree_contrat_max' => 60, 'branche' => 'IND'],

        ['code_produit' => 'CADENCE', 'code_produit_garantie' => 'SUR', 'libelle' => 'Sûreté', 'est_obligatoire' => 0, 'nature_garantie' => 'Complementaire facultatif', 'type' => 'Deces', 'age_min' => 12, 'age_max' => 90, 'duree_cotisation_min' => 6, 'duree_cotisation_max' => 60, 'duree_contrat_min' => 6, 'duree_contrat_max' => 60, 'branche' => 'IND'],

        ['code_produit' => 'CADENCE', 'code_produit_garantie' => 'DECESACC', 'libelle' => 'Décès accidentel', 'est_obligatoire' => 0, 'nature_garantie' => 'Complementaire facultatif', 'type' => 'Deces', 'age_min' => 12, 'age_max' => 90, 'duree_cotisation_min' => 6, 'duree_cotisation_max' => 60, 'duree_contrat_min' => 6, 'duree_contrat_max' => 60, 'branche' => 'IND'],

        ['code_produit' => 'CRTBANKBNI', 'code_produit_garantie' => 'DECES', 'libelle' => 'Deces', 'est_obligatoire' => 1, 'nature_garantie' => 'Principale Obligatoire', 'type' => 'Deces', 'age_min' => 18, 'age_max' => 65, 'duree_cotisation_min' => 0, 'duree_cotisation_max' => 99, 'duree_contrat_min' => 0, 'duree_contrat_max' => 99, 'branche' => 'BANKASS'],

        ['code_produit' => 'IFC', 'code_produit_garantie' => 'RET', 'libelle' => 'RETRAITE', 'est_obligatoire' => 1, 'nature_garantie' => 'Principale Obligatoire', 'type' => 'Epargne', 'age_min' => 18, 'age_max' => 65, 'duree_cotisation_min' => 0, 'duree_cotisation_max' => 99, 'duree_contrat_min' => 0, 'duree_contrat_max' => 99, 'branche' => 'COL'],

        ['code_produit' => 'IFC', 'code_produit_garantie' => 'LIC', 'libelle' => 'LICENCIEMENT', 'est_obligatoire' => 0, 'nature_garantie' => 'Complementaire facultatif', 'type' => 'Epargne', 'age_min' => 18, 'age_max' => 65, 'duree_cotisation_min' => 0, 'duree_cotisation_max' => 99, 'duree_contrat_min' => 0, 'duree_contrat_max' => 99, 'branche' => 'COL'],

        ['code_produit' => 'IFC', 'code_produit_garantie' => 'DECES', 'libelle' => 'DECES', 'est_obligatoire' => 0, 'nature_garantie' => 'Complementaire facultatif', 'type' => 'Deces', 'age_min' => 18, 'age_max' => 65, 'duree_cotisation_min' => 0, 'duree_cotisation_max' => 99, 'duree_contrat_min' => 0, 'duree_contrat_max' => 99, 'branche' => 'COL'],

        ['code_produit' => 'LBV_1995', 'code_produit_garantie' => 'EPARGNE', 'libelle' => 'EPARGNE', 'est_obligatoire' => 1, 'nature_garantie' => 'Principale Obligatoire', 'type' => 'Epargne', 'age_min' => 12, 'age_max' => 90, 'duree_cotisation_min' => 5, 'duree_cotisation_max' => 60, 'duree_contrat_min' => 5, 'duree_contrat_max' => 60, 'branche' => 'IND'],

        ['code_produit' => 'LFFUN', 'code_produit_garantie' => 'ASSFUN_CONJT', 'libelle' => 'ASSISTANCE FUNERAILLES CONJOINT', 'est_obligatoire' => 0, 'nature_garantie' => 'Complementaire facultatif', 'type' => 'Deces', 'age_min' => 18, 'age_max' => 65, 'duree_cotisation_min' => 0, 'duree_cotisation_max' => 99, 'duree_contrat_min' => 0, 'duree_contrat_max' => 99, 'branche' => 'COL'],

        ['code_produit' => 'LFFUN', 'code_produit_garantie' => 'ASSFUN_ADH', 'libelle' => 'ASSISTANCE FUNERAILLES ADHERANT', 'est_obligatoire' => 1, 'nature_garantie' => 'Principale Obligatoire', 'type' => 'Deces', 'age_min' => 18, 'age_max' => 65, 'duree_cotisation_min' => 0, 'duree_cotisation_max' => 99, 'duree_contrat_min' => 0, 'duree_contrat_max' => 99, 'branche' => 'COL'],

        ['code_produit' => 'LFFUN', 'code_produit_garantie' => 'ASSFUN_ENFT', 'libelle' => 'ASSISTANCE FUNERAILLES ENFANT', 'est_obligatoire' => 0, 'nature_garantie' => 'Complementaire facultatif', 'type' => 'Deces', 'age_min' => 18, 'age_max' => 75, 'duree_cotisation_min' => 0, 'duree_cotisation_max' => 99, 'duree_contrat_min' => 0, 'duree_contrat_max' => 99, 'branche' => 'COL'],

        ['code_produit' => 'LFFUN', 'code_produit_garantie' => 'ASSFUN_ASCDT', 'libelle' => 'ASSISTANCE FUNERAILLES ASCENDANTS', 'est_obligatoire' => 0, 'nature_garantie' => 'Complementaire facultatif', 'type' => 'Deces', 'age_min' => 18, 'age_max' => 75, 'duree_cotisation_min' => 0, 'duree_cotisation_max' => 99, 'duree_contrat_min' => 0, 'duree_contrat_max' => 99, 'branche' => 'COL'],

        ['code_produit' => 'LIBRAVOUS', 'code_produit_garantie' => 'EPARGNE', 'libelle' => 'Epargne', 'est_obligatoire' => 1, 'nature_garantie' => 'Principale Obligatoire', 'type' => 'Epargne', 'age_min' => 12, 'age_max' => 90, 'duree_cotisation_min' => 5, 'duree_cotisation_max' => 60, 'duree_contrat_min' => 5, 'duree_contrat_max' => 60, 'branche' => 'IND'],

        ['code_produit' => 'LOYEMP', 'code_produit_garantie' => 'DECES', 'libelle' => 'DECES', 'est_obligatoire' => 1, 'nature_garantie' => 'Principale Obligatoire', 'type' => 'Deces', 'age_min' => 18, 'age_max' => 70, 'duree_cotisation_min' => 0, 'duree_cotisation_max' => 99, 'duree_contrat_min' => 0, 'duree_contrat_max' => 99, 'branche' => 'COL'],

        ['code_produit' => 'LPENSION', 'code_produit_garantie' => 'EPR', 'libelle' => 'EPARGNE RETRAITE', 'est_obligatoire' => 1, 'nature_garantie' => 'Principale Obligatoire', 'type' => 'Epargne', 'age_min' => 18, 'age_max' => 65, 'duree_cotisation_min' => 0, 'duree_cotisation_max' => 99, 'duree_contrat_min' => 0, 'duree_contrat_max' => 99, 'branche' => 'COL'],

        ['code_produit' => 'LPREVO', 'code_produit_garantie' => 'DTC/IAD', 'libelle' => 'DECES TOUTES CAUSES INVALIDITE ABSOLUE ET DEFINITIVE', 'est_obligatoire' => 1, 'nature_garantie' => 'Principale Obligatoire', 'type' => 'Deces', 'age_min' => 18, 'age_max' => 65, 'duree_cotisation_min' => 0, 'duree_cotisation_max' => 99, 'duree_contrat_min' => 0, 'duree_contrat_max' => 99, 'branche' => 'COL'],

        ['code_produit' => 'LPREVO', 'code_produit_garantie' => 'DACC', 'libelle' => 'DECES ACCIDENTEL', 'est_obligatoire' => 0, 'nature_garantie' => 'Complementaire facultatif', 'type' => 'Deces', 'age_min' => 18, 'age_max' => 65, 'duree_cotisation_min' => 0, 'duree_cotisation_max' => 99, 'duree_contrat_min' => 0, 'duree_contrat_max' => 99, 'branche' => 'COL'],

        ['code_produit' => 'LPREVO', 'code_produit_garantie' => 'IPP/IPT', 'libelle' => 'INVALIDITE PERMANENTE PARTIELLE/TOTALE', 'est_obligatoire' => 0, 'nature_garantie' => 'Complementaire facultatif', 'type' => 'Deces', 'age_min' => 18, 'age_max' => 65, 'duree_cotisation_min' => 0, 'duree_cotisation_max' => 99, 'duree_contrat_min' => 0, 'duree_contrat_max' => 99, 'branche' => 'COL'],

        ['code_produit' => 'PFA_BNI', 'code_produit_garantie' => 'SECU', 'libelle' => 'SECURITE', 'est_obligatoire' => 1, 'nature_garantie' => 'Principale Obligatoire', 'type' => 'Epargne', 'age_min' => 18, 'age_max' => 90, 'duree_cotisation_min' => 6, 'duree_cotisation_max' => 99, 'duree_contrat_min' => 6, 'duree_contrat_max' => 99, 'branche' => 'BANKASS'],

        ['code_produit' => 'PFA_BNI', 'code_produit_garantie' => 'PERF', 'libelle' => 'PERFORMANCE', 'est_obligatoire' => 1, 'nature_garantie' => 'Complementaire Obligatoire', 'type' => 'UC', 'age_min' => 18, 'age_max' => 90, 'duree_cotisation_min' => 6, 'duree_cotisation_max' => 99, 'duree_contrat_min' => 6, 'duree_contrat_max' => 99, 'branche' => 'BANKASS'],

        ['code_produit' => 'PFA_BNI', 'code_produit_garantie' => 'SUR', 'libelle' => 'SURETE', 'est_obligatoire' => 0, 'nature_garantie' => 'Complementaire facultatif', 'type' => 'Deces', 'age_min' => 18, 'age_max' => 90, 'duree_cotisation_min' => 6, 'duree_cotisation_max' => 99, 'duree_contrat_min' => 6, 'duree_contrat_max' => 99, 'branche' => 'BANKASS'],

        ['code_produit' => 'PFA_COL', 'code_produit_garantie' => 'SECU', 'libelle' => 'SECURITE', 'est_obligatoire' => 1, 'nature_garantie' => 'Principale Obligatoire', 'type' => 'Epargne', 'age_min' => 12, 'age_max' => 90, 'duree_cotisation_min' => 6, 'duree_cotisation_max' => 60, 'duree_contrat_min' => 6, 'duree_contrat_max' => 60, 'branche' => 'COL'],

        ['code_produit' => 'PFA_COL', 'code_produit_garantie' => 'PERF', 'libelle' => 'PERFORMANCE', 'est_obligatoire' => 1, 'nature_garantie' => 'Complementaire Obligatoire', 'type' => 'UC', 'age_min' => 12, 'age_max' => 90, 'duree_cotisation_min' => 6, 'duree_cotisation_max' => 60, 'duree_contrat_min' => 6, 'duree_contrat_max' => 60, 'branche' => 'COL'],

        ['code_produit' => 'PFA_COL', 'code_produit_garantie' => 'SUR', 'libelle' => 'SURETE', 'est_obligatoire' => 0, 'nature_garantie' => 'Complementaire facultatif', 'type' => 'Deces', 'age_min' => 12, 'age_max' => 90, 'duree_cotisation_min' => 6, 'duree_cotisation_max' => 60, 'duree_contrat_min' => 6, 'duree_contrat_max' => 60, 'branche' => 'COL'],

        ['code_produit' => 'PFA_IND', 'code_produit_garantie' => 'SECU', 'libelle' => 'SECURITE', 'est_obligatoire' => 1, 'nature_garantie' => 'Principale Obligatoire', 'type' => 'Epargne', 'age_min' => 12, 'age_max' => 90, 'duree_cotisation_min' => 6, 'duree_cotisation_max' => 60, 'duree_contrat_min' => 6, 'duree_contrat_max' => 60, 'branche' => 'IND'],

        ['code_produit' => 'PFA_IND', 'code_produit_garantie' => 'PERF', 'libelle' => 'PERFORMANCE', 'est_obligatoire' => 1, 'nature_garantie' => 'Complementaire Obligatoire', 'type' => 'UC', 'age_min' => 12, 'age_max' => 90, 'duree_cotisation_min' => 6, 'duree_cotisation_max' => 60, 'duree_contrat_min' => 6, 'duree_contrat_max' => 60, 'branche' => 'IND'],

        ['code_produit' => 'PFA_IND', 'code_produit_garantie' => 'SUR', 'libelle' => 'SURETE', 'est_obligatoire' => 0, 'nature_garantie' => 'Complementaire facultatif', 'type' => 'Deces', 'age_min' => 12, 'age_max' => 90, 'duree_cotisation_min' => 6, 'duree_cotisation_max' => 60, 'duree_contrat_min' => 6, 'duree_contrat_max' => 60, 'branche' => 'IND'],

        ['code_produit' => 'PRR_1997', 'code_produit_garantie' => 'ER', 'libelle' => 'Epargne Retraite', 'est_obligatoire' => 1, 'nature_garantie' => 'Principale Obligatoire', 'type' => 'Epargne', 'age_min' => 12, 'age_max' => 90, 'duree_cotisation_min' => 6, 'duree_cotisation_max' => 60, 'duree_contrat_min' => 6, 'duree_contrat_max' => 60, 'branche' => 'IND'],

        ['code_produit' => 'PRR_1997', 'code_produit_garantie' => 'FR', 'libelle' => 'Fonds Réalisable', 'est_obligatoire' => 1, 'nature_garantie' => 'Complementaire Obligatoire', 'type' => 'Epargne', 'age_min' => 12, 'age_max' => 90, 'duree_cotisation_min' => 6, 'duree_cotisation_max' => 60, 'duree_contrat_min' => 6, 'duree_contrat_max' => 60, 'branche' => 'IND'],

        ['code_produit' => 'PRR_1997', 'code_produit_garantie' => 'DECES', 'libelle' => 'Deces Toutes Causes-Invalidité Absolue et Définitive', 'est_obligatoire' => 0, 'nature_garantie' => 'Complementaire facultatif', 'type' => 'Deces', 'age_min' => 12, 'age_max' => 90, 'duree_cotisation_min' => 6, 'duree_cotisation_max' => 60, 'duree_contrat_min' => 6, 'duree_contrat_max' => 60, 'branche' => 'IND'],

        ['code_produit' => 'PVRBNI', 'code_produit_garantie' => 'EPRG', 'libelle' => 'Epargne', 'est_obligatoire' => 1, 'nature_garantie' => 'Principale Obligatoire', 'type' => 'Epargne', 'age_min' => 18, 'age_max' => 65, 'duree_cotisation_min' => 5, 'duree_cotisation_max' => 99, 'duree_contrat_min' => 5, 'duree_contrat_max' => 99, 'branche' => 'BANKASS'],

        ['code_produit' => 'SECURICPTE', 'code_produit_garantie' => 'OBSQ_SECURICPTE', 'libelle' => 'Obsèques SECURICPTE', 'est_obligatoire' => 1, 'nature_garantie' => 'Principale Obligatoire', 'type' => 'Deces', 'age_min' => 18, 'age_max' => 65, 'duree_cotisation_min' => 0, 'duree_cotisation_max' => 99, 'duree_contrat_min' => 0, 'duree_contrat_max' => 99, 'branche' => 'BANKASS1'],

        ['code_produit' => 'SUPMIXTE', 'code_produit_garantie' => 'EPARGNE', 'libelle' => 'Epargne', 'est_obligatoire' => 1, 'nature_garantie' => 'Principale Obligatoire', 'type' => 'Epargne', 'age_min' => 12, 'age_max' => 90, 'duree_cotisation_min' => 6, 'duree_cotisation_max' => 60, 'duree_contrat_min' => 6, 'duree_contrat_max' => 60, 'branche' => 'IND'],

        ['code_produit' => 'SUPMIXTE', 'code_produit_garantie' => 'DECES', 'libelle' => 'Deces', 'est_obligatoire' => 0, 'nature_garantie' => 'Complementaire facultatif', 'type' => 'Deces', 'age_min' => 12, 'age_max' => 90, 'duree_cotisation_min' => 6, 'duree_cotisation_max' => 60, 'duree_contrat_min' => 6, 'duree_contrat_max' => 60, 'branche' => 'IND'],

        ['code_produit' => 'SUPMIXTE', 'code_produit_garantie' => 'DECES ACCID', 'libelle' => 'DECES ACCIDENTEL', 'est_obligatoire' => 0, 'nature_garantie' => 'Complementaire facultatif', 'type' => 'Deces', 'age_min' => 12, 'age_max' => 90, 'duree_cotisation_min' => 6, 'duree_cotisation_max' => 60, 'duree_contrat_min' => 6, 'duree_contrat_max' => 60, 'branche' => 'IND'],

        ['code_produit' => 'TTT_1995', 'code_produit_garantie' => 'CER', 'libelle' => 'Compte Epargne Retraite', 'est_obligatoire' => 1, 'nature_garantie' => 'Principale Obligatoire', 'type' => 'Epargne', 'age_min' => 12, 'age_max' => 90, 'duree_cotisation_min' => 1, 'duree_cotisation_max' => 8, 'duree_contrat_min' => 1, 'duree_contrat_max' => 8, 'branche' => 'IND'],

        ['code_produit' => 'TTT_1995', 'code_produit_garantie' => 'CRI', 'libelle' => 'Compte à Revenu Immédiat', 'est_obligatoire' => 0, 'nature_garantie' => 'Complementaire facultatif', 'type' => 'Epargne', 'age_min' => 12, 'age_max' => 90, 'duree_cotisation_min' => 1, 'duree_cotisation_max' => 8, 'duree_contrat_min' => 1, 'duree_contrat_max' => 8, 'branche' => 'IND'],

        ['code_produit' => 'YKC_2006', 'code_produit_garantie' => 'OBSQ', 'libelle' => 'Obsèques Adhérent', 'est_obligatoire' => 1, 'nature_garantie' => 'Principale Obligatoire', 'type' => 'Deces', 'age_min' => 18, 'age_max' => 65, 'duree_cotisation_min' => 0, 'duree_cotisation_max' => 99, 'duree_contrat_min' => 0, 'duree_contrat_max' => 99, 'branche' => 'COL'],

        ['code_produit' => 'YKC_2006', 'code_produit_garantie' => 'OBSQCONJT', 'libelle' => 'Obsèques Conjoint', 'est_obligatoire' => 0, 'nature_garantie' => 'Complementaire facultatif', 'type' => 'Deces', 'age_min' => 18, 'age_max' => 65, 'duree_cotisation_min' => 0, 'duree_cotisation_max' => 99, 'duree_contrat_min' => 0, 'duree_contrat_max' => 99, 'branche' => 'COL'],

        ['code_produit' => 'YKC_2006', 'code_produit_garantie' => 'OBSQENFT', 'libelle' => 'Obsèques Enfant', 'est_obligatoire' => 0, 'nature_garantie' => 'Complementaire facultatif', 'type' => 'Deces', 'age_min' => 3, 'age_max' => 25, 'duree_cotisation_min' => 0, 'duree_cotisation_max' => 99, 'duree_contrat_min' => 0, 'duree_contrat_max' => 99, 'branche' => 'COL'],

        ['code_produit' => 'YKC_2006', 'code_produit_garantie' => 'REMB', 'libelle' => 'Remboursement', 'est_obligatoire' => 0, 'nature_garantie' => 'Complementaire facultatif', 'type' => 'Epargne', 'age_min' => 18, 'age_max' => 65, 'duree_cotisation_min' => 0, 'duree_cotisation_max' => 99, 'duree_contrat_min' => 0, 'duree_contrat_max' => 99, 'branche' => 'COL'],

        ['code_produit' => 'YKE_2008', 'code_produit_garantie' => 'HOMMAGE', 'libelle' => 'HOMMAGE', 'est_obligatoire' => 1, 'nature_garantie' => 'Principale Obligatoire', 'type' => 'Mixte', 'age_min' => 12, 'age_max' => 75, 'duree_cotisation_min' => 5, 'duree_cotisation_max' => 5, 'duree_contrat_min' => 5, 'duree_contrat_max' => 99, 'branche' => 'IND'],

        ['code_produit' => 'YKE_2008', 'code_produit_garantie' => 'SURETE', 'libelle' => 'SURETE', 'est_obligatoire' => 1, 'nature_garantie' => 'Complementaire Obligatoire', 'type' => 'Mixte', 'age_min' => 12, 'age_max' => 75, 'duree_cotisation_min' => 5, 'duree_cotisation_max' => 5, 'duree_contrat_min' => 5, 'duree_contrat_max' => 99, 'branche' => 'IND'],

        ['code_produit' => 'YKE_2008', 'code_produit_garantie' => 'SENIOR', 'libelle' => 'SENIOR', 'est_obligatoire' => 0, 'nature_garantie' => 'Complementaire facultatif', 'type' => 'Epargne', 'age_min' => 75, 'age_max' => 90, 'duree_cotisation_min' => 5, 'duree_cotisation_max' => 5, 'duree_contrat_min' => 5, 'duree_contrat_max' => 99, 'branche' => 'IND'],

        ['code_produit' => 'YKF_2004', 'code_produit_garantie' => 'OBSQ', 'libelle' => 'Obsèques', 'est_obligatoire' => 1, 'nature_garantie' => 'Principale Obligatoire', 'type' => 'Deces', 'age_min' => 18, 'age_max' => 65, 'duree_cotisation_min' => 10, 'duree_cotisation_max' => 10, 'duree_contrat_min' => 10, 'duree_contrat_max' => 10, 'branche' => 'BANKASS'],

        ['code_produit' => 'YKF_2004', 'code_produit_garantie' => 'REMB', 'libelle' => 'Remboursement', 'est_obligatoire' => 0, 'nature_garantie' => 'Complementaire facultatif', 'type' => 'Epargne', 'age_min' => 18, 'age_max' => 65, 'duree_cotisation_min' => 10, 'duree_cotisation_max' => 10, 'duree_contrat_min' => 10, 'duree_contrat_max' => 10, 'branche' => 'BANKASS'],

        ['code_produit' => 'YKF_2004', 'code_produit_garantie' => 'OBSQENFT', 'libelle' => 'Obsèques Enfant', 'est_obligatoire' => 0, 'nature_garantie' => 'Complementaire facultatif', 'type' => 'Deces', 'age_min' => 3, 'age_max' => 21, 'duree_cotisation_min' => 10, 'duree_cotisation_max' => 10, 'duree_contrat_min' => 10, 'duree_contrat_max' => 10, 'branche' => 'BANKASS'],

        ['code_produit' => 'YKF_2004', 'code_produit_garantie' => 'OBSQCONJT', 'libelle' => 'Obsèques conjoint', 'est_obligatoire' => 0, 'nature_garantie' => 'Complementaire facultatif', 'type' => 'Deces', 'age_min' => 18, 'age_max' => 65, 'duree_cotisation_min' => 10, 'duree_cotisation_max' => 10, 'duree_contrat_min' => 10, 'duree_contrat_max' => 10, 'branche' => 'BANKASS'],

        ['code_produit' => 'YKF_2008', 'code_produit_garantie' => 'OBSQENFT', 'libelle' => 'Obsèques enfant', 'est_obligatoire' => 0, 'nature_garantie' => 'Complementaire facultatif', 'type' => 'Deces', 'age_min' => 3, 'age_max' => 25, 'duree_cotisation_min' => 10, 'duree_cotisation_max' => 10, 'duree_contrat_min' => 10, 'duree_contrat_max' => 10, 'branche' => 'IND'],

        ['code_produit' => 'YKF_2008', 'code_produit_garantie' => 'OBSQ', 'libelle' => 'Obsèques Principale', 'est_obligatoire' => 1, 'nature_garantie' => 'Principale Obligatoire', 'type' => 'Deces', 'age_min' => 18, 'age_max' => 65, 'duree_cotisation_min' => 10, 'duree_cotisation_max' => 10, 'duree_contrat_min' => 10, 'duree_contrat_max' => 10, 'branche' => 'IND'],

        ['code_produit' => 'YKF_2008', 'code_produit_garantie' => 'REMB', 'libelle' => 'Remboursement', 'est_obligatoire' => 0, 'nature_garantie' => 'Complementaire facultatif', 'type' => 'Epargne', 'age_min' => 18, 'age_max' => 65, 'duree_cotisation_min' => 10, 'duree_cotisation_max' => 10, 'duree_contrat_min' => 10, 'duree_contrat_max' => 10, 'branche' => 'IND'],

        ['code_produit' => 'YKF_2008', 'code_produit_garantie' => 'OBSQCONJT', 'libelle' => 'Obsèques conjoint', 'est_obligatoire' => 0, 'nature_garantie' => 'Complementaire facultatif', 'type' => 'Deces', 'age_min' => 18, 'age_max' => 65, 'duree_cotisation_min' => 10, 'duree_cotisation_max' => 10, 'duree_contrat_min' => 10, 'duree_contrat_max' => 10, 'branche' => 'IND'],

        ['code_produit' => 'YKL_2004', 'code_produit_garantie' => 'OBSQ', 'libelle' => 'Obsèques', 'est_obligatoire' => 1, 'nature_garantie' => 'Principale Obligatoire', 'type' => 'Deces', 'age_min' => 18, 'age_max' => 65, 'duree_cotisation_min' => 10, 'duree_cotisation_max' => 10, 'duree_contrat_min' => 10, 'duree_contrat_max' => 10, 'branche' => 'BANKASS'],

        ['code_produit' => 'YKL_2004', 'code_produit_garantie' => 'REMB', 'libelle' => 'Remboursement', 'est_obligatoire' => 0, 'nature_garantie' => 'Complementaire facultatif', 'type' => 'Epargne', 'age_min' => 18, 'age_max' => 65, 'duree_cotisation_min' => 10, 'duree_cotisation_max' => 10, 'duree_contrat_min' => 10, 'duree_contrat_max' => 10, 'branche' => 'BANKASS'],

        ['code_produit' => 'YKS_2008', 'code_produit_garantie' => 'OBSQ', 'libelle' => 'Obsèques', 'est_obligatoire' => 1, 'nature_garantie' => 'Principale Obligatoire', 'type' => 'Deces', 'age_min' => 12, 'age_max' => 65, 'duree_cotisation_min' => 10, 'duree_cotisation_max' => 10, 'duree_contrat_min' => 10, 'duree_contrat_max' => 10, 'branche' => 'IND'],

        ['code_produit' => 'YKS_2008', 'code_produit_garantie' => 'REMB', 'libelle' => 'Remboursement', 'est_obligatoire' => 0, 'nature_garantie' => 'Complementaire facultatif', 'type' => 'Epargne', 'age_min' => 12, 'age_max' => 65, 'duree_cotisation_min' => 10, 'duree_cotisation_max' => 10, 'duree_contrat_min' => 10, 'duree_contrat_max' => 10, 'branche' => 'IND'],

        ['code_produit' => 'YKS_2008', 'code_produit_garantie' => 'SENIOR', 'libelle' => 'SENIOR', 'est_obligatoire' => 0, 'nature_garantie' => 'Complementaire facultatif', 'type' => 'Epargne', 'age_min' => 75, 'age_max' => 90, 'duree_cotisation_min' => 10, 'duree_cotisation_max' => 10, 'duree_contrat_min' => 10, 'duree_contrat_max' => 10, 'branche' => 'IND'],

        ['code_produit' => 'YKV_2004', 'code_produit_garantie' => 'REMB', 'libelle' => 'Remboursement', 'est_obligatoire' => 0, 'nature_garantie' => 'Complementaire facultatif', 'type' => 'Epargne', 'age_min' => 18, 'age_max' => 65, 'duree_cotisation_min' => 10, 'duree_cotisation_max' => 10, 'duree_contrat_min' => 10, 'duree_contrat_max' => 10, 'branche' => 'BANKASS'],

        ['code_produit' => 'YKV_2004', 'code_produit_garantie' => 'OBSQ', 'libelle' => 'Obsèques', 'est_obligatoire' => 1, 'nature_garantie' => 'Principale Obligatoire', 'type' => 'Deces', 'age_min' => 18, 'age_max' => 65, 'duree_cotisation_min' => 10, 'duree_cotisation_max' => 10, 'duree_contrat_min' => 10, 'duree_contrat_max' => 10, 'branche' => 'BANKASS'],

        ['code_produit' => 'CAD_EDUCPLUS', 'code_produit_garantie' => 'ETUDE', 'libelle' => 'ETUDE', 'est_obligatoire' => 1, 'nature_garantie' => 'Principale Obligatoire', 'type' => 'Epargne', 'age_min' => 20, 'age_max' => 55, 'duree_cotisation_min' => 10, 'duree_cotisation_max' => 20, 'duree_contrat_min' => 10, 'duree_contrat_max' => 20, 'branche' => 'IND'],

        ['code_produit' => 'CAD_EDUCPLUS', 'code_produit_garantie' => 'SURETEPLUS', 'libelle' => 'SURETE', 'est_obligatoire' => 1, 'nature_garantie' => 'Complementaire Obligatoire', 'type' => 'Deces', 'age_min' => 20, 'age_max' => 55, 'duree_cotisation_min' => 10, 'duree_cotisation_max' => 20, 'duree_contrat_min' => 10, 'duree_contrat_max' => 20, 'branche' => 'IND'],

        ['code_produit' => 'CAD_EDUCPLUS', 'code_produit_garantie' => 'RENTE', 'libelle' => 'RENTE', 'est_obligatoire' => 1, 'nature_garantie' => 'Complementaire Obligatoire', 'type' => 'Deces', 'age_min' => 20, 'age_max' => 55, 'duree_cotisation_min' => 10, 'duree_cotisation_max' => 20, 'duree_contrat_min' => 10, 'duree_contrat_max' => 20, 'branche' => 'IND'],

        ['code_produit' => 'CAD_EDUCPLUS', 'code_produit_garantie' => 'OBSQEDUPLUS', 'libelle' => 'OBSEQUES', 'est_obligatoire' => 1, 'nature_garantie' => 'Complementaire Obligatoire', 'type' => 'Deces', 'age_min' => 20, 'age_max' => 55, 'duree_cotisation_min' => 10, 'duree_cotisation_max' => 20, 'duree_contrat_min' => 10, 'duree_contrat_max' => 20, 'branche' => 'IND'],

        ['code_produit' => 'DOIHOO', 'code_produit_garantie' => 'INV_2020', 'libelle' => 'INVEST', 'est_obligatoire' => 1, 'nature_garantie' => 'Principale Obligatoire', 'type' => 'Epargne', 'age_min' => 18, 'age_max' => 99, 'duree_cotisation_min' => 8, 'duree_cotisation_max' => 8, 'duree_contrat_min' => 8, 'duree_contrat_max' => 8, 'branche' => 'IND'],

        ['code_produit' => 'DOIHOO', 'code_produit_garantie' => 'DOI_2020', 'libelle' => 'DOIHOO', 'est_obligatoire' => 1, 'nature_garantie' => 'Complementaire Obligatoire', 'type' => 'Deces', 'age_min' => 18, 'age_max' => 99, 'duree_cotisation_min' => 8, 'duree_cotisation_max' => 8, 'duree_contrat_min' => 8, 'duree_contrat_max' => 8, 'branche' => 'IND'],

        ['code_produit' => 'YKE_2018', 'code_produit_garantie' => 'HOMMAGE', 'libelle' => 'Hommage', 'est_obligatoire' => 1, 'nature_garantie' => 'Principale Obligatoire', 'type' => 'Mixte', 'age_min' => 12, 'age_max' => 106, 'duree_cotisation_min' => 5, 'duree_cotisation_max' => 5, 'duree_contrat_min' => 5, 'duree_contrat_max' => 5, 'branche' => 'IND'],

        ['code_produit' => 'YKE_2018', 'code_produit_garantie' => 'SURETE', 'libelle' => 'Sureté', 'est_obligatoire' => 1, 'nature_garantie' => 'Complementaire Obligatoire', 'type' => 'Mixte', 'age_min' => 12, 'age_max' => 106, 'duree_cotisation_min' => 5, 'duree_cotisation_max' => 5, 'duree_contrat_min' => 5, 'duree_contrat_max' => 5, 'branche' => 'IND'],

        ['code_produit' => 'YKE_2018', 'code_produit_garantie' => 'SENIOR', 'libelle' => 'Sénior', 'est_obligatoire' => 0, 'nature_garantie' => 'Complementaire facultatif', 'type' => 'Epargne', 'age_min' => 12, 'age_max' => 106, 'duree_cotisation_min' => 5, 'duree_cotisation_max' => 5, 'duree_contrat_min' => 5, 'duree_contrat_max' => 5, 'branche' => 'IND'],

        ['code_produit' => 'YKS_2018', 'code_produit_garantie' => 'OBSQ', 'libelle' => 'Obsèques', 'est_obligatoire' => 1, 'nature_garantie' => 'Principale Obligatoire', 'type' => 'Deces', 'age_min' => 12, 'age_max' => 75, 'duree_cotisation_min' => 10, 'duree_cotisation_max' => 10, 'duree_contrat_min' => 10, 'duree_contrat_max' => 10, 'branche' => 'IND'],

        ['code_produit' => 'CADENCE', 'code_produit_garantie' => 'LIB', 'libelle' => 'Liberté', 'est_obligatoire' => 1, 'nature_garantie' => 'Principale Obligatoire', 'type' => 'Epargne', 'age_min' => 12, 'age_max' => 90, 'duree_cotisation_min' => 6, 'duree_cotisation_max' => 60, 'duree_contrat_min' => 6, 'duree_contrat_max' => 60, 'branche' => 'COURTAGE'],

        ['code_produit' => 'CADENCE', 'code_produit_garantie' => 'DIG', 'libelle' => 'Dignité', 'est_obligatoire' => 1, 'nature_garantie' => 'Complementaire Obligatoire', 'type' => 'Epargne', 'age_min' => 12, 'age_max' => 90, 'duree_cotisation_min' => 6, 'duree_cotisation_max' => 60, 'duree_contrat_min' => 6, 'duree_contrat_max' => 60, 'branche' => 'COURTAGE'],

        ['code_produit' => 'CADENCE', 'code_produit_garantie' => 'SUR', 'libelle' => 'Sureté', 'est_obligatoire' => 0, 'nature_garantie' => 'Complementaire facultatif', 'type' => 'Deces', 'age_min' => 12, 'age_max' => 90, 'duree_cotisation_min' => 6, 'duree_cotisation_max' => 60, 'duree_contrat_min' => 6, 'duree_contrat_max' => 60, 'branche' => 'COURTAGE'],

        ['code_produit' => 'CADENCE', 'code_produit_garantie' => 'DECESACC', 'libelle' => 'Deces accidentel', 'est_obligatoire' => 0, 'nature_garantie' => 'Complementaire facultatif', 'type' => 'Deces', 'age_min' => 12, 'age_max' => 90, 'duree_cotisation_min' => 6, 'duree_cotisation_max' => 60, 'duree_contrat_min' => 6, 'duree_contrat_max' => 60, 'branche' => 'COURTAGE'],

        ['code_produit' => 'LBV_1995', 'code_produit_garantie' => 'EPARGNE', 'libelle' => 'EPARGNE', 'est_obligatoire' => 1, 'nature_garantie' => 'Principale Obligatoire', 'type' => 'Epargne', 'age_min' => 12, 'age_max' => 90, 'duree_cotisation_min' => 5, 'duree_cotisation_max' => 60, 'duree_contrat_min' => 5, 'duree_contrat_max' => 60, 'branche' => 'COURTAGE'],

        ['code_produit' => 'LIBRAVOUS', 'code_produit_garantie' => 'EPARGNE', 'libelle' => 'Epargne', 'est_obligatoire' => 1, 'nature_garantie' => 'Principale Obligatoire', 'type' => 'Epargne', 'age_min' => 12, 'age_max' => 90, 'duree_cotisation_min' => 5, 'duree_cotisation_max' => 60, 'duree_contrat_min' => 5, 'duree_contrat_max' => 60, 'branche' => 'COURTAGE'],

        ['code_produit' => 'PFA_COUTAGE', 'code_produit_garantie' => 'SECU', 'libelle' => 'SECURITE', 'est_obligatoire' => 1, 'nature_garantie' => 'Principale Obligatoire', 'type' => 'Epargne', 'age_min' => 12, 'age_max' => 90, 'duree_cotisation_min' => 6, 'duree_cotisation_max' => 60, 'duree_contrat_min' => 6, 'duree_contrat_max' => 60, 'branche' => 'COURTAGE'],

        ['code_produit' => 'PFA_COUTAGE', 'code_produit_garantie' => 'PERF', 'libelle' => 'PERFORMANCE', 'est_obligatoire' => 1, 'nature_garantie' => 'Complementaire Obligatoire', 'type' => 'UC', 'age_min' => 12, 'age_max' => 90, 'duree_cotisation_min' => 6, 'duree_cotisation_max' => 60, 'duree_contrat_min' => 6, 'duree_contrat_max' => 60, 'branche' => 'COURTAGE'],

        ['code_produit' => 'PFA_COUTAGE', 'code_produit_garantie' => 'SUR', 'libelle' => 'SURETE', 'est_obligatoire' => 0, 'nature_garantie' => 'Complementaire facultatif', 'type' => 'Deces', 'age_min' => 12, 'age_max' => 90, 'duree_cotisation_min' => 6, 'duree_cotisation_max' => 60, 'duree_contrat_min' => 6, 'duree_contrat_max' => 60, 'branche' => 'COURTAGE'],

        ['code_produit' => 'PRR_1997', 'code_produit_garantie' => 'ER', 'libelle' => 'Epargne Retraite', 'est_obligatoire' => 1, 'nature_garantie' => 'Principale Obligatoire', 'type' => 'Epargne', 'age_min' => 12, 'age_max' => 90, 'duree_cotisation_min' => 6, 'duree_cotisation_max' => 60, 'duree_contrat_min' => 6, 'duree_contrat_max' => 60, 'branche' => 'COURTAGE'],

        ['code_produit' => 'PRR_1997', 'code_produit_garantie' => 'FR', 'libelle' => 'Fonds Réalisable', 'est_obligatoire' => 1, 'nature_garantie' => 'Complementaire Obligatoire', 'type' => 'Epargne', 'age_min' => 12, 'age_max' => 90, 'duree_cotisation_min' => 6, 'duree_cotisation_max' => 60, 'duree_contrat_min' => 6, 'duree_contrat_max' => 60, 'branche' => 'COURTAGE'],

        ['code_produit' => 'YKE_2018', 'code_produit_garantie' => 'HOMMAGE', 'libelle' => 'Hommage', 'est_obligatoire' => 1, 'nature_garantie' => 'Principale Obligatoire', 'type' => 'Mixte', 'age_min' => 12, 'age_max' => 106, 'duree_cotisation_min' => 5, 'duree_cotisation_max' => 5, 'duree_contrat_min' => 5, 'duree_contrat_max' => 5, 'branche' => 'COURTAGE'],

        ['code_produit' => 'YKE_2018', 'code_produit_garantie' => 'SURETE', 'libelle' => 'Sureté', 'est_obligatoire' => 1, 'nature_garantie' => 'Complementaire Obligatoire', 'type' => 'Mixte', 'age_min' => 12, 'age_max' => 106, 'duree_cotisation_min' => 5, 'duree_cotisation_max' => 5, 'duree_contrat_min' => 5, 'duree_contrat_max' => 5, 'branche' => 'COURTAGE'],

        ['code_produit' => 'YKE_2018', 'code_produit_garantie' => 'SENIOR', 'libelle' => 'Sénior', 'est_obligatoire' => 0, 'nature_garantie' => 'Complementaire facultatif', 'type' => 'Epargne', 'age_min' => 12, 'age_max' => 106, 'duree_cotisation_min' => 5, 'duree_cotisation_max' => 5, 'duree_contrat_min' => 5, 'duree_contrat_max' => 5, 'branche' => 'COURTAGE'],

        ['code_produit' => 'YKS_2008', 'code_produit_garantie' => 'OBSQ', 'libelle' => 'Obsèques', 'est_obligatoire' => 1, 'nature_garantie' => 'Principale Obligatoire', 'type' => 'Deces', 'age_min' => 12, 'age_max' => 65, 'duree_cotisation_min' => 10, 'duree_cotisation_max' => 10, 'duree_contrat_min' => 10, 'duree_contrat_max' => 10, 'branche' => 'COURTAGE'],

        ['code_produit' => 'YKS_2008', 'code_produit_garantie' => 'REMB', 'libelle' => 'Remboursement', 'est_obligatoire' => 0, 'nature_garantie' => 'Complementaire facultatif', 'type' => 'Epargne', 'age_min' => 12, 'age_max' => 65, 'duree_cotisation_min' => 10, 'duree_cotisation_max' => 10, 'duree_contrat_min' => 10, 'duree_contrat_max' => 10, 'branche' => 'COURTAGE'],

        ['code_produit' => 'YKS_2008', 'code_produit_garantie' => 'SENIOR', 'libelle' => 'SENIOR', 'est_obligatoire' => 0, 'nature_garantie' => 'Complementaire facultatif', 'type' => 'Epargne', 'age_min' => 75, 'age_max' => 90, 'duree_cotisation_min' => 10, 'duree_cotisation_max' => 10, 'duree_contrat_min' => 10, 'duree_contrat_max' => 10, 'branche' => 'COURTAGE'],

        ['code_produit' => 'CAD_EDUCPLUS', 'code_produit_garantie' => 'ETUDE', 'libelle' => 'ETUDE', 'est_obligatoire' => 1, 'nature_garantie' => 'Principale Obligatoire', 'type' => 'Epargne', 'age_min' => 20, 'age_max' => 55, 'duree_cotisation_min' => 10, 'duree_cotisation_max' => 20, 'duree_contrat_min' => 10, 'duree_contrat_max' => 20, 'branche' => 'COURTAGE'],

        ['code_produit' => 'CAD_EDUCPLUS', 'code_produit_garantie' => 'SURETEPLUS', 'libelle' => 'SURETE', 'est_obligatoire' => 1, 'nature_garantie' => 'Complementaire Obligatoire', 'type' => 'Deces', 'age_min' => 20, 'age_max' => 55, 'duree_cotisation_min' => 10, 'duree_cotisation_max' => 20, 'duree_contrat_min' => 10, 'duree_contrat_max' => 20, 'branche' => 'COURTAGE'],

        ['code_produit' => 'CAD_EDUCPLUS', 'code_produit_garantie' => 'RENTE', 'libelle' => 'RENTE', 'est_obligatoire' => 1, 'nature_garantie' => 'Complementaire Obligatoire', 'type' => 'Deces', 'age_min' => 20, 'age_max' => 55, 'duree_cotisation_min' => 10, 'duree_cotisation_max' => 20, 'duree_contrat_min' => 10, 'duree_contrat_max' => 20, 'branche' => 'COURTAGE'],

        ['code_produit' => 'CAD_EDUCPLUS', 'code_produit_garantie' => 'OBSQEDUPLUS', 'libelle' => 'OBSEQUES', 'est_obligatoire' => 1, 'nature_garantie' => 'Complementaire Obligatoire', 'type' => 'Deces', 'age_min' => 20, 'age_max' => 55, 'duree_cotisation_min' => 10, 'duree_cotisation_max' => 20, 'duree_contrat_min' => 10, 'duree_contrat_max' => 20, 'branche' => 'COURTAGE'],

        ['code_produit' => 'DOIHOO', 'code_produit_garantie' => 'INV_2020', 'libelle' => 'INVEST', 'est_obligatoire' => 1, 'nature_garantie' => 'Principale Obligatoire', 'type' => 'Epargne', 'age_min' => 18, 'age_max' => 99, 'duree_cotisation_min' => 8, 'duree_cotisation_max' => 8, 'duree_contrat_min' => 8, 'duree_contrat_max' => 8, 'branche' => 'COURTAGE'],

        ['code_produit' => 'DOIHOO', 'code_produit_garantie' => 'DOI_2020', 'libelle' => 'DOIHOO', 'est_obligatoire' => 1, 'nature_garantie' => 'Complementaire Obligatoire', 'type' => 'Deces', 'age_min' => 18, 'age_max' => 99, 'duree_cotisation_min' => 8, 'duree_cotisation_max' => 8, 'duree_contrat_min' => 8, 'duree_contrat_max' => 8, 'branche' => 'COURTAGE'],

        ['code_produit' => 'YKE_2008', 'code_produit_garantie' => 'HOMMAGE', 'libelle' => 'HOMMAGE', 'est_obligatoire' => 1, 'nature_garantie' => 'Principale Obligatoire', 'type' => 'Mixte', 'age_min' => 12, 'age_max' => 75, 'duree_cotisation_min' => 5, 'duree_cotisation_max' => 5, 'duree_contrat_min' => 5, 'duree_contrat_max' => 99, 'branche' => 'COURTAGE'],

        ['code_produit' => 'YKE_2008', 'code_produit_garantie' => 'SURETE', 'libelle' => 'SURETE', 'est_obligatoire' => 1, 'nature_garantie' => 'Complementaire Obligatoire', 'type' => 'Mixte', 'age_min' => 12, 'age_max' => 75, 'duree_cotisation_min' => 5, 'duree_cotisation_max' => 5, 'duree_contrat_min' => 5, 'duree_contrat_max' => 99, 'branche' => 'COURTAGE'],

        ['code_produit' => 'YKE_2008', 'code_produit_garantie' => 'SENIOR', 'libelle' => 'SENIOR', 'est_obligatoire' => 0, 'nature_garantie' => 'Complementaire facultatif', 'type' => 'Epargne', 'age_min' => 75, 'age_max' => 90, 'duree_cotisation_min' => 5, 'duree_cotisation_max' => 5, 'duree_contrat_min' => 5, 'duree_contrat_max' => 99, 'branche' => 'COURTAGE'],

        ['code_produit' => 'YKF_2008', 'code_produit_garantie' => 'OBSQENFT', 'libelle' => 'Obsèques enfant', 'est_obligatoire' => 0, 'nature_garantie' => 'Complementaire facultatif', 'type' => 'Deces', 'age_min' => 3, 'age_max' => 25, 'duree_cotisation_min' => 10, 'duree_cotisation_max' => 10, 'duree_contrat_min' => 10, 'duree_contrat_max' => 10, 'branche' => 'COURTAGE'],

        ['code_produit' => 'YKF_2008', 'code_produit_garantie' => 'OBSQ', 'libelle' => 'Obsèques Principale', 'est_obligatoire' => 1, 'nature_garantie' => 'Principale Obligatoire', 'type' => 'Deces', 'age_min' => 18, 'age_max' => 65, 'duree_cotisation_min' => 10, 'duree_cotisation_max' => 10, 'duree_contrat_min' => 10, 'duree_contrat_max' => 10, 'branche' => 'COURTAGE'],

        ['code_produit' => 'YKF_2008', 'code_produit_garantie' => 'REMB', 'libelle' => 'Remboursement', 'est_obligatoire' => 0, 'nature_garantie' => 'Complementaire facultatif', 'type' => 'Epargne', 'age_min' => 18, 'age_max' => 65, 'duree_cotisation_min' => 10, 'duree_cotisation_max' => 10, 'duree_contrat_min' => 10, 'duree_contrat_max' => 10, 'branche' => 'COURTAGE'],

        ['code_produit' => 'YKF_2008', 'code_produit_garantie' => 'OBSQCONJT', 'libelle' => 'Obsèques conjoint', 'est_obligatoire' => 0, 'nature_garantie' => 'Complementaire facultatif', 'type' => 'Deces', 'age_min' => 18, 'age_max' => 65, 'duree_cotisation_min' => 10, 'duree_cotisation_max' => 10, 'duree_contrat_min' => 10, 'duree_contrat_max' => 10, 'branche' => 'COURTAGE'],

        ['code_produit' => 'PFA_COUTAGE', 'code_produit_garantie' => 'SECU', 'libelle' => 'SECURITE', 'est_obligatoire' => 1, 'nature_garantie' => 'Principale Obligatoire', 'type' => 'Epargne', 'age_min' => 12, 'age_max' => 90, 'duree_cotisation_min' => 6, 'duree_cotisation_max' => 60, 'duree_contrat_min' => 6, 'duree_contrat_max' => 60, 'branche' => 'COURTAGE'],

        ['code_produit' => 'PFA_COUTAGE', 'code_produit_garantie' => 'PERF', 'libelle' => 'PERFORMANCE', 'est_obligatoire' => 1, 'nature_garantie' => 'Complementaire Obligatoire', 'type' => 'UC', 'age_min' => 12, 'age_max' => 90, 'duree_cotisation_min' => 6, 'duree_cotisation_max' => 60, 'duree_contrat_min' => 6, 'duree_contrat_max' => 60, 'branche' => 'COURTAGE'],

        ['code_produit' => 'PFA_COUTAGE', 'code_produit_garantie' => 'SUR', 'libelle' => 'SURETE', 'est_obligatoire' => 0, 'nature_garantie' => 'Complementaire facultatif', 'type' => 'Deces', 'age_min' => 12, 'age_max' => 90, 'duree_cotisation_min' => 6, 'duree_cotisation_max' => 60, 'duree_contrat_min' => 6, 'duree_contrat_max' => 60, 'branche' => 'COURTAGE'],

        ['code_produit' => 'PRR_1997', 'code_produit_garantie' => 'ER', 'libelle' => 'Epargne Retraite', 'est_obligatoire' => 1, 'nature_garantie' => 'Principale Obligatoire', 'type' => 'Epargne', 'age_min' => 12, 'age_max' => 90, 'duree_cotisation_min' => 6, 'duree_cotisation_max' => 60, 'duree_contrat_min' => 6, 'duree_contrat_max' => 60, 'branche' => 'COURTAGE'],

        ['code_produit' => 'PRR_1997', 'code_produit_garantie' => 'FR', 'libelle' => 'Fonds Réalisable', 'est_obligatoire' => 1, 'nature_garantie' => 'Complementaire Obligatoire', 'type' => 'Epargne', 'age_min' => 12, 'age_max' => 90, 'duree_cotisation_min' => 6, 'duree_cotisation_max' => 60, 'duree_contrat_min' => 6, 'duree_contrat_max' => 60, 'branche' => 'COURTAGE'],

        ['code_produit' => 'SUPMIXTE', 'code_produit_garantie' => 'EPARGNE', 'libelle' => 'Epargne', 'est_obligatoire' => 1, 'nature_garantie' => 'Principale Obligatoire', 'type' => 'Epargne', 'age_min' => 12, 'age_max' => 90, 'duree_cotisation_min' => 6, 'duree_cotisation_max' => 60, 'duree_contrat_min' => 6, 'duree_contrat_max' => 60, 'branche' => 'COURTAGE'],

        ['code_produit' => 'LFFUN', 'code_produit_garantie' => 'ASSFUN_CONJT', 'libelle' => 'ASSISTANCE FUNERAILLES CONJOINT', 'est_obligatoire' => 0, 'nature_garantie' => 'Complementaire facultatif', 'type' => 'Deces', 'age_min' => 18, 'age_max' => 65, 'duree_cotisation_min' => 0, 'duree_cotisation_max' => 99, 'duree_contrat_min' => 0, 'duree_contrat_max' => 99, 'branche' => 'COURTAGE'],

        ['code_produit' => 'LFFUN', 'code_produit_garantie' => 'ASSFUN_ADH', 'libelle' => 'ASSISTANCE FUNERAILLES ADHERANT', 'est_obligatoire' => 1, 'nature_garantie' => 'Principale Obligatoire', 'type' => 'Deces', 'age_min' => 18, 'age_max' => 65, 'duree_cotisation_min' => 0, 'duree_cotisation_max' => 99, 'duree_contrat_min' => 0, 'duree_contrat_max' => 99, 'branche' => 'COURTAGE'],

        ['code_produit' => 'LFFUN', 'code_produit_garantie' => 'ASSFUN_ENFT', 'libelle' => 'ASSISTANCE FUNERAILLES ENFANT', 'est_obligatoire' => 0, 'nature_garantie' => 'Complementaire facultatif', 'type' => 'Deces', 'age_min' => 18, 'age_max' => 75, 'duree_cotisation_min' => 0, 'duree_cotisation_max' => 99, 'duree_contrat_min' => 0, 'duree_contrat_max' => 99, 'branche' => 'COURTAGE'],

        ['code_produit' => 'LFFUN', 'code_produit_garantie' => 'ASSFUN_ASCDT', 'libelle' => 'ASSISTANCE FUNERAILLES ASCENDANTS', 'est_obligatoire' => 0, 'nature_garantie' => 'Complementaire facultatif', 'type' => 'Deces', 'age_min' => 18, 'age_max' => 75, 'duree_cotisation_min' => 0, 'duree_cotisation_max' => 99, 'duree_contrat_min' => 0, 'duree_contrat_max' => 99, 'branche' => 'COURTAGE'],

        ['code_produit' => 'LPREVO', 'code_produit_garantie' => 'DTC/IAD', 'libelle' => 'DECES TOUTES CAUSES INVALIDITE ABSOLUE ET DEFINITIVE', 'est_obligatoire' => 1, 'nature_garantie' => 'Principale Obligatoire', 'type' => 'Deces', 'age_min' => 18, 'age_max' => 65, 'duree_cotisation_min' => 0, 'duree_cotisation_max' => 99, 'duree_contrat_min' => 0, 'duree_contrat_max' => 99, 'branche' => 'COURTAGE'],

        ['code_produit' => 'LPREVO', 'code_produit_garantie' => 'DACC', 'libelle' => 'DECES ACCIDENTEL', 'est_obligatoire' => 0, 'nature_garantie' => 'Complementaire facultatif', 'type' => 'Deces', 'age_min' => 18, 'age_max' => 65, 'duree_cotisation_min' => 0, 'duree_cotisation_max' => 99, 'duree_contrat_min' => 0, 'duree_contrat_max' => 99, 'branche' => 'COURTAGE'],

        ['code_produit' => 'LPREVO', 'code_produit_garantie' => 'IPP/IPT', 'libelle' => 'INVALIDITE PERMANENTE PARTIELLE/TOTALE', 'est_obligatoire' => 0, 'nature_garantie' => 'Complementaire facultatif', 'type' => 'Deces', 'age_min' => 18, 'age_max' => 65, 'duree_cotisation_min' => 0, 'duree_cotisation_max' => 99, 'duree_contrat_min' => 0, 'duree_contrat_max' => 99, 'branche' => 'COURTAGE'],

        ['code_produit' => 'LOYEMP', 'code_produit_garantie' => 'DECES', 'libelle' => 'DECES', 'est_obligatoire' => 1, 'nature_garantie' => 'Principale Obligatoire', 'type' => 'Deces', 'age_min' => 18, 'age_max' => 70, 'duree_cotisation_min' => 0, 'duree_cotisation_max' => 99, 'duree_contrat_min' => 0, 'duree_contrat_max' => 99, 'branche' => 'COURTAGE'],

        ['code_produit' => 'LPENSION', 'code_produit_garantie' => 'EPR', 'libelle' => 'EPARGNE RETRAITE', 'est_obligatoire' => 1, 'nature_garantie' => 'Principale Obligatoire', 'type' => 'Epargne', 'age_min' => 18, 'age_max' => 65, 'duree_cotisation_min' => 0, 'duree_cotisation_max' => 99, 'duree_contrat_min' => 0, 'duree_contrat_max' => 99, 'branche' => 'COURTAGE'],

        ['code_produit' => 'YKE_2018', 'code_produit_garantie' => 'HOMMAGE', 'libelle' => 'Hommage', 'est_obligatoire' => 1, 'nature_garantie' => 'Principale Obligatoire', 'type' => 'Mixte', 'age_min' => 12, 'age_max' => 106, 'duree_cotisation_min' => 5, 'duree_cotisation_max' => 5, 'duree_contrat_min' => 5, 'duree_contrat_max' => 5, 'branche' => 'BANKASS'],

        ['code_produit' => 'YKE_2018', 'code_produit_garantie' => 'SURETE', 'libelle' => 'Sureté', 'est_obligatoire' => 1, 'nature_garantie' => 'Complementaire Obligatoire', 'type' => 'Mixte', 'age_min' => 12, 'age_max' => 106, 'duree_cotisation_min' => 5, 'duree_cotisation_max' => 5, 'duree_contrat_min' => 5, 'duree_contrat_max' => 5, 'branche' => 'BANKASS'],

        ['code_produit' => 'YKE_2018', 'code_produit_garantie' => 'SENIOR', 'libelle' => 'Sénior', 'est_obligatoire' => 0, 'nature_garantie' => 'Complementaire facultatif', 'type' => 'Epargne', 'age_min' => 74, 'age_max' => 106, 'duree_cotisation_min' => 5, 'duree_cotisation_max' => 5, 'duree_contrat_min' => 5, 'duree_contrat_max' => 5, 'branche' => 'BANKASS'],

        ['code_produit' => 'PVRPRE', 'code_produit_garantie' => 'EPGPLUS', 'libelle' => 'Epargne plus', 'est_obligatoire' => 1, 'nature_garantie' => 'Principale Obligatoire', 'type' => 'Epargne', 'age_min' => 12, 'age_max' => 106, 'duree_cotisation_min' => 5, 'duree_cotisation_max' => 99, 'duree_contrat_min' => 5, 'duree_contrat_max' => 99, 'branche' => 'BANKASS'],

        ['code_produit' => 'PVRPRE', 'code_produit_garantie' => 'PTYS', 'libelle' => 'Protectys', 'est_obligatoire' => 1, 'nature_garantie' => 'Complementaire Obligatoire', 'type' => 'Deces', 'age_min' => 12, 'age_max' => 106, 'duree_cotisation_min' => 5, 'duree_cotisation_max' => 99, 'duree_contrat_min' => 5, 'duree_contrat_max' => 99, 'branche' => 'BANKASS'],

        ['code_produit' => 'PVRPRE', 'code_produit_garantie' => 'PERSIT', 'libelle' => 'Persistance', 'est_obligatoire' => 1, 'nature_garantie' => 'Complementaire Obligatoire', 'type' => 'Bonus', 'age_min' => 12, 'age_max' => 106, 'duree_cotisation_min' => 5, 'duree_cotisation_max' => 99, 'duree_contrat_min' => 5, 'duree_contrat_max' => 99, 'branche' => 'BANKASS'],

        ['code_produit' => 'LPREVO', 'code_produit_garantie' => 'DTC/IAD', 'libelle' => 'DECES TOUTES CAUSES INVALIDITE ABSOLUE ET DEFINITIVE', 'est_obligatoire' => 1, 'nature_garantie' => 'Principale Obligatoire', 'type' => 'Deces', 'age_min' => 18, 'age_max' => 65, 'duree_cotisation_min' => 0, 'duree_cotisation_max' => 99, 'duree_contrat_min' => 0, 'duree_contrat_max' => 99, 'branche' => 'IND'],

                // ASSCPTBNI
        ['code_produit' => 'ASSCPTBNI', 'code_produit_garantie' => 'DECES', 'libelle' => 'Deces', 'est_obligatoire' => 1, 'nature_garantie' => 'Principale Obligatoire', 'type' => 'Deces', 'age_min' => 18, 'age_max' => 65, 'duree_cotisation_min' => 0, 'duree_cotisation_max' => 99, 'duree_contrat_min' => 0, 'duree_contrat_max' => 99, 'branche' => 'COURTAGE'],

        // CRTBANKBNI
        ['code_produit' => 'CRTBANKBNI', 'code_produit_garantie' => 'DECES', 'libelle' => 'Deces', 'est_obligatoire' => 1, 'nature_garantie' => 'Principale Obligatoire', 'type' => 'Deces', 'age_min' => 18, 'age_max' => 65, 'duree_cotisation_min' => 0, 'duree_cotisation_max' => 99, 'duree_contrat_min' => 0, 'duree_contrat_max' => 99, 'branche' => 'COURTAGE'],

        // IFC
        ['code_produit' => 'IFC', 'code_produit_garantie' => 'RET', 'libelle' => 'RETRAITE', 'est_obligatoire' => 1, 'nature_garantie' => 'Principale Obligatoire', 'type' => 'Epargne', 'age_min' => 18, 'age_max' => 65, 'duree_cotisation_min' => 0, 'duree_cotisation_max' => 99, 'duree_contrat_min' => 0, 'duree_contrat_max' => 99, 'branche' => 'COURTAGE'],
        ['code_produit' => 'IFC', 'code_produit_garantie' => 'LIC', 'libelle' => 'LICENCIEMENT', 'est_obligatoire' => 0, 'nature_garantie' => 'Complementaire facultatif', 'type' => 'Epargne', 'age_min' => 18, 'age_max' => 65, 'duree_cotisation_min' => 0, 'duree_cotisation_max' => 99, 'duree_contrat_min' => 0, 'duree_contrat_max' => 99, 'branche' => 'COURTAGE'],
        ['code_produit' => 'IFC', 'code_produit_garantie' => 'DECES', 'libelle' => 'DECES', 'est_obligatoire' => 0, 'nature_garantie' => 'Complementaire facultatif', 'type' => 'Deces', 'age_min' => 18, 'age_max' => 65, 'duree_cotisation_min' => 0, 'duree_cotisation_max' => 99, 'duree_contrat_min' => 0, 'duree_contrat_max' => 99, 'branche' => 'COURTAGE'],

        // PRR_1997 - garantie manquante
        ['code_produit' => 'PRR_1997', 'code_produit_garantie' => 'DECES', 'libelle' => 'Deces Toutes Causes-Invalidité Absolue et Définitive', 'est_obligatoire' => 0, 'nature_garantie' => 'Complementaire facultatif', 'type' => 'Deces', 'age_min' => 12, 'age_max' => 90, 'duree_cotisation_min' => 6, 'duree_cotisation_max' => 60, 'duree_contrat_min' => 6, 'duree_contrat_max' => 60, 'branche' => 'COURTAGE'],

        // PVRBNI
        ['code_produit' => 'PVRBNI', 'code_produit_garantie' => 'EPRG', 'libelle' => 'Epargne', 'est_obligatoire' => 1, 'nature_garantie' => 'Principale Obligatoire', 'type' => 'Epargne', 'age_min' => 18, 'age_max' => 65, 'duree_cotisation_min' => 5, 'duree_cotisation_max' => 99, 'duree_contrat_min' => 5, 'duree_contrat_max' => 99, 'branche' => 'COURTAGE'],

        // SECURICPTE
        ['code_produit' => 'SECURICPTE', 'code_produit_garantie' => 'OBSQ_SECURICPTE', 'libelle' => 'Obsèques SECURICPTE', 'est_obligatoire' => 1, 'nature_garantie' => 'Principale Obligatoire', 'type' => 'Deces', 'age_min' => 18, 'age_max' => 65, 'duree_cotisation_min' => 0, 'duree_cotisation_max' => 99, 'duree_contrat_min' => 0, 'duree_contrat_max' => 99, 'branche' => 'COURTAGE'],

        // SUPMIXTE - garanties manquantes
        ['code_produit' => 'SUPMIXTE', 'code_produit_garantie' => 'DECES', 'libelle' => 'Deces', 'est_obligatoire' => 0, 'nature_garantie' => 'Complementaire facultatif', 'type' => 'Deces', 'age_min' => 12, 'age_max' => 90, 'duree_cotisation_min' => 6, 'duree_cotisation_max' => 60, 'duree_contrat_min' => 6, 'duree_contrat_max' => 60, 'branche' => 'COURTAGE'],
        ['code_produit' => 'SUPMIXTE', 'code_produit_garantie' => 'DECES ACCID', 'libelle' => 'DECES ACCIDENTEL', 'est_obligatoire' => 0, 'nature_garantie' => 'Complementaire facultatif', 'type' => 'Deces', 'age_min' => 12, 'age_max' => 90, 'duree_cotisation_min' => 6, 'duree_cotisation_max' => 60, 'duree_contrat_min' => 6, 'duree_contrat_max' => 60, 'branche' => 'COURTAGE'],

        // TTT_1995
        ['code_produit' => 'TTT_1995', 'code_produit_garantie' => 'CER', 'libelle' => 'Compte Epargne Retraite', 'est_obligatoire' => 1, 'nature_garantie' => 'Principale Obligatoire', 'type' => 'Epargne', 'age_min' => 12, 'age_max' => 90, 'duree_cotisation_min' => 1, 'duree_cotisation_max' => 8, 'duree_contrat_min' => 1, 'duree_contrat_max' => 8, 'branche' => 'COURTAGE'],
        ['code_produit' => 'TTT_1995', 'code_produit_garantie' => 'CRI', 'libelle' => 'Compte à Revenu Immédiat', 'est_obligatoire' => 0, 'nature_garantie' => 'Complementaire facultatif', 'type' => 'Epargne', 'age_min' => 12, 'age_max' => 90, 'duree_cotisation_min' => 1, 'duree_cotisation_max' => 8, 'duree_contrat_min' => 1, 'duree_contrat_max' => 8, 'branche' => 'COURTAGE'],

        // YKC_2006
        ['code_produit' => 'YKC_2006', 'code_produit_garantie' => 'OBSQ', 'libelle' => 'Obsèques Adhérent', 'est_obligatoire' => 1, 'nature_garantie' => 'Principale Obligatoire', 'type' => 'Deces', 'age_min' => 18, 'age_max' => 65, 'duree_cotisation_min' => 0, 'duree_cotisation_max' => 99, 'duree_contrat_min' => 0, 'duree_contrat_max' => 99, 'branche' => 'COURTAGE'],
        ['code_produit' => 'YKC_2006', 'code_produit_garantie' => 'OBSQCONJT', 'libelle' => 'Obsèques Conjoint', 'est_obligatoire' => 0, 'nature_garantie' => 'Complementaire facultatif', 'type' => 'Deces', 'age_min' => 18, 'age_max' => 65, 'duree_cotisation_min' => 0, 'duree_cotisation_max' => 99, 'duree_contrat_min' => 0, 'duree_contrat_max' => 99, 'branche' => 'COURTAGE'],
        ['code_produit' => 'YKC_2006', 'code_produit_garantie' => 'OBSQENFT', 'libelle' => 'Obsèques Enfant', 'est_obligatoire' => 0, 'nature_garantie' => 'Complementaire facultatif', 'type' => 'Deces', 'age_min' => 3, 'age_max' => 25, 'duree_cotisation_min' => 0, 'duree_cotisation_max' => 99, 'duree_contrat_min' => 0, 'duree_contrat_max' => 99, 'branche' => 'COURTAGE'],
        ['code_produit' => 'YKC_2006', 'code_produit_garantie' => 'REMB', 'libelle' => 'Remboursement', 'est_obligatoire' => 0, 'nature_garantie' => 'Complementaire facultatif', 'type' => 'Epargne', 'age_min' => 18, 'age_max' => 65, 'duree_cotisation_min' => 0, 'duree_cotisation_max' => 99, 'duree_contrat_min' => 0, 'duree_contrat_max' => 99, 'branche' => 'COURTAGE'],

        // YKF_2004
        ['code_produit' => 'YKF_2004', 'code_produit_garantie' => 'OBSQ', 'libelle' => 'Obsèques', 'est_obligatoire' => 1, 'nature_garantie' => 'Principale Obligatoire', 'type' => 'Deces', 'age_min' => 18, 'age_max' => 65, 'duree_cotisation_min' => 10, 'duree_cotisation_max' => 10, 'duree_contrat_min' => 10, 'duree_contrat_max' => 10, 'branche' => 'COURTAGE'],
        ['code_produit' => 'YKF_2004', 'code_produit_garantie' => 'REMB', 'libelle' => 'Remboursement', 'est_obligatoire' => 0, 'nature_garantie' => 'Complementaire facultatif', 'type' => 'Epargne', 'age_min' => 18, 'age_max' => 65, 'duree_cotisation_min' => 10, 'duree_cotisation_max' => 10, 'duree_contrat_min' => 10, 'duree_contrat_max' => 10, 'branche' => 'COURTAGE'],
        ['code_produit' => 'YKF_2004', 'code_produit_garantie' => 'OBSQENFT', 'libelle' => 'Obsèques Enfant', 'est_obligatoire' => 0, 'nature_garantie' => 'Complementaire facultatif', 'type' => 'Deces', 'age_min' => 3, 'age_max' => 21, 'duree_cotisation_min' => 10, 'duree_cotisation_max' => 10, 'duree_contrat_min' => 10, 'duree_contrat_max' => 10, 'branche' => 'COURTAGE'],
        ['code_produit' => 'YKF_2004', 'code_produit_garantie' => 'OBSQCONJT', 'libelle' => 'Obsèques conjoint', 'est_obligatoire' => 0, 'nature_garantie' => 'Complementaire facultatif', 'type' => 'Deces', 'age_min' => 18, 'age_max' => 65, 'duree_cotisation_min' => 10, 'duree_cotisation_max' => 10, 'duree_contrat_min' => 10, 'duree_contrat_max' => 10, 'branche' => 'COURTAGE'],

        // YKL_2004
        ['code_produit' => 'YKL_2004', 'code_produit_garantie' => 'OBSQ', 'libelle' => 'Obsèques', 'est_obligatoire' => 1, 'nature_garantie' => 'Principale Obligatoire', 'type' => 'Deces', 'age_min' => 18, 'age_max' => 65, 'duree_cotisation_min' => 10, 'duree_cotisation_max' => 10, 'duree_contrat_min' => 10, 'duree_contrat_max' => 10, 'branche' => 'COURTAGE'],
        ['code_produit' => 'YKL_2004', 'code_produit_garantie' => 'REMB', 'libelle' => 'Remboursement', 'est_obligatoire' => 0, 'nature_garantie' => 'Complementaire facultatif', 'type' => 'Epargne', 'age_min' => 18, 'age_max' => 65, 'duree_cotisation_min' => 10, 'duree_cotisation_max' => 10, 'duree_contrat_min' => 10, 'duree_contrat_max' => 10, 'branche' => 'COURTAGE'],

        // YKV_2004
        ['code_produit' => 'YKV_2004', 'code_produit_garantie' => 'OBSQ', 'libelle' => 'Obsèques', 'est_obligatoire' => 1, 'nature_garantie' => 'Principale Obligatoire', 'type' => 'Deces', 'age_min' => 18, 'age_max' => 65, 'duree_cotisation_min' => 10, 'duree_cotisation_max' => 10, 'duree_contrat_min' => 10, 'duree_contrat_max' => 10, 'branche' => 'COURTAGE'],
        ['code_produit' => 'YKV_2004', 'code_produit_garantie' => 'REMB', 'libelle' => 'Remboursement', 'est_obligatoire' => 0, 'nature_garantie' => 'Complementaire facultatif', 'type' => 'Epargne', 'age_min' => 18, 'age_max' => 65, 'duree_cotisation_min' => 10, 'duree_cotisation_max' => 10, 'duree_contrat_min' => 10, 'duree_contrat_max' => 10, 'branche' => 'COURTAGE'],

        // YKS_2018
        ['code_produit' => 'YKS_2018', 'code_produit_garantie' => 'OBSQ', 'libelle' => 'Obsèques', 'est_obligatoire' => 1, 'nature_garantie' => 'Principale Obligatoire', 'type' => 'Deces', 'age_min' => 12, 'age_max' => 75, 'duree_cotisation_min' => 10, 'duree_cotisation_max' => 10, 'duree_contrat_min' => 10, 'duree_contrat_max' => 10, 'branche' => 'COURTAGE'],

        // PVRPRE
        ['code_produit' => 'PVRPRE', 'code_produit_garantie' => 'EPGPLUS', 'libelle' => 'Epargne plus', 'est_obligatoire' => 1, 'nature_garantie' => 'Principale Obligatoire', 'type' => 'Epargne', 'age_min' => 12, 'age_max' => 106, 'duree_cotisation_min' => 5, 'duree_cotisation_max' => 99, 'duree_contrat_min' => 5, 'duree_contrat_max' => 99, 'branche' => 'COURTAGE'],
        ['code_produit' => 'PVRPRE', 'code_produit_garantie' => 'PTYS', 'libelle' => 'Protectys', 'est_obligatoire' => 1, 'nature_garantie' => 'Complementaire Obligatoire', 'type' => 'Deces', 'age_min' => 12, 'age_max' => 106, 'duree_cotisation_min' => 5, 'duree_cotisation_max' => 99, 'duree_contrat_min' => 5, 'duree_contrat_max' => 99, 'branche' => 'COURTAGE'],
        ['code_produit' => 'PVRPRE', 'code_produit_garantie' => 'PERSIT', 'libelle' => 'Persistance', 'est_obligatoire' => 1, 'nature_garantie' => 'Complementaire Obligatoire', 'type' => 'Bonus', 'age_min' => 12, 'age_max' => 106, 'duree_cotisation_min' => 5, 'duree_cotisation_max' => 99, 'duree_contrat_min' => 5, 'duree_contrat_max' => 99, 'branche' => 'COURTAGE'],
        
        // YKP_2024
        ['code_produit' => 'YKP_2024', 'code_produit_garantie' => 'SUR_CENT', 'libelle' => 'SURETE 100% GAGNANT', 'est_obligatoire' => 1, 'nature_garantie' => 'Principale Obligatoire', 'type' => 'Deces', 'age_min' => 0, 'age_max' => 0, 'duree_cotisation_min' => 0, 'duree_cotisation_max' => 0, 'duree_contrat_min' => 0, 'duree_contrat_max' => 0, 'branche' => 'IND'],

        ['code_produit' => 'YKP_2024', 'code_produit_garantie' => 'REMB_100%', 'libelle' => 'REMBOURSEMENT 100% GAGNANT', 'est_obligatoire' => 1, 'nature_garantie' => 'Complementaire Obligatoire', 'type' => 'KVIE', 'age_min' => 0, 'age_max' => 0, 'duree_cotisation_min' => 0, 'duree_cotisation_max' => 0, 'duree_contrat_min' => 0, 'duree_contrat_max' => 0, 'branche' => 'IND'],

        // INV_2020
        ['code_produit' => 'INV_2020', 'code_produit_garantie' => 'INVEST+', 'libelle' => 'Invest+', 'est_obligatoire' => 1, 'nature_garantie' => 'Principale Obligatoire', 'type' => 'Epargne', 'age_min' => 0, 'age_max' => 0, 'duree_cotisation_min' => 0, 'duree_cotisation_max' => 0, 'duree_contrat_min' => 0, 'duree_contrat_max' => 0, 'branche' => 'IND'],

        ['code_produit' => 'INV_2020', 'code_produit_garantie' => 'SDIG', 'libelle' => 'Services Digitaux', 'est_obligatoire' => 1, 'nature_garantie' => 'Complementaire Obligatoire', 'type' => '', 'age_min' => 0, 'age_max' => 0, 'duree_cotisation_min' => 0, 'duree_cotisation_max' => 0, 'duree_contrat_min' => 0, 'duree_contrat_max' => 0, 'branche' => 'IND'],

        ['code_produit' => 'INV_2020', 'code_produit_garantie' => 'SURETE', 'libelle' => 'Surêté', 'est_obligatoire' => 0, 'nature_garantie' => 'Complementaire facultatif', 'type' => 'KVIE', 'age_min' => 0, 'age_max' => 0, 'duree_cotisation_min' => 0, 'duree_cotisation_max' => 0, 'duree_contrat_min' => 0, 'duree_contrat_max' => 0, 'branche' => 'IND'],

        ['code_produit' => 'INV_2020', 'code_produit_garantie' => 'Exceptionnel', 'libelle' => 'Versement Exceptionnel', 'est_obligatoire' => 0, 'nature_garantie' => 'Complementaire facultatif', 'type' => 'Epargne', 'age_min' => 0, 'age_max' => 0, 'duree_cotisation_min' => 0, 'duree_cotisation_max' => 0, 'duree_contrat_min' => 0, 'duree_contrat_max' => 0, 'branche' => 'IND'],
    ];

    public function run(): void
    {
        $this->command->info('🚀 Début du seeding des garanties produits...');
        $this->command->newLine();

        // Récupérer tous les produits par code pour la résolution du produit_uuid
        $produits = Produit::all()->keyBy('code');

        if ($produits->isEmpty()) {
            $this->command->warn('⚠️  Aucun produit trouvé. Veuillez exécuter ProduitSeeder d\'abord.');
            return;
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $missingProduit = 0;
        $total = count(self::GARANTIES);

        $this->command->info("📋 {$total} garanties à traiter...");
        $this->command->newLine();

        $progressBar = $this->command->getOutput()->createProgressBar($total);
        $progressBar->start();

        $missingCodes = [];

        foreach (self::GARANTIES as $garantieData) {
            $produit = $produits->get($garantieData['code_produit']);

            if (!$produit) {
                $missingProduit++;
                $missingCodes[$garantieData['code_produit']] = true;
                $progressBar->advance();
                continue;
            }

            $data = $this->prepareData($garantieData, $produit->uuid_produit);

            $result = $this->createOrUpdateGarantie($data);

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
        $this->command->info('📊 Résumé du seeding des garanties produits :');
        $this->command->info("  ✅ {$created} garanties créées");
        $this->command->info("  🔄 {$updated} garanties mises à jour");
        $this->command->info("  ⏭️  {$skipped} garanties ignorées");
        if ($missingProduit > 0) {
            $this->command->warn("  ⚠️  {$missingProduit} garanties ignorées (produit introuvable) : " . implode(', ', array_keys($missingCodes)));
        }
        $this->command->info("  📋 {$total} garanties traitées");
        $this->command->newLine();
        $this->command->info('✅ Seed des garanties produits terminé !');
    }

    /**
     * Préparer les données de la garantie
     */
    private function prepareData(array $garantieData, string $produitUuid): array
    {
        return [
            'uuid_produit_garantie' => (string) Str::uuid(),
            'produit_uuid' => $produitUuid,
            'code_produit' => $this->parseString($garantieData['code_produit']),
            'code_produit_garantie' => $this->parseString($garantieData['code_produit_garantie']),
            'libelle' => $this->parseString($garantieData['libelle']),
            'est_obligatoire' => $this->parseBool($garantieData['est_obligatoire']),
            'nature_garantie' => $this->parseString($garantieData['nature_garantie']),
            'type' => $this->parseString($garantieData['type']),
            'age_min' => $this->parseInt($garantieData['age_min']),
            'age_max' => $this->parseInt($garantieData['age_max']),
            'duree_cotisation_min' => $this->parseInt($garantieData['duree_cotisation_min']),
            'duree_cotisation_max' => $this->parseInt($garantieData['duree_cotisation_max']),
            'duree_contrat_min' => $this->parseInt($garantieData['duree_contrat_min']),
            'duree_contrat_max' => $this->parseInt($garantieData['duree_contrat_max']),
            'branche' => $this->parseString($garantieData['branche']),
            'description' => $garantieData['libelle'] . ' - Garantie du produit ' . $garantieData['code_produit'],
            'created_by' => null,
            'updated_by' => null,
            'deleted_by' => null,
        ];
    }

    /**
     * Créer ou mettre à jour une garantie produit
     * Unicité logique : produit_uuid + code_produit_garantie + branche
     * (car un même couple code_produit/code_produit_garantie peut être dupliqué par branche : IND, COL, BANKASS, COURTAGE...)
     */
    private function createOrUpdateGarantie(array $data): string
    {
        $existing = ProduitGarantie::where('produit_uuid', $data['produit_uuid'])
            ->where('code_produit_garantie', $data['code_produit_garantie'])
            ->where('branche', $data['branche'])
            ->first();

        if ($existing) {
            $fieldsToCheck = ['libelle', 'est_obligatoire', 'nature_garantie', 'type', 'age_min', 'age_max', 'duree_cotisation_min', 'duree_cotisation_max', 'duree_contrat_min', 'duree_contrat_max'];
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

        ProduitGarantie::create($data);
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