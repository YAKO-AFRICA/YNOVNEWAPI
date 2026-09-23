<?php

namespace Database\Seeders;

use App\Models\Api\Ynov\parameter\Agence;
use App\Models\Api\Ynov\parameter\AgenceHoraire;
use Illuminate\Database\Seeder;

class YakoAgenceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $agences = [
            [
                'code' => 'AG001',
                'libelle' => 'YAKO AFRICA ASSURANCES VIE',
                'description' => 'Compagnie d’assurance-vie à Abidjan.',
                'email' => 'plateau@yako.ci',
                'telephone' => '+2252720304050',
                'telephone_2' => '+2252720304051',
                'adresse' => 'Av. Charles Noguès, Abidjan',
                'ville' => 'Abidjan',
                'quartier' => 'Plateau',
                'code_postal' => null,
                'pays' => 'Côte d’Ivoire',
                'latitude' => 5.3177759,
                'longitude' => -4.0161944,
                'responsable' => 'Direction YAKO AFRICA',
                'status' => 'actif',
                'horaires' => [
                    'lundi' => ['heure_ouverture' => '08:00', 'heure_fermeture' => '14:00', 'ferme' => false, 'rendez_vous_actif' => true, 'capacite_rendez_vous' => 30, 'commentaire' => 'Accueil standard'],
                    'mardi' => ['heure_ouverture' => '08:00', 'heure_fermeture' => '14:00', 'ferme' => false, 'rendez_vous_actif' => true, 'capacite_rendez_vous' => 30, 'commentaire' => 'Accueil standard'],
                    'mercredi' => ['heure_ouverture' => '08:00', 'heure_fermeture' => '14:00', 'ferme' => false, 'rendez_vous_actif' => true, 'capacite_rendez_vous' => 30, 'commentaire' => 'Accueil standard'],
                    'jeudi' => ['heure_ouverture' => '08:00', 'heure_fermeture' => '14:00', 'ferme' => false, 'rendez_vous_actif' => true, 'capacite_rendez_vous' => 30, 'commentaire' => 'Accueil standard'],
                    'vendredi' => ['heure_ouverture' => '08:00', 'heure_fermeture' => '14:00', 'ferme' => false, 'rendez_vous_actif' => true, 'capacite_rendez_vous' => 30, 'commentaire' => 'Accueil standard'],
                    'samedi' => ['heure_ouverture' => null, 'heure_fermeture' => null, 'ferme' => true, 'rendez_vous_actif' => false, 'capacite_rendez_vous' => 0, 'commentaire' => 'Fermé le samedi'],
                    'dimanche' => ['heure_ouverture' => null, 'heure_fermeture' => null, 'ferme' => true, 'rendez_vous_actif' => false, 'capacite_rendez_vous' => 0, 'commentaire' => 'Fermé le dimanche'],
                ],
            ],
            [
                'code' => 'AG002',
                'libelle' => 'YAKO AFRICA YAMOUSSOUKRO',
                'description' => 'Compagnie d’assurance-vie à Yamoussoukro.',
                'email' => null,
                'telephone' => '+2252720202020',
                'telephone_2' => null,
                'adresse' => 'RP5R+68F, Yamoussoukro',
                'ville' => 'Yamoussoukro',
                'quartier' => 'Centre ville',
                'code_postal' => null,
                'pays' => 'Côte d’Ivoire',
                'latitude' => 6.8080148,
                'longitude' => -5.2591258,
                'responsable' => 'Direction YAKO AFRICA',
                'status' => 'actif',
                'horaires' => [
                    'lundi' => ['heure_ouverture' => null, 'heure_fermeture' => null, 'ferme' => true, 'rendez_vous_actif' => false, 'capacite_rendez_vous' => 0, 'commentaire' => 'Fermé'],
                    'mardi' => ['heure_ouverture' => '07:00', 'heure_fermeture' => '17:00', 'ferme' => false, 'rendez_vous_actif' => true, 'capacite_rendez_vous' => 15, 'commentaire' => 'RDV uniquement mardi de 07h à 17h'],
                    'mercredi' => ['heure_ouverture' => null, 'heure_fermeture' => null, 'ferme' => true, 'rendez_vous_actif' => false, 'capacite_rendez_vous' => 0, 'commentaire' => 'Pas de rendez-vous'],
                    'jeudi' => ['heure_ouverture' => '07:00', 'heure_fermeture' => '17:00', 'ferme' => false, 'rendez_vous_actif' => true, 'capacite_rendez_vous' => 15, 'commentaire' => 'RDV uniquement jeudi de 07h à 17h'],
                    'vendredi' => ['heure_ouverture' => null, 'heure_fermeture' => null, 'ferme' => true, 'rendez_vous_actif' => false, 'capacite_rendez_vous' => 0, 'commentaire' => 'Fermé'],
                    'samedi' => ['heure_ouverture' => null, 'heure_fermeture' => null, 'ferme' => true, 'rendez_vous_actif' => false, 'capacite_rendez_vous' => 0, 'commentaire' => 'Fermé le samedi'],
                    'dimanche' => ['heure_ouverture' => null, 'heure_fermeture' => null, 'ferme' => true, 'rendez_vous_actif' => false, 'capacite_rendez_vous' => 0, 'commentaire' => 'Fermé le dimanche'],
                ],
            ],
        ];

        foreach ($agences as $agenceData) {
            $agence = Agence::updateOrCreate(
                ['code' => $agenceData['code']],
                [
                    'libelle' => $agenceData['libelle'],
                    'description' => $agenceData['description'],
                    'email' => $agenceData['email'],
                    'telephone' => $agenceData['telephone'],
                    'telephone_2' => $agenceData['telephone_2'],
                    'adresse' => $agenceData['adresse'],
                    'ville' => $agenceData['ville'],
                    'quartier' => $agenceData['quartier'],
                    'code_postal' => $agenceData['code_postal'],
                    'pays' => $agenceData['pays'],
                    'latitude' => $agenceData['latitude'],
                    'longitude' => $agenceData['longitude'],
                    'responsable' => $agenceData['responsable'],
                    'status' => $agenceData['status'],
                ]
            );

            foreach ($agenceData['horaires'] as $jour => $horaire) {
                AgenceHoraire::updateOrCreate(
                    [
                        'agence_uuid' => $agence->uuid_agence,
                        'jour' => $jour,
                    ],
                    [
                        'agence_uuid' => $agence->uuid_agence,
                        'jour' => $jour,
                        'heure_ouverture' => $horaire['heure_ouverture'],
                        'heure_fermeture' => $horaire['heure_fermeture'],
                        'heure_ouverture_midi' => null,
                        'heure_fermeture_midi' => null,
                        'ferme' => $horaire['ferme'],
                        'commentaire' => $horaire['commentaire'],
                        'rendez_vous_actif' => $horaire['rendez_vous_actif'],
                        'capacite_rendez_vous' => $horaire['capacite_rendez_vous'],
                    ]
                );
            }
        }
    }
}
