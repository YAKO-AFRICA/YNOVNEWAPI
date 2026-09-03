<?php
// database/seeders/TypePrestationSeeder.php

namespace Database\Seeders;

use App\Models\Api\Ynov\parameter\CategoryTypePrestation;
use App\Models\Api\Ynov\parameter\TypePrestation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TypePrestationSeeder extends Seeder
{
    /**
     * Mapping des catégories
     */
    private const CATEGORY_MAPPING = [
        'INC' => 'Autres',
        'TECH' => 'Technique',
        'AVT' => 'Administratif',
        'COR' => 'Correction',
    ];


    /**
     * Liste complète des types de prestations
     */
    private const PRESTATIONS = [
        // ============================================================
        // TECHNIQUE (TECH)
        // ============================================================
        [
            'code' => '35',
            'libelle' => 'Arrêt prélèvement (Conservation de capital)',
            'description' => 'Arrêt du prélèvement avec conservation du capital',
            'categorie' => 'TECH',
            'impact' => '0',
            'delai_traitement' => 7,
        ],
        [
            'code' => '31',
            'libelle' => 'Remboursement (trop perçu après Arrêt Contrat)',
            'description' => 'Remboursement des trop perçus après arrêt de contrat',
            'categorie' => 'TECH',
            'impact' => '0',
            'delai_traitement' => 15,
        ],
        [
            'code' => '20',
            'libelle' => 'Avance',
            'description' => 'Demande d\'avance sur contrat',
            'categorie' => 'TECH',
            'impact' => '0',
            'delai_traitement' => 7,
        ],
        [
            'code' => '21',
            'libelle' => 'Dénonciation',
            'description' => 'Dénonciation du contrat',
            'categorie' => 'TECH',
            'impact' => '1',
            'delai_traitement' => 15,
        ],
        [
            'code' => '22',
            'libelle' => 'Renonciation',
            'description' => 'Renonciation au contrat',
            'categorie' => 'TECH',
            'impact' => '1',
            'delai_traitement' => 15,
        ],
        [
            'code' => '23',
            'libelle' => 'Rachat total',
            'description' => 'Rachat total du contrat',
            'categorie' => 'TECH',
            'impact' => '1',
            'delai_traitement' => 15,
        ],
        [
            'code' => '24',
            'libelle' => 'Rachat partiel',
            'description' => 'Rachat partiel du contrat',
            'categorie' => 'TECH',
            'impact' => '0',
            'delai_traitement' => 7,
        ],
        [
            'code' => '25',
            'libelle' => 'Remboursement (Trop perçu après fin cotisation YKF, YKS)',
            'description' => 'Remboursement des trop perçus après fin de cotisation',
            'categorie' => 'TECH',
            'impact' => '0',
            'delai_traitement' => 15,
        ],
        [
            'code' => '26',
            'libelle' => 'Sinistre',
            'description' => 'Déclaration de sinistre',
            'categorie' => 'TECH',
            'impact' => '1',
            'delai_traitement' => 7,
        ],
        [
            'code' => '27',
            'libelle' => 'Terme',
            'description' => 'Terme du contrat',
            'categorie' => 'TECH',
            'impact' => '1',
            'delai_traitement' => 15,
        ],
        [
            'code' => 'COUR',
            'libelle' => 'Remise en vigueur (Réactiver contrat)',
            'description' => 'Réactivation d\'un contrat',
            'categorie' => 'TECH',
            'impact' => '0',
            'delai_traitement' => 7,
        ],
        [
            'code' => '32',
            'libelle' => 'Arrêt Garanties (SENIOR, REMB ...)',
            'description' => 'Arrêt des garanties',
            'categorie' => 'TECH',
            'impact' => '0',
            'delai_traitement' => 15,
        ],
        [
            'code' => '33',
            'libelle' => 'Décès Souscripteur (Reduction de Capital)',
            'description' => 'Décès du souscripteur avec réduction de capital',
            'categorie' => 'TECH',
            'impact' => '0',
            'delai_traitement' => 7,
        ],
        [
            'code' => '34',
            'libelle' => 'Sinistre (Conjoint, Enft, Senior...)',
            'description' => 'Sinistre concernant conjoint, enfant ou senior',
            'categorie' => 'TECH',
            'impact' => '1',
            'delai_traitement' => 7,
        ],
        [
            'code' => '36',
            'libelle' => 'Résiliation',
            'description' => 'Résiliation du contrat',
            'categorie' => 'TECH',
            'impact' => '1',
            'delai_traitement' => 15,
        ],
        [
            'code' => '37',
            'libelle' => 'Terme Cotisations YAKO',
            'description' => 'Terme des cotisations YAKO',
            'categorie' => 'TECH',
            'impact' => '1',
            'delai_traitement' => 15,
        ],
        [
            'code' => 'RENADC',
            'libelle' => 'Annulation contrat (Déduction Commission)',
            'description' => 'Annulation de contrat avec déduction de commission',
            'categorie' => 'TECH',
            'impact' => '1',
            'delai_traitement' => 15,
        ],
        [
            'code' => 'SINSEN',
            'libelle' => 'Sinistre Senior',
            'description' => 'Sinistre pour les contrats Senior',
            'categorie' => 'TECH',
            'impact' => '1',
            'delai_traitement' => 7,
        ],
        [
            'code' => '38',
            'libelle' => 'Décès Souscripteur (Paiement Prestation)',
            'description' => 'Décès du souscripteur avec paiement de prestation',
            'categorie' => 'TECH',
            'impact' => '1',
            'delai_traitement' => 15,
        ],
        [
            'code' => '40',
            'libelle' => 'Remboursement (Trop perçu pendant cotisation)',
            'description' => 'Remboursement des trop perçus pendant la cotisation',
            'categorie' => 'TECH',
            'impact' => '0',
            'delai_traitement' => 15,
        ],
        [
            'code' => '41',
            'libelle' => 'Remboursement (Trop perçu après fin cotisation)',
            'description' => 'Remboursement des trop perçus après fin de cotisation',
            'categorie' => 'TECH',
            'impact' => '0',
            'delai_traitement' => 15,
        ],
        [
            'code' => '42',
            'libelle' => 'Mise en veille (contrat en fin de cotisation)',
            'description' => 'Mise en veille du contrat en fin de cotisation',
            'categorie' => 'TECH',
            'impact' => '0',
            'delai_traitement' => 15,
        ],
        [
            'code' => '43',
            'libelle' => 'Rachat Partiel pour Avance non Liquidée',
            'description' => 'Rachat partiel pour avance non liquidée',
            'categorie' => 'TECH',
            'impact' => '0',
            'delai_traitement' => 7,
        ],
        [
            'code' => '44',
            'libelle' => 'Remboursement (Trop perçu après Sinistre)',
            'description' => 'Remboursement des trop perçus après sinistre',
            'categorie' => 'TECH',
            'impact' => '0',
            'delai_traitement' => 15,
        ],
        [
            'code' => '46',
            'libelle' => 'Transformation INVEST',
            'description' => 'Transformation vers INVEST',
            'categorie' => 'TECH',
            'impact' => '1',
            'delai_traitement' => 7,
        ],
        [
            'code' => '47',
            'libelle' => 'Reduction',
            'description' => 'Réduction de contrat',
            'categorie' => 'TECH',
            'impact' => '0',
            'delai_traitement' => 7,
        ],
        [
            'code' => '48',
            'libelle' => 'Remboursement (Trop perçu après fin Avance)',
            'description' => 'Remboursement des trop perçus après fin d\'avance',
            'categorie' => 'TECH',
            'impact' => '0',
            'delai_traitement' => 15,
        ],
        [
            'code' => '49',
            'libelle' => 'Liquidation',
            'description' => 'Liquidation du contrat',
            'categorie' => 'TECH',
            'impact' => '0',
            'delai_traitement' => 15,
        ],
        [
            'code' => '50',
            'libelle' => 'Tirage Doihoo',
            'description' => 'Tirage DOIHOO',
            'categorie' => 'TECH',
            'impact' => '0',
            'delai_traitement' => 7,
        ],
        [
            'code' => '51',
            'libelle' => 'Transfert VALEUR',
            'description' => 'Transfert de valeur',
            'categorie' => 'TECH',
            'impact' => '0',
            'delai_traitement' => 3,
        ],
        [
            'code' => '52',
            'libelle' => 'Sinistre IFC',
            'description' => 'Sinistre IFC',
            'categorie' => 'TECH',
            'impact' => '0',
            'delai_traitement' => 7,
        ],
        [
            'code' => '53',
            'libelle' => 'Transformation (Rachat Total)',
            'description' => 'Transformation par rachat total',
            'categorie' => 'TECH',
            'impact' => '1',
            'delai_traitement' => 7,
        ],
        [
            'code' => '54',
            'libelle' => 'Transformation (Terme)',
            'description' => 'Transformation par terme',
            'categorie' => 'TECH',
            'impact' => '1',
            'delai_traitement' => 7,
        ],
        [
            'code' => '55',
            'libelle' => 'Transformation (Terme Cotisation YKE)',
            'description' => 'Transformation par terme de cotisation YKE',
            'categorie' => 'TECH',
            'impact' => '1',
            'delai_traitement' => 7,
        ],

        // ============================================================
        // ADMINISTRATIF (AVT)
        // ============================================================
        [
            'code' => '7',
            'libelle' => 'Changement du mode de paiement',
            'description' => 'Modification du mode de paiement',
            'categorie' => 'AVT',
            'impact' => '0',
            'delai_traitement' => 7,
        ],
        [
            'code' => '6',
            'libelle' => 'Changement de date d\'effet',
            'description' => 'Modification de la date d\'effet du contrat',
            'categorie' => 'AVT',
            'impact' => '0',
            'delai_traitement' => 7,
        ],
        [
            'code' => '2',
            'libelle' => 'Changement d\'adresse du souscripteur',
            'description' => 'Modification de l\'adresse du souscripteur',
            'categorie' => 'AVT',
            'impact' => '0',
            'delai_traitement' => 7,
        ],
        [
            'code' => '3',
            'libelle' => 'Changement de contact téléphonique du souscripteur',
            'description' => 'Modification du téléphone du souscripteur',
            'categorie' => 'AVT',
            'impact' => '0',
            'delai_traitement' => 7,
        ],
        [
            'code' => '4',
            'libelle' => 'Rectification du nom de l\'assuré',
            'description' => 'Correction du nom de l\'assuré',
            'categorie' => 'AVT',
            'impact' => '0',
            'delai_traitement' => 7,
        ],
        [
            'code' => '5',
            'libelle' => 'Rectification du lieu de naissance de l\'assuré',
            'description' => 'Correction du lieu de naissance de l\'assuré',
            'categorie' => 'AVT',
            'impact' => '0',
            'delai_traitement' => 7,
        ],
        [
            'code' => '28',
            'libelle' => 'Réduction de prime',
            'description' => 'Réduction du montant de la prime',
            'categorie' => 'AVT',
            'impact' => '0',
            'delai_traitement' => 7,
        ],
        [
            'code' => '29',
            'libelle' => 'Réduction de capital',
            'description' => 'Réduction du capital souscrit',
            'categorie' => 'AVT',
            'impact' => '0',
            'delai_traitement' => 7,
        ],
        [
            'code' => 'SUS',
            'libelle' => 'Suspension',
            'description' => 'Suspension du contrat',
            'categorie' => 'AVT',
            'impact' => '0',
            'delai_traitement' => 7,
        ],
        [
            'code' => 'AV1',
            'libelle' => 'Modification de nom',
            'description' => 'Modification du nom',
            'categorie' => 'AVT',
            'impact' => '0',
            'delai_traitement' => 7,
        ],
        [
            'code' => 'AV2',
            'libelle' => 'Modification de nom, prénom(s)',
            'description' => 'Modification du nom et des prénoms',
            'categorie' => 'AVT',
            'impact' => '0',
            'delai_traitement' => 7,
        ],
        [
            'code' => 'AV3',
            'libelle' => 'Modification adresse, n° tél., lieu de résidence',
            'description' => 'Modification des coordonnées du souscripteur',
            'categorie' => 'AVT',
            'impact' => '0',
            'delai_traitement' => 7,
        ],
        [
            'code' => 'AV4',
            'libelle' => 'Ajout de bénéficiaire',
            'description' => 'Ajout d\'un bénéficiaire au contrat',
            'categorie' => 'AVT',
            'impact' => '0',
            'delai_traitement' => 7,
        ],
        [
            'code' => 'AV5',
            'libelle' => 'Modification de la durée du contrat',
            'description' => 'Modification de la durée du contrat',
            'categorie' => 'AVT',
            'impact' => '0',
            'delai_traitement' => 7,
        ],
        [
            'code' => 'AV6',
            'libelle' => 'Rectification lieu de naissance',
            'description' => 'Correction du lieu de naissance',
            'categorie' => 'AVT',
            'impact' => '0',
            'delai_traitement' => 7,
        ],
        [
            'code' => 'AV7',
            'libelle' => 'Rectification de la filiation',
            'description' => 'Correction de la filiation',
            'categorie' => 'AVT',
            'impact' => '0',
            'delai_traitement' => 7,
        ],
        [
            'code' => 'AV8',
            'libelle' => 'Modification de prime (diminution, augmentation)',
            'description' => 'Modification du montant de la prime',
            'categorie' => 'AVT',
            'impact' => '0',
            'delai_traitement' => 7,
        ],
        [
            'code' => 'AV9',
            'libelle' => 'Modification prime SURETE',
            'description' => 'Modification de la prime SURETE',
            'categorie' => 'AVT',
            'impact' => '0',
            'delai_traitement' => 7,
        ],
        [
            'code' => 'AV10',
            'libelle' => 'Incorporation d\'assuré',
            'description' => 'Incorporation d\'un nouvel assuré',
            'categorie' => 'AVT',
            'impact' => '0',
            'delai_traitement' => 7,
        ],
        [
            'code' => 'AV11',
            'libelle' => 'Modification de périodicité',
            'description' => 'Modification de la périodicité des paiements',
            'categorie' => 'AVT',
            'impact' => '0',
            'delai_traitement' => 7,
        ],
        [
            'code' => 'AV12',
            'libelle' => 'Adjonction de l\'option remboursement',
            'description' => 'Ajout de l\'option de remboursement',
            'categorie' => 'AVT',
            'impact' => '0',
            'delai_traitement' => 7,
        ],
        [
            'code' => 'AV13',
            'libelle' => 'Annulation de la garantie Remboursement',
            'description' => 'Annulation de la garantie remboursement',
            'categorie' => 'AVT',
            'impact' => '0',
            'delai_traitement' => 7,
        ],
        [
            'code' => 'AV14',
            'libelle' => 'Réduction de capital de référence',
            'description' => 'Réduction du capital de référence',
            'categorie' => 'AVT',
            'impact' => '0',
            'delai_traitement' => 7,
        ],
        [
            'code' => 'AV15',
            'libelle' => 'Retrait d\'un assuré',
            'description' => 'Retrait d\'un assuré du contrat',
            'categorie' => 'AVT',
            'impact' => '0',
            'delai_traitement' => 7,
        ],
        [
            'code' => 'AV16',
            'libelle' => 'Modification date de naissance de l\'assuré',
            'description' => 'Correction de la date de naissance de l\'assuré',
            'categorie' => 'AVT',
            'impact' => '0',
            'delai_traitement' => 7,
        ],
        [
            'code' => '39',
            'libelle' => 'Décès Souscripteur (Changement Payeur de Prime)',
            'description' => 'Décès du souscripteur avec changement du payeur',
            'categorie' => 'AVT',
            'impact' => '0',
            'delai_traitement' => 7,
        ],
        [
            'code' => '45',
            'libelle' => 'Transformation',
            'description' => 'Transformation du contrat',
            'categorie' => 'AVT',
            'impact' => '1',
            'delai_traitement' => 7,
        ],

        // ============================================================
        // CORRECTION (COR)
        // ============================================================
        [
            'code' => 'C01',
            'libelle' => 'Correction Etat Garantie',
            'description' => 'Correction de l\'état de la garantie',
            'categorie' => 'COR',
            'impact' => '0',
            'delai_traitement' => 2,
        ],
        [
            'code' => 'C02',
            'libelle' => 'Correction Compte Bancaire',
            'description' => 'Correction du compte bancaire',
            'categorie' => 'COR',
            'impact' => '0',
            'delai_traitement' => null,
        ],
        [
            'code' => 'C03',
            'libelle' => 'Correction Matricule',
            'description' => 'Correction du matricule',
            'categorie' => 'COR',
            'impact' => '0',
            'delai_traitement' => 1,
        ],
        [
            'code' => 'C04',
            'libelle' => 'Correction Code Conseiller',
            'description' => 'Correction du code conseiller',
            'categorie' => 'COR',
            'impact' => '0',
            'delai_traitement' => 1,
        ],
        [
            'code' => 'C05',
            'libelle' => 'Correction Nom Souscripteur',
            'description' => 'Correction du nom du souscripteur',
            'categorie' => 'COR',
            'impact' => '0',
            'delai_traitement' => 1,
        ],
        [
            'code' => 'C06',
            'libelle' => 'Correction Date Effet',
            'description' => 'Correction de la date d\'effet',
            'categorie' => 'COR',
            'impact' => '0',
            'delai_traitement' => 2,
        ],
        [
            'code' => 'C07',
            'libelle' => 'Correction Date de naissance',
            'description' => 'Correction de la date de naissance',
            'categorie' => 'COR',
            'impact' => '0',
            'delai_traitement' => 2,
        ],
        [
            'code' => 'C08',
            'libelle' => 'Correction Ajout Bénéficiaire',
            'description' => 'Correction pour ajout de bénéficiaire',
            'categorie' => 'COR',
            'impact' => '0',
            'delai_traitement' => 2,
        ],
        [
            'code' => 'C09',
            'libelle' => 'Correction Ajout Souscripteur',
            'description' => 'Correction pour ajout de souscripteur',
            'categorie' => 'COR',
            'impact' => '0',
            'delai_traitement' => 2,
        ],
        [
            'code' => 'C10',
            'libelle' => 'Correction Code Produit Formule',
            'description' => 'Correction du code produit formule',
            'categorie' => 'COR',
            'impact' => '0',
            'delai_traitement' => 1,
        ],
        [
            'code' => 'C11',
            'libelle' => 'Correction Etat Encaissement',
            'description' => 'Correction de l\'état d\'encaissement',
            'categorie' => 'COR',
            'impact' => '0',
            'delai_traitement' => 2,
        ],
        [
            'code' => 'C12',
            'libelle' => 'Correction Périodicité',
            'description' => 'Correction de la périodicité',
            'categorie' => 'COR',
            'impact' => '0',
            'delai_traitement' => 1,
        ],
        [
            'code' => 'C13',
            'libelle' => 'Correction Prime',
            'description' => 'Correction de la prime',
            'categorie' => 'COR',
            'impact' => '0',
            'delai_traitement' => 1,
        ],
        [
            'code' => 'C14',
            'libelle' => 'Correction Contrat à Valider',
            'description' => 'Correction du contrat à valider',
            'categorie' => 'COR',
            'impact' => '0',
            'delai_traitement' => null,
        ],
        [
            'code' => 'C15',
            'libelle' => 'Correction Situation matrimoniale',
            'description' => 'Correction de la situation matrimoniale',
            'categorie' => 'COR',
            'impact' => '0',
            'delai_traitement' => 1,
        ],
        [
            'code' => 'C16',
            'libelle' => 'Correction Ajout Capital',
            'description' => 'Correction pour ajout de capital',
            'categorie' => 'COR',
            'impact' => '0',
            'delai_traitement' => 1,
        ],
        [
            'code' => 'C17',
            'libelle' => 'Correction Nom Assuré',
            'description' => 'Correction du nom de l\'assuré',
            'categorie' => 'COR',
            'impact' => '0',
            'delai_traitement' => 1,
        ],
    ];

    public function run(): void
    {
        $this->command->info('🚀 Début du seeding des types de prestations...');
        $this->command->newLine();

        // Récupérer les catégories
        $categories = CategoryTypePrestation::all()->keyBy('code');

        if ($categories->isEmpty()) {
            $this->command->warn('⚠️  Aucune catégorie trouvée. Veuillez exécuter CategoryTypePrestationSeeder d\'abord.');
            return;
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $total = count(self::PRESTATIONS);

        $this->command->info("📋 {$total} types de prestations à traiter...");
        $this->command->newLine();

        $progressBar = $this->command->getOutput()->createProgressBar($total);
        $progressBar->start();

        foreach (self::PRESTATIONS as $prestationData) {
            // Récupérer la catégorie
            $categoryCode = $prestationData['categorie'];
            $category = $categories->get($categoryCode);

            if (!$category) {
                $this->command->warn("  ⚠️  Catégorie '{$categoryCode}' non trouvée pour la prestation '{$prestationData['code']}'");
                $progressBar->advance();
                continue;
            }

            // Déterminer l'impact
            // $impact = $prestationData['impact'];
            // Si impact = 255, on le transforme en 0 (non sortie portefeuille)
            // if ($impact === 255 || $impact === '255') {
            //     $impact = TypePrestation::IMPACT_NON_SORTIE_PORTEFEUILLE;
            // } else {
            //     $impact = TypePrestation::IMPACT_SORTIE_PORTEFEUILLE;
            // }

            // Préparer les données
            $data = [
                'uuid_type_prestation' => (string) Str::uuid(),
                'code' => $prestationData['code'],
                'libelle' => $prestationData['libelle'],
                'description' => $prestationData['description'],
                'category_uuid' => $category->uuid_category_type_prestations,
                'impact' => $prestationData['impact'],
                'delai_traitement' => $prestationData['delai_traitement'],
                'status' => 'actif',
                'created_by' => null,
                'updated_by' => null,
                'deleted_by' => null,
            ];

            // Créer ou mettre à jour
            $result = $this->createOrUpdatePrestation($data);
            
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
        $this->command->info('📊 Résumé du seeding des types de prestations :');
        $this->command->info("  ✅ {$created} types de prestations créés");
        $this->command->info("  🔄 {$updated} types de prestations mis à jour");
        $this->command->info("  ⏭️  {$skipped} types de prestations ignorés");
        $this->command->info("  📋 {$total} types de prestations traités");
        $this->command->newLine();
        $this->command->info('✅ Seed des types de prestations terminé !');
    }

    /**
     * Créer ou mettre à jour un type de prestation
     */
    private function createOrUpdatePrestation(array $data): string
    {
        $existing = TypePrestation::where('code', $data['code'])->first();

        if ($existing) {
            // Vérifier si des champs importants ont changé
            $fieldsToCheck = ['libelle', 'description', 'category_uuid', 'impact', 'delai_traitement'];
            $hasChanged = false;

            foreach ($fieldsToCheck as $field) {
                if (isset($data[$field]) && $existing->$field != $data[$field]) {
                    $hasChanged = true;
                    break;
                }
            }

            if ($hasChanged) {
                $data['updated_by'] = null;
                $existing->update($data);
                return 'updated';
            }
            return 'skipped';
        }

        TypePrestation::create($data);
        return 'created';
    }
}