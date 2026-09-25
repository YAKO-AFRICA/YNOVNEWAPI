<?php

namespace Database\Seeders;

use App\Models\Api\Ynov\parameter\CategoryTypePrestation;
use App\Models\Api\Ynov\parameter\Produit;
use App\Models\Api\Ynov\parameter\TypePrestation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AssignNonTechPrestationsToAllProductsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Début de l\'assignation des prestations non-TECH à tous les produits...');

        // Récupérer la catégorie TECH
        $techCategory = CategoryTypePrestation::where('code', 'TECH')->first();

        if (!$techCategory) {
            $this->command->error('Catégorie TECH non trouvée. Arrêt du seeder.');
            return;
        }

        // Récupérer tous les types de prestations dont category.code != 'TECH'
        $nonTechPrestations = TypePrestation::where('status', 'actif')
            ->whereHas('category', function ($query) use ($techCategory) {
                $query->where('code', '!=', 'TECH');
            })
            ->get();

        $this->command->info('Types de prestations non-TECH trouvés : ' . $nonTechPrestations->count());

        if ($nonTechPrestations->isEmpty()) {
            $this->command->warn('Aucun type de prestation non-TECH trouvé.');
            return;
        }

        // Récupérer tous les produits actifs
        $produits = Produit::where('statut', 'actif')->get();

        $this->command->info('Produits actifs trouvés : ' . $produits->count());

        if ($produits->isEmpty()) {
            $this->command->warn('Aucun produit actif trouvé.');
            return;
        }

        $totalAssignations = 0;
        $errors = 0;

        // Parcourir chaque produit
        foreach ($produits as $produit) {
            $this->command->info('Traitement du produit : ' . $produit->code);

            // Parcourir chaque type de prestation non-TECH
            foreach ($nonTechPrestations as $typePrestation) {
                try {
                    // Vérifier si l'association existe déjà
                    $existing = DB::table('produit_prestations')
                        ->where('produit_uuid', $produit->uuid_produit)
                        ->where('type_prestation_uuid', $typePrestation->uuid_type_prestation)
                        ->first();

                    if ($existing) {
                        $this->command->line('  - Déjà associé : ' . $typePrestation->code);
                        continue;
                    }

                    // Créer l'association
                    DB::table('produit_prestations')->insert([
                        'uuid_product_prestation' => (string) \Illuminate\Support\Str::uuid(),
                        'produit_uuid' => $produit->uuid_produit,
                        'type_prestation_uuid' => $typePrestation->uuid_type_prestation,
                        'produit_type' => null,
                        'status' => 'actif',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $totalAssignations++;
                    $this->command->line('  + Associé : ' . $typePrestation->code . ' (non-TECH)');

                } catch (\Exception $e) {
                    $errors++;
                    $this->command->error('  - Erreur pour ' . $typePrestation->code . ' : ' . $e->getMessage());
                    Log::error('Erreur lors de l\'assignation de prestation non-TECH', [
                        'produit' => $produit->code,
                        'type_prestation' => $typePrestation->code,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        $this->command->info('Assignation terminée !');
        $this->command->info('Total des associations créées : ' . $totalAssignations);
        $this->command->info('Total des erreurs : ' . $errors);

        if ($errors > 0) {
            $this->command->warn('Des erreurs ont été rencontrées. Consultez les logs pour plus de détails.');
        } else {
            $this->command->info('Toutes les prestations non-TECH ont été assignées avec succès à tous les produits.');
        }
    }
}
