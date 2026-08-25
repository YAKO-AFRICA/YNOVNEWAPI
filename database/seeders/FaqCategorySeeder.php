<?php
// database/seeders/FaqCategorySeeder.php
namespace Database\Seeders;

use App\Models\Api\Ynov\parameter\FaqCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class FaqCategorySeeder extends Seeder
{
    public function run(): void
    {
        $defaultCategories = [
            [
                'code' => 'compte',
                'label' => 'Compte & connexion',
                'icon' => 'bi-person-circle',
                'color' => '#3490dc',
                'description' => 'Questions relatives à la création de compte, connexion, gestion du profil.',
                'order' => 1,
                'is_default' => true,
            ],
            [
                'code' => 'souscription',
                'label' => 'Souscription & contrats',
                'icon' => 'bi-file-earmark-text',
                'color' => '#2ecc71',
                'description' => 'Questions sur les souscriptions, les contrats et les garanties.',
                'order' => 2,
                'is_default' => true,
            ],
            [
                'code' => 'paiement',
                'label' => 'Paiements',
                'icon' => 'bi-credit-card',
                'color' => '#f39c12',
                'description' => 'Questions sur les paiements, les prélèvements et les factures.',
                'order' => 3,
                'is_default' => true,
            ],
            [
                'code' => 'sinistre',
                'label' => 'Sinistres & réclamations',
                'icon' => 'bi-exclamation-triangle',
                'color' => '#e74c3c',
                'description' => 'Questions sur les déclarations de sinistre et les réclamations.',
                'order' => 4,
                'is_default' => true,
            ],
            [
                'code' => 'securite',
                'label' => 'Sécurité',
                'icon' => 'bi-shield-lock',
                'color' => '#9b59b6',
                'description' => 'Questions sur la sécurité des comptes et des données.',
                'order' => 5,
                'is_default' => true,
            ],
            [
                'code' => 'assistance',
                'label' => 'Assistance',
                'icon' => 'bi-headset',
                'color' => '#1abc9c',
                'description' => 'Questions sur l\'assistance et le support client.',
                'order' => 6,
                'is_default' => true,
            ],
            [
                'code' => 'rendez-vous',
                'label' => 'Rendez-vous',
                'icon' => 'bi-calendar-event',
                'color' => '#e67e22',
                'description' => 'Questions sur la prise de rendez-vous et les visites en agence.',
                'order' => 7,
                'is_default' => true,
            ],
        ];

        foreach ($defaultCategories as $category) {
            FaqCategory::firstOrCreate(
                ['code' => $category['code']],
                [
                    'uuid_faq_category' => (string) Str::uuid(),
                    'label' => $category['label'],
                    'icon' => $category['icon'],
                    'color' => $category['color'],
                    'description' => $category['description'],
                    'order' => $category['order'],
                    'is_active' => true,
                    'is_default' => $category['is_default'],
                ]
            );
        }
    }
}