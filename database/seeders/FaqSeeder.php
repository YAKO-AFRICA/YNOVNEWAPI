<?php

namespace Database\Seeders;

use App\Models\Api\Ynov\parameter\Faq;
use App\Models\Api\Ynov\parameter\FaqCategory;
use Illuminate\Database\Seeder;

class FaqSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = FaqCategory::where('is_active', true)->get();

        $faqsData = [
            'compte' => [
                [
                    'question' => 'Comment créer un compte sur la plateforme ?',
                    'answer' => 'Pour créer un compte, cliquez sur le bouton "S\'inscrire" sur la page d\'accueil, remplissez le formulaire avec vos informations personnelles et validez votre adresse email.',
                    'is_featured' => true,
                ],
                [
                    'question' => 'J\'ai oublié mon mot de passe, comment le réinitialiser ?',
                    'answer' => 'Cliquez sur "Mot de passe oublié" sur la page de connexion, entrez votre email et suivez les instructions envoyées par email pour créer un nouveau mot de passe.',
                    'is_featured' => true,
                ],
                [
                    'question' => 'Comment modifier mes informations personnelles ?',
                    'answer' => 'Connectez-vous à votre compte, allez dans la section "Mon Profil" et cliquez sur "Modifier" pour mettre à jour vos informations.',
                ],
                [
                    'question' => 'Puis-je changer mon adresse email ?',
                    'answer' => 'Oui, vous pouvez changer votre adresse email depuis votre profil. Vous devrez confirmer la nouvelle adresse email.',
                ],
                [
                    'question' => 'Comment supprimer mon compte ?',
                    'answer' => 'Pour supprimer votre compte, contactez notre service client. Cette action est irréversible et toutes vos données seront supprimées.',
                ],
            ],
            'souscription' => [
                [
                    'question' => 'Comment souscrire à un contrat ?',
                    'answer' => 'Après vous être connecté, allez dans la section "Mes Contrats" et cliquez sur "Nouvelle souscription". Suivez les étapes pour choisir votre produit et finaliser votre souscription.',
                    'is_featured' => true,
                ],
                [
                    'question' => 'Quels sont les documents nécessaires pour souscrire ?',
                    'answer' => 'Vous aurez besoin d\'une pièce d\'identité valide, d\'un justificatif de domicile et de vos informations bancaires pour le prélèvement.',
                ],
                [
                    'question' => 'Puis-je modifier mon contrat après souscription ?',
                    'answer' => 'Oui, certaines modifications sont possibles. Contactez votre conseiller ou utilisez l\'espace client pour faire les demandes.',
                ],
                [
                    'question' => 'Quelle est la durée d\'un contrat ?',
                    'answer' => 'La durée des contrats varie selon le type de produit. Consultez les conditions générales de votre contrat pour plus de détails.',
                ],
                [
                    'question' => 'Comment résilier mon contrat ?',
                    'answer' => 'Vous pouvez résilier votre contrat depuis votre espace client ou en envoyant une lettre recommandée à votre service client, en respectant le préavis mentionné dans votre contrat.',
                ],
            ],
            'paiement' => [
                [
                    'question' => 'Quels sont les modes de paiement acceptés ?',
                    'answer' => 'Nous acceptons les prélèvements automatiques, les cartes bancaires et les virements bancaires.',
                    'is_featured' => true,
                ],
                [
                    'question' => 'Comment mettre en place un prélèvement automatique ?',
                    'answer' => 'Depuis votre espace client, allez dans la section "Paiements" et ajoutez votre RIB pour activer le prélèvement automatique.',
                ],
                [
                    'question' => 'Où trouver mes factures ?',
                    'answer' => 'Vos factures sont disponibles dans la section "Mes Factures" de votre espace client. Elles sont également envoyées par email.',
                ],
                [
                    'question' => 'J\'ai un problème de paiement, que faire ?',
                    'answer' => 'Contactez notre service client au plus vite pour régulariser votre situation et éviter toute interruption de service.',
                ],
                [
                    'question' => 'Puis-je payer à l\'avance ?',
                    'answer' => 'Oui, vous pouvez effectuer des paiements anticipés depuis votre espace client.',
                ],
            ],
            'sinistre' => [
                [
                    'question' => 'Comment déclarer un sinistre ?',
                    'answer' => 'Déclarez votre sinistre depuis votre espace client dans la section "Sinistres" ou contactez notre numéro d\'urgence disponible 24h/24 et 7j/7.',
                    'is_featured' => true,
                ],
                [
                    'question' => 'Quels documents faut-il fournir pour une déclaration de sinistre ?',
                    'answer' => 'Vous aurez besoin du constat amiable, de photos des dommages, et de tout document justificatif selon le type de sinistre.',
                ],
                [
                    'question' => 'Quel est le délai de traitement d\'un sinistre ?',
                    'answer' => 'Le délai de traitement varie selon la complexité du dossier. En moyenne, vous recevrez une réponse sous 15 jours ouvrés.',
                ],
                [
                    'question' => 'Comment suivre l\'avancement de mon dossier sinistre ?',
                    'answer' => 'Connectez-vous à votre espace client et allez dans la section "Mes Sinistres" pour suivre l\'état de votre dossier en temps réel.',
                ],
                [
                    'question' => 'Je ne suis pas d\'accord avec l\'indemnisation proposée, que faire ?',
                    'answer' => 'Vous pouvez contester la décision en envoyant un courrier motivé à notre service des réclamations ou en utilisant le formulaire de réclamation en ligne.',
                ],
            ],
            'securite' => [
                [
                    'question' => 'Comment sécuriser mon compte ?',
                    'answer' => 'Utilisez un mot de passe fort, activez la double authentification (2FA) et ne partagez jamais vos identifiants.',
                    'is_featured' => true,
                ],
                [
                    'question' => 'Qu\'est-ce que la double authentification (2FA) ?',
                    'answer' => 'La double authentification ajoute une couche de sécurité en demandant un code unique lors de la connexion, en plus de votre mot de passe.',
                ],
                [
                    'question' => 'Comment activer la 2FA sur mon compte ?',
                    'answer' => 'Allez dans les paramètres de sécurité de votre compte et suivez les instructions pour activer la double authentification par SMS ou application.',
                ],
                [
                    'question' => 'Mes données sont-elles protégées ?',
                    'answer' => 'Oui, nous utilisons des protocoles de chiffrement avancés et respectons strictement le RGPD pour protéger vos données personnelles.',
                ],
                [
                    'question' => 'Que faire en cas de suspicion de piratage ?',
                    'answer' => 'Changez immédiatement votre mot de passe, contactez notre support et vérifiez vos activités récentes dans votre espace client.',
                ],
            ],
            'assistance' => [
                [
                    'question' => 'Comment contacter le support client ?',
                    'answer' => 'Vous pouvez nous contacter par téléphone, email, chat en direct ou via le formulaire de contact sur notre site.',
                    'is_featured' => true,
                ],
                [
                    'question' => 'Quels sont les horaires du support client ?',
                    'answer' => 'Notre support est disponible du lundi au vendredi de 8h à 18h. Une permanence est assurée pour les urgences 24h/24.',
                ],
                [
                    'question' => 'Puis-je prendre rendez-vous en agence ?',
                    'answer' => 'Oui, vous pouvez prendre rendez-vous en ligne ou par téléphone pour rencontrer un conseiller dans l\'une de nos agences.',
                ],
                [
                    'question' => 'Comment faire une réclamation ?',
                    'answer' => 'Utilisez le formulaire de réclamation en ligne ou envoyez un courrier à notre service des réclamations. Vous recevrez un accusé de réception sous 48h.',
                ],
                [
                    'question' => 'Y a-t-il une FAQ en ligne ?',
                    'answer' => 'Oui, cette FAQ est disponible 24h/24 pour répondre à vos questions les plus fréquentes.',
                ],
            ],
            'rendez-vous' => [
                [
                    'question' => 'Comment prendre un rendez-vous en agence ?',
                    'answer' => 'Connectez-vous à votre espace client, allez dans la section "Rendez-vous" et choisissez l\'agence, le créneau horaire et le motif de votre rendez-vous.',
                    'is_featured' => true,
                ],
                [
                    'question' => 'Puis-je annuler ou modifier mon rendez-vous ?',
                    'answer' => 'Oui, vous pouvez modifier ou annuler votre rendez-vous depuis votre espace client jusqu\'à 24h avant le créneau prévu.',
                ],
                [
                    'question' => 'Quels documents apporter lors d\'un rendez-vous ?',
                    'answer' => 'Apportez votre pièce d\'identité, les documents relatifs à votre demande et tout justificatif nécessaire selon le motif du rendez-vous.',
                ],
                [
                    'question' => 'Les rendez-vous sont-ils payants ?',
                    'answer' => 'Les rendez-vous de conseil sont gratuits. Certains services spécifiques peuvent faire l\'objet de tarifs qui vous seront communiqués à l\'avance.',
                ],
                [
                    'question' => 'Puis-je avoir un rendez-vous par visioconférence ?',
                    'answer' => 'Oui, nous proposons des rendez-vous en visioconférence pour certains types de demandes. Choisissez cette option lors de la prise de rendez-vous.',
                ],
            ],
            'questions_generales' => [
                [
                    'question' => 'Comment fonctionne la plateforme ?',
                    'answer' => 'Notre plateforme vous permet de gérer vos contrats, vos paiements, vos sinistres et de prendre rendez-vous en ligne, 24h/24 et 7j/7.',
                    'is_featured' => true,
                ],
                [
                    'question' => 'La plateforme est-elle accessible sur mobile ?',
                    'answer' => 'Oui, notre plateforme est responsive et s\'adapte à tous les écrans. Une application mobile est également disponible.',
                ],
                [
                    'question' => 'Y a-t-il une application mobile ?',
                    'answer' => 'Oui, notre application est disponible sur iOS et Android. Téléchargez-la depuis l\'App Store ou Google Play.',
                ],
                [
                    'question' => 'Comment rester informé des nouveautés ?',
                    'answer' => 'Abonnez-vous à notre newsletter et suivez-nous sur les réseaux sociaux pour recevoir les dernières actualités.',
                ],
                [
                    'question' => 'La plateforme est-elle disponible en anglais ?',
                    'answer' => 'Oui, la plateforme est disponible en plusieurs langues dont l\'anglais. Changez la langue dans les paramètres de votre compte.',
                ],
            ],
        ];

        foreach ($categories as $category) {
            $categoryCode = $category->code;

            if (isset($faqsData[$categoryCode])) {
                foreach ($faqsData[$categoryCode] as $index => $faqData) {
                    // Vérifier si la FAQ existe déjà
                    $existing = Faq::where('question', $faqData['question'])
                        ->where('faq_category_uuid', $category->uuid_faq_category)
                        ->first();

                    if (!$existing) {
                        Faq::create([
                            'faq_category_uuid' => $category->uuid_faq_category,
                            'category' => $categoryCode,
                            'category_label' => $category->label,
                            'question' => $faqData['question'],
                            'answer' => $faqData['answer'],
                            'order' => $index + 1,
                            'is_active' => true,
                            'is_featured' => $faqData['is_featured'] ?? false,
                            'tags' => [$categoryCode],
                        ]);
                    }
                }
            }
        }

        $this->command->info('FAQs créées avec succès.');
    }
}
