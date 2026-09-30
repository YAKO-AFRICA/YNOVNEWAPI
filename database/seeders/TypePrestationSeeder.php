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
            'description' => 'Arrêt du prélèvement des cotisations tout en conservant le capital accumulé sur le contrat. Le contrat reste en vigueur mais plus aucun prélèvement n\'est effectué.',
            'categorie' => 'TECH',
            'impact' => '0',
            'delai_traitement' => 7,
        ],
        [
            'code' => '31',
            'libelle' => 'Remboursement (trop perçu après Arrêt Contrat)',
            'description' => 'Remboursement des sommes trop perçues suite à un arrêt de contrat. Calcul et restitution des montants indûment prélevés après la date d\'arrêt effectif.',
            'categorie' => 'TECH',
            'impact' => '0',
            'delai_traitement' => 15,
        ],
        [
            'code' => '20',
            'libelle' => 'Avance',
            'description' => 'Demande d\'avance sur le capital épargné du contrat. Permet au souscripteur d\'obtenir une avance remboursable sur ses fonds disponibles.',
            'categorie' => 'TECH',
            'impact' => '0',
            'delai_traitement' => 7,
        ],
        [
            'code' => '21',
            'libelle' => 'Dénonciation',
            'description' => 'Dénonciation du contrat par le souscripteur entraînant sa résiliation anticipée. Sortie définitive du portefeuille avec versement des provisions mathématiques.',
            'categorie' => 'TECH',
            'impact' => '1',
            'delai_traitement' => 15,
        ],
        [
            'code' => '22',
            'libelle' => 'Renonciation',
            'description' => 'Renonciation au contrat dans le délai légal 30 jours. Droit de rétractation permettant l\'annulation du contrat avec remboursement intégral des versements.',
            'categorie' => 'TECH',
            'impact' => '1',
            'delai_traitement' => 15,
        ],
        [
            'code' => '23',
            'libelle' => 'Rachat total',
            'description' => 'Rachat total du contrat avec versement de l\'intégralité du capital épargné et des intérêts. Clôture définitive du contrat.',
            'categorie' => 'TECH',
            'impact' => '1',
            'delai_traitement' => 15,
        ],
        [
            'code' => '24',
            'libelle' => 'Rachat partiel',
            'description' => 'Rachat partiel du contrat permettant de récupérer une portion du capital épargné tout en maintenant le contrat en vigueur pour le solde.',
            'categorie' => 'TECH',
            'impact' => '0',
            'delai_traitement' => 7,
        ],
        [
            'code' => '25',
            'libelle' => 'Remboursement (Trop perçu après fin cotisation YKF, YKS)',
            'description' => 'Remboursement des trop perçus après la fin des cotisations sur les contrats YAKO FAMILLE et YAKO SOLO. Régularisation des montants suite à l\'arrêt des versements.',
            'categorie' => 'TECH',
            'impact' => '0',
            'delai_traitement' => 15,
        ],
        [
            'code' => '26',
            'libelle' => 'Sinistre',
            'description' => 'Déclaration et traitement d\'un sinistre. Déclenchement des garanties et versement du capital aux bénéficiaires désignés.',
            'categorie' => 'TECH',
            'impact' => '1',
            'delai_traitement' => 7,
        ],
        [
            'code' => '27',
            'libelle' => 'Terme',
            'description' => 'Arrivée à terme du contrat. Échéance contractuelle entraînant le versement du capital et des intérêts ou la transformation en rente viagère selon les options choisies.',
            'categorie' => 'TECH',
            'impact' => '1',
            'delai_traitement' => 15,
        ],
        [
            'code' => 'COUR',
            'libelle' => 'Remise en vigueur (Réactiver contrat)',
            'description' => 'Réactivation d\'un contrat suspendu ou en arrêt de prélèvement. Reprise des cotisations et remise en vigueur des garanties.',
            'categorie' => 'TECH',
            'impact' => '0',
            'delai_traitement' => 7,
        ],
        [
            'code' => '32',
            'libelle' => 'Arrêt Garanties (SENIOR, REMB ...)',
            'description' => 'Arrêt des garanties spécifiques (Senior, Remboursement, etc.) tout en maintenant le contrat en vigueur. Modification des options de couverture.',
            'categorie' => 'TECH',
            'impact' => '0',
            'delai_traitement' => 15,
        ],
        [
            'code' => '33',
            'libelle' => 'Décès Souscripteur (Reduction de Capital)',
            'description' => 'Décès du souscripteur entraînant une réduction du capital du contrat. Maintien du contrat avec un capital réduit pour les bénéficiaires.',
            'categorie' => 'TECH',
            'impact' => '0',
            'delai_traitement' => 7,
        ],
        [
            'code' => '34',
            'libelle' => 'Sinistre (Conjoint, Enft, Senior...)',
            'description' => 'Sinistre concernant le conjoint, les enfants ou les assurés seniors. Déclenchement des garanties annexes et versement des prestations correspondantes.',
            'categorie' => 'TECH',
            'impact' => '1',
            'delai_traitement' => 7,
        ],
        [
            'code' => '36',
            'libelle' => 'Résiliation',
            'description' => 'Résiliation du contrat à l\'initiative du souscripteur ou de l\'assureur. Clôture du contrat avec versement des provisions mathématiques nettes de frais.',
            'categorie' => 'TECH',
            'impact' => '1',
            'delai_traitement' => 15,
        ],
        [
            'code' => '37',
            'libelle' => 'Terme Cotisations YAKO',
            'description' => 'Fin de la période de cotisation pour les contrats YAKO. Arrêt des versements tout en maintenant les garanties et l\'épargne acquise.',
            'categorie' => 'TECH',
            'impact' => '1',
            'delai_traitement' => 15,
        ],
        [
            'code' => 'RENADC',
            'libelle' => 'Annulation contrat (Déduction Commission)',
            'description' => 'Annulation de contrat avec déduction des commissions perçues. Remboursement des versements nets de commissions sur la période concernée.',
            'categorie' => 'TECH',
            'impact' => '1',
            'delai_traitement' => 15,
        ],
        [
            'code' => 'SINSEN',
            'libelle' => 'Sinistre Senior',
            'description' => 'Sinistre spécifique aux contrats Senior. Déclenchement des garanties adaptées aux personnes âgées et versement des prestations correspondantes.',
            'categorie' => 'TECH',
            'impact' => '1',
            'delai_traitement' => 7,
        ],
        [
            'code' => '38',
            'libelle' => 'Décès Souscripteur (Paiement Prestation)',
            'description' => 'Décès du souscripteur avec paiement immédiat de la prestation aux bénéficiaires. Versement du capital et des intérêts dus au moment du décès.',
            'categorie' => 'TECH',
            'impact' => '1',
            'delai_traitement' => 15,
        ],
        [
            'code' => '40',
            'libelle' => 'Remboursement (Trop perçu pendant cotisation)',
            'description' => 'Remboursement des sommes trop perçues durant la période de cotisation. Régularisation des prélèvements excédentaires effectués sur le contrat.',
            'categorie' => 'TECH',
            'impact' => '0',
            'delai_traitement' => 15,
        ],
        [
            'code' => '41',
            'libelle' => 'Remboursement (Trop perçu après fin cotisation)',
            'description' => 'Remboursement des trop perçus survenus après la fin de la période de cotisation. Régularisation post-cotisation des montants indûment perçus.',
            'categorie' => 'TECH',
            'impact' => '0',
            'delai_traitement' => 15,
        ],
        [
            'code' => '42',
            'libelle' => 'Mise en veille (contrat en fin de cotisation)',
            'description' => 'Mise en veille du contrat arrivé en fin de cotisation. Suspension des versements tout en maintenant le capital et les garanties acquises.',
            'categorie' => 'TECH',
            'impact' => '0',
            'delai_traitement' => 15,
        ],
        [
            'code' => '43',
            'libelle' => 'Rachat Partiel pour Avance non Liquidée',
            'description' => 'Rachat partiel destiné à couvrir une avance non liquidée. Utilisation du capital disponible pour solder une avance en attente de traitement.',
            'categorie' => 'TECH',
            'impact' => '0',
            'delai_traitement' => 7,
        ],
        [
            'code' => '44',
            'libelle' => 'Remboursement (Trop perçu après Sinistre)',
            'description' => 'Remboursement des trop perçus consécutifs à un sinistre. Régularisation des montants versés par erreur après le traitement d\'un sinistre.',
            'categorie' => 'TECH',
            'impact' => '0',
            'delai_traitement' => 15,
        ],
        [
            'code' => '46',
            'libelle' => 'Transformation INVEST',
            'description' => 'Transformation du contrat vers le support INVEST. Changement de l\'allocation d\'actifs vers des supports d\'investissement plus dynamiques.',
            'categorie' => 'TECH',
            'impact' => '0',
            'delai_traitement' => 7,
        ],
        [
            'code' => '47',
            'libelle' => 'Reduction',
            'description' => 'Réduction du capital du contrat suite à un événement (décès, option de réduction). Maintien du contrat avec un capital diminué.',
            'categorie' => 'TECH',
            'impact' => '0',
            'delai_traitement' => 7,
        ],
        [
            'code' => '48',
            'libelle' => 'Remboursement (Trop perçu après fin Avance)',
            'description' => 'Remboursement des trop perçus après la fin d\'une période d\'avance. Régularisation des montants suite à l\'arrêt de l\'avance.',
            'categorie' => 'TECH',
            'impact' => '0',
            'delai_traitement' => 15,
        ],
        [
            'code' => '49',
            'libelle' => 'Liquidation',
            'description' => 'Liquidation du contrat avec vente des supports d\'investissement et versement du capital net. Clôture définitive avec conversion en espèces.',
            'categorie' => 'TECH',
            'impact' => '0',
            'delai_traitement' => 15,
        ],
        [
            'code' => '50',
            'libelle' => 'Tirage Doihoo',
            'description' => 'Opération de tirage DOIHOO. Traitement spécifique lié aux opérations DOIHOO sur le contrat.',
            'categorie' => 'TECH',
            'impact' => '0',
            'delai_traitement' => 7,
        ],
        [
            'code' => '51',
            'libelle' => 'Transfert VALEUR',
            'description' => 'Transfert de valeur d\'un contrat vers un autre ou vers un autre support. Déplacement de capital tout en maintenant les droits acquis.',
            'categorie' => 'TECH',
            'impact' => '0',
            'delai_traitement' => 3,
        ],
        [
            'code' => '52',
            'libelle' => 'Sinistre IFC',
            'description' => 'Sinistre IFC (Indemnités Fin de Carrière). Traitement des garanties spécifiques liées à la fin de carrière professionnelle.',
            'categorie' => 'TECH',
            'impact' => '0',
            'delai_traitement' => 7,
        ],
        [
            'code' => '53',
            'libelle' => 'Transformation (Rachat Total)',
            'description' => 'Transformation du contrat par rachat total. Modification des caractéristiques du contrat entraînant un rachat intégral du capital.',
            'categorie' => 'TECH',
            'impact' => '0',
            'delai_traitement' => 7,
        ],
        [
            'code' => '54',
            'libelle' => 'Transformation (Terme)',
            'description' => 'Transformation du contrat par terme. Modification des conditions du contrat arrivé à échéance avec versement des prestations.',
            'categorie' => 'TECH',
            'impact' => '0',
            'delai_traitement' => 7,
        ],
        [
            'code' => '55',
            'libelle' => 'Transformation (Terme Cotisation YKE)',
            'description' => 'Transformation du contrat par terme de cotisation YKE. Modification suite à la fin de la période de cotisation sur les contrats YKE.',
            'categorie' => 'TECH',
            'impact' => '0',
            'delai_traitement' => 7,
        ],

        // ============================================================
        // ADMINISTRATIF (AVT)
        // ============================================================
        [
            'code' => '7',
            'libelle' => 'Changement du mode de paiement',
            'description' => 'Modification du mode de paiement du contrat (prélèvement automatique, virement, chèque, etc.). Changement des modalités de règlement des cotisations.',
            'categorie' => 'AVT',
            'impact' => '0',
            'delai_traitement' => 7,
        ],
        [
            'code' => '6',
            'libelle' => 'Changement de date d\'effet',
            'description' => 'Modification de la date d\'effet du contrat. Décalage de la date de début de validité des garanties avec impact sur les échéances et les primes.',
            'categorie' => 'AVT',
            'impact' => '0',
            'delai_traitement' => 7,
        ],
        [
            'code' => '2',
            'libelle' => 'Changement d\'adresse du souscripteur',
            'description' => 'Modification de l\'adresse postale du souscripteur. Mise à jour des coordonnées pour l\'envoi des courriers et relevés de compte.',
            'categorie' => 'AVT',
            'impact' => '0',
            'delai_traitement' => 7,
        ],
        [
            'code' => '3',
            'libelle' => 'Changement de contact téléphonique du souscripteur',
            'description' => 'Modification du numéro de téléphone du souscripteur. Mise à jour des coordonnées téléphoniques pour les contacts et communications.',
            'categorie' => 'AVT',
            'impact' => '0',
            'delai_traitement' => 7,
        ],
        [
            'code' => '4',
            'libelle' => 'Rectification du nom de l\'assuré',
            'description' => 'Correction du nom de l\'assuré en cas d\'erreur ou de changement. Rectification administrative pour assurer la conformité des données contractuelles.',
            'categorie' => 'AVT',
            'impact' => '0',
            'delai_traitement' => 7,
        ],
        [
            'code' => '5',
            'libelle' => 'Rectification du lieu de naissance de l\'assuré',
            'description' => 'Correction du lieu de naissance de l\'assuré. Rectification administrative des informations personnelles pour la validité du contrat.',
            'categorie' => 'AVT',
            'impact' => '0',
            'delai_traitement' => 7,
        ],
        [
            'code' => '28',
            'libelle' => 'Réduction de prime',
            'description' => 'Réduction du montant de la prime due. Diminution des cotisations suite à une modification des garanties ou des conditions du contrat.',
            'categorie' => 'AVT',
            'impact' => '0',
            'delai_traitement' => 7,
        ],
        [
            'code' => '29',
            'libelle' => 'Réduction de capital',
            'description' => 'Réduction du capital souscrit sur le contrat. Diminution du montant garanti tout en maintenant le contrat en vigueur.',
            'categorie' => 'AVT',
            'impact' => '0',
            'delai_traitement' => 7,
        ],
        [
            'code' => 'SUS',
            'libelle' => 'Suspension',
            'description' => 'Suspension temporaire du contrat. Arrêt des cotisations et des garanties pour une période déterminée avec possibilité de reprise ultérieure.',
            'categorie' => 'AVT',
            'impact' => '0',
            'delai_traitement' => 7,
        ],
        [
            'code' => 'AV1',
            'libelle' => 'Modification de nom',
            'description' => 'Modification du nom du souscripteur ou de l\'assuré suite à mariage, divorce ou correction administrative.',
            'categorie' => 'AVT',
            'impact' => '0',
            'delai_traitement' => 7,
        ],
        [
            'code' => 'AV2',
            'libelle' => 'Modification de nom, prénom(s)',
            'description' => 'Modification simultanée du nom et des prénoms du souscripteur ou de l\'assuré. Mise à jour complète de l\'identité civile.',
            'categorie' => 'AVT',
            'impact' => '0',
            'delai_traitement' => 7,
        ],
        [
            'code' => 'AV3',
            'libelle' => 'Modification adresse, n° tél., lieu de résidence',
            'description' => 'Modification groupée des coordonnées du souscripteur : adresse, numéro de téléphone et lieu de résidence. Mise à jour complète des informations de contact.',
            'categorie' => 'AVT',
            'impact' => '0',
            'delai_traitement' => 7,
        ],
        [
            'code' => 'AV4',
            'libelle' => 'Ajout de bénéficiaire',
            'description' => 'Ajout d\'un nouveau bénéficiaire au contrat. Désignation d\'une personne qui recevra le capital en cas de décès de l\'assuré.',
            'categorie' => 'AVT',
            'impact' => '0',
            'delai_traitement' => 7,
        ],
        [
            'code' => 'AV5',
            'libelle' => 'Modification de la durée du contrat',
            'description' => 'Modification de la durée initiale du contrat. Rallongement ou raccourcissement de la période de couverture selon les conditions contractuelles.',
            'categorie' => 'AVT',
            'impact' => '0',
            'delai_traitement' => 7,
        ],
        [
            'code' => 'AV6',
            'libelle' => 'Rectification lieu de naissance',
            'description' => 'Correction du lieu de naissance du souscripteur ou de l\'assuré. Rectification administrative pour la conformité des données personnelles.',
            'categorie' => 'AVT',
            'impact' => '0',
            'delai_traitement' => 7,
        ],
        [
            'code' => 'AV7',
            'libelle' => 'Rectification de la filiation',
            'description' => 'Correction des informations de filiation (noms des parents). Rectification administrative des données généalogiques requises.',
            'categorie' => 'AVT',
            'impact' => '0',
            'delai_traitement' => 7,
        ],
        [
            'code' => 'AV8',
            'libelle' => 'Modification de prime (diminution, augmentation)',
            'description' => 'Modification du montant de la prime, soit à la hausse soit à la baisse. Ajustement des cotisations suite à une modification des garanties ou du capital.',
            'categorie' => 'AVT',
            'impact' => '0',
            'delai_traitement' => 7,
        ],
        [
            'code' => 'AV9',
            'libelle' => 'Modification prime SURETE',
            'description' => 'Modification spécifique de la prime liée à la garantie SURETE. Ajustement de la cotisation pour cette option particulière.',
            'categorie' => 'AVT',
            'impact' => '0',
            'delai_traitement' => 7,
        ],
        [
            'code' => 'AV10',
            'libelle' => 'Incorporation d\'assuré',
            'description' => 'Incorporation d\'un nouvel assuré sur le contrat. Ajout d\'une personne couverte par les garanties du contrat existant.',
            'categorie' => 'AVT',
            'impact' => '0',
            'delai_traitement' => 7,
        ],
        [
            'code' => 'AV11',
            'libelle' => 'Modification de périodicité',
            'description' => 'Modification de la périodicité des paiements (mensuel, trimestriel, semestriel, annuel). Changement de la fréquence des cotisations.',
            'categorie' => 'AVT',
            'impact' => '0',
            'delai_traitement' => 7,
        ],
        [
            'code' => 'AV12',
            'libelle' => 'Adjonction de l\'option remboursement',
            'description' => 'Ajout de l\'option de remboursement des primes en cas de décès. Activation de la garantie de restitution des cotisations aux bénéficiaires.',
            'categorie' => 'AVT',
            'impact' => '0',
            'delai_traitement' => 7,
        ],
        [
            'code' => 'AV13',
            'libelle' => 'Annulation de la garantie Remboursement',
            'description' => 'Annulation de la garantie remboursement des primes. Suppression de l\'option de restitution des cotisations en cas de décès.',
            'categorie' => 'AVT',
            'impact' => '0',
            'delai_traitement' => 7,
        ],
        [
            'code' => 'AV14',
            'libelle' => 'Réduction de capital de référence',
            'description' => 'Réduction du capital de référence utilisé pour le calcul des garanties. Diminution du montant de base du contrat.',
            'categorie' => 'AVT',
            'impact' => '0',
            'delai_traitement' => 7,
        ],
        [
            'code' => 'AV15',
            'libelle' => 'Retrait d\'un assuré',
            'description' => 'Retrait d\'un assuré du contrat. Suppression d\'une personne couverte par les garanties tout en maintenant le contrat pour les autres assurés.',
            'categorie' => 'AVT',
            'impact' => '0',
            'delai_traitement' => 7,
        ],
        [
            'code' => 'AV16',
            'libelle' => 'Modification date de naissance de l\'assuré',
            'description' => 'Correction de la date de naissance de l\'assuré. Rectification administrative pour la conformité des données personnelles et le calcul des primes.',
            'categorie' => 'AVT',
            'impact' => '0',
            'delai_traitement' => 7,
        ],
        [
            'code' => '39',
            'libelle' => 'Décès Souscripteur (Changement Payeur de Prime)',
            'description' => 'Décès du souscripteur avec changement du payeur des primes. Désignation d\'un nouveau payeur pour continuer le paiement des cotisations.',
            'categorie' => 'AVT',
            'impact' => '0',
            'delai_traitement' => 7,
        ],
        [
            'code' => '45',
            'libelle' => 'Transformation',
            'description' => 'Transformation du contrat vers une autre formule ou un autre produit. Modification des caractéristiques fondamentales du contrat avec accord du souscripteur.',
            'categorie' => 'AVT',
            'impact' => '0',
            'delai_traitement' => 7,
        ],

        // ============================================================
        // CORRECTION (COR)
        // ============================================================
        [
            'code' => 'C01',
            'libelle' => 'Correction Etat Garantie',
            'description' => 'Correction de l\'état de la garantie (active, suspendue, résiliée). Rectification du statut des garanties sur le contrat.',
            'categorie' => 'COR',
            'impact' => '0',
            'delai_traitement' => 2,
        ],
        [
            'code' => 'C02',
            'libelle' => 'Correction Compte Bancaire',
            'description' => 'Correction des coordonnées bancaires pour les prélèvements ou remboursements. Mise à jour de l\'IBAN, RIB ou coordonnés bancaires.',
            'categorie' => 'COR',
            'impact' => '0',
            'delai_traitement' => null,
        ],
        [
            'code' => 'C03',
            'libelle' => 'Correction Matricule',
            'description' => 'Correction du matricule ou numéro d\'identification du souscripteur. Rectification de l\'identifiant administratif unique.',
            'categorie' => 'COR',
            'impact' => '0',
            'delai_traitement' => 1,
        ],
        [
            'code' => 'C04',
            'libelle' => 'Correction Code Conseiller',
            'description' => 'Correction du code conseiller ou intermédiaire. Rectification de l\'identifiant du professionnel ayant commercialisé le contrat.',
            'categorie' => 'COR',
            'impact' => '0',
            'delai_traitement' => 1,
        ],
        [
            'code' => 'C05',
            'libelle' => 'Correction Nom Souscripteur',
            'description' => 'Correction du nom du souscripteur en cas d\'erreur de saisie ou de changement administratif. Rectification de l\'identité civile du titulaire.',
            'categorie' => 'COR',
            'impact' => '0',
            'delai_traitement' => 1,
        ],
        [
            'code' => 'C06',
            'libelle' => 'Correction Date Effet',
            'description' => 'Correction de la date d\'effet du contrat. Rectification de la date de début de validité des garanties en cas d\'erreur.',
            'categorie' => 'COR',
            'impact' => '0',
            'delai_traitement' => 2,
        ],
        [
            'code' => 'C07',
            'libelle' => 'Correction Date de naissance',
            'description' => 'Correction de la date de naissance du souscripteur ou de l\'assuré. Rectification administrative pour la conformité des données.',
            'categorie' => 'COR',
            'impact' => '0',
            'delai_traitement' => 2,
        ],
        [
            'code' => 'C08',
            'libelle' => 'Correction Ajout Bénéficiaire',
            'description' => 'Correction des informations concernant un bénéficiaire ajouté au contrat. Rectification des données d\'identification du bénéficiaire.',
            'categorie' => 'COR',
            'impact' => '0',
            'delai_traitement' => 2,
        ],
        [
            'code' => 'C09',
            'libelle' => 'Correction Ajout Souscripteur',
            'description' => 'Correction des informations lors de l\'ajout d\'un souscripteur au contrat. Rectification des données du nouveau titulaire.',
            'categorie' => 'COR',
            'impact' => '0',
            'delai_traitement' => 2,
        ],
        [
            'code' => 'C10',
            'libelle' => 'Correction Code Produit Formule',
            'description' => 'Correction du code produit ou de la formule souscrite. Rectification de l\'identifiant du produit d\'assurance sur le contrat.',
            'categorie' => 'COR',
            'impact' => '0',
            'delai_traitement' => 1,
        ],
        [
            'code' => 'C11',
            'libelle' => 'Correction Etat Encaissement',
            'description' => 'Correction de l\'état d\'encaissement des primes (encaissé, en attente, rejeté). Rectification du statut de paiement des cotisations.',
            'categorie' => 'COR',
            'impact' => '0',
            'delai_traitement' => 2,
        ],
        [
            'code' => 'C12',
            'libelle' => 'Correction Périodicité',
            'description' => 'Correction de la périodicité de paiement (mensuel, trimestriel, etc.). Rectification de la fréquence des cotisations sur le contrat.',
            'categorie' => 'COR',
            'impact' => '0',
            'delai_traitement' => 1,
        ],
        [
            'code' => 'C13',
            'libelle' => 'Correction Prime',
            'description' => 'Correction du montant de la prime. Rectification du montant des cotisations en cas d\'erreur de calcul ou de saisie.',
            'categorie' => 'COR',
            'impact' => '0',
            'delai_traitement' => 1,
        ],
        [
            'code' => 'C14',
            'libelle' => 'Correction Contrat à Valider',
            'description' => 'Correction des informations d\'un contrat en attente de validation. Rectification des données avant finalisation du contrat.',
            'categorie' => 'COR',
            'impact' => '0',
            'delai_traitement' => null,
        ],
        [
            'code' => 'C15',
            'libelle' => 'Correction Situation matrimoniale',
            'description' => 'Correction de la situation matrimoniale du souscripteur (célibataire, marié, divorcé, veuf). Rectification du statut civil.',
            'categorie' => 'COR',
            'impact' => '0',
            'delai_traitement' => 1,
        ],
        [
            'code' => 'C16',
            'libelle' => 'Correction Ajout Capital',
            'description' => 'Correction lors de l\'ajout de capital sur le contrat. Rectification des montants et modalités d\'augmentation du capital.',
            'categorie' => 'COR',
            'impact' => '0',
            'delai_traitement' => 1,
        ],
        [
            'code' => 'C17',
            'libelle' => 'Correction Nom Assuré',
            'description' => 'Correction du nom de l\'assuré sur le contrat. Rectification de l\'identité de la personne couverte par les garanties.',
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