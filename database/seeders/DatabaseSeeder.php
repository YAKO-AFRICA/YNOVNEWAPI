<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Database\Seeders\SuperAdminRoleSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            SuperAdminRoleSeeder::class,
            AssignSuperAdminPermissionsSeeder::class,
            AssignClientPermissionsSeeder::class,
            PermissionSeeder::class,
            SuperAdminUserSeeder::class,
            FaqCategorySeeder::class,
            JourFerieSeeder::class,
            MotifTraitementSeeder::class,
            TypeProduitSeeder::class,
            ProduitSeeder::class,
            ProduitGarantieSeeder::class,
            CategoryTypePrestationSeeder::class,
        ]);
    }
}