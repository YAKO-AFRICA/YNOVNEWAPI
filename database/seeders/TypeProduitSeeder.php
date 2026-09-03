<?php
// database/seeders/TypeProduitSeeder.php

namespace Database\Seeders;

use App\Models\Api\Ynov\parameter\TypeProduit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TypeProduitSeeder extends Seeder
{
    /**
     * Liste des types de produits
     */
    private const TYPES = [
        [
            'code' => 'KVIE',
            'libelle' => 'En cas de vie',
            'description' => 'Produits d\'assurance vie',
        ],
        [
            'code' => 'KDEC',
            'libelle' => 'En cas de décès',
            'description' => 'Produits d\'assurance décès',
        ],
        [
            'code' => 'MIXTE',
            'libelle' => 'Mixte',
            'description' => 'Produits mixtes (vie et décès)',
        ],
        [
            'code' => 'EPA',
            'libelle' => 'Épargne',
            'description' => 'Produits d\'épargne',
        ],
        [
            'code' => 'CAPI',
            'libelle' => 'Capitalisation',
            'description' => 'Produits de capitalisation',
        ],
        [
            'code' => 'COMP',
            'libelle' => 'Complémentaire',
            'description' => 'Produits complémentaires',
        ],
    ];

    public function run(): void
    {
        $this->command->info('🔄 Création des types de produits...');

        $created = 0;
        $skipped = 0;

        foreach (self::TYPES as $type) {
            // Vérifier si le type existe déjà
            $existing = TypeProduit::where('code', $type['code'])->first();

            if ($existing) {
                $this->command->warn("  ⏭️  Type '{$type['code']}' existe déjà, ignoré.");
                $skipped++;
                continue;
            }

            TypeProduit::create([
                'uuid_type_produit' => (string) Str::uuid(),
                'code' => $type['code'],
                'libelle' => $type['libelle'],
                'created_by' => null, // Ou l'UUID d'un utilisateur système
                'updated_by' => null,
                'deleted_by' => null,
            ]);

            $this->command->info("  ✅ Type '{$type['code']}' - {$type['libelle']} créé.");
            $created++;
        }

        $this->command->newLine();
        $this->command->info("📊 Résumé : {$created} types créés, {$skipped} déjà existants.");
        $this->command->info('✅ Seed des types de produits terminé !');
    }
}