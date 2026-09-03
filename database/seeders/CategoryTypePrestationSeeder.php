<?php
// database/seeders/CategoryTypePrestationSeeder.php

namespace Database\Seeders;

use App\Models\Api\Ynov\parameter\CategoryTypePrestation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategoryTypePrestationSeeder extends Seeder
{
    /**
     * Liste des catégories de types de prestations
     */
    private const CATEGORIES = [
        [
            'code' => 'INC',
            'libelle' => 'Autres',
            'description' => 'Catégorie pour les prestations diverses et non classifiées',
        ],
        [
            'code' => 'TECH',
            'libelle' => 'Technique',
            'description' => 'Catégorie pour les prestations techniques',
        ],
        [
            'code' => 'AVT',
            'libelle' => 'Administratif',
            'description' => 'Catégorie pour les prestations administratives et de gestion',
        ],
        [
            'code' => 'COR',
            'libelle' => 'Correction',
            'description' => 'Catégorie pour les prestations de correction et de régularisation',
        ],
    ];

    public function run(): void
    {
        $this->command->info('🔄 Création des catégories de types de prestations...');

        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach (self::CATEGORIES as $category) {
            $result = $this->createOrUpdateCategory($category);
            
            if ($result === 'created') {
                $created++;
                $this->command->info("  ✅ Catégorie '{$category['code']}' - {$category['libelle']} créée.");
            } elseif ($result === 'updated') {
                $updated++;
                $this->command->info("  🔄 Catégorie '{$category['code']}' - {$category['libelle']} mise à jour.");
            } else {
                $skipped++;
                $this->command->warn("  ⏭️  Catégorie '{$category['code']}' existe déjà, ignorée.");
            }
        }

        $this->command->newLine();
        $this->command->info('📊 Résumé du seeding des catégories :');
        $this->command->info("  ✅ {$created} catégories créées");
        $this->command->info("  🔄 {$updated} catégories mises à jour");
        $this->command->info("  ⏭️  {$skipped} catégories ignorées");
        $this->command->newLine();
        $this->command->info('✅ Seed des catégories de types de prestations terminé !');
    }

    /**
     * Créer ou mettre à jour une catégorie
     */
    private function createOrUpdateCategory(array $category): string
    {
        $existing = CategoryTypePrestation::where('code', $category['code'])->first();

        if ($existing) {
            // Vérifier si le libellé ou la description ont changé
            if ($existing->libelle !== $category['libelle'] || $existing->description !== $category['description']) {
                $existing->update([
                    'libelle' => $category['libelle'],
                    'description' => $category['description'],
                    'updated_by' => null,
                ]);
                return 'updated';
            }
            return 'skipped';
        }

        // Créer une nouvelle catégorie
        CategoryTypePrestation::create([
            'uuid_category_type_prestations' => (string) Str::uuid(),
            'code' => $category['code'],
            'libelle' => $category['libelle'],
            'description' => $category['description'],
            'status' => 'actif',
            'created_by' => null,
            'updated_by' => null,
            'deleted_by' => null,
        ]);

        return 'created';
    }
}