<?php
namespace Database\Seeders;

use App\Models\Api\Ynov\parameter\Role;
use Illuminate\Database\Seeder;
// use Illuminate\Support\Str;
class SuperAdminRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ============================================================
        // Rôle Super Administrateur
        // ============================================================
        Role::firstOrCreate(
            ['code' => 'super_admin'],
            [
                'libelle' => 'Super Administrateur',
                'description' => 'Rôle disposant de tous les droits sur la plateforme. Non modifiable et non supprimable via interface.',
                'is_system' => true,
                'is_super_admin' => true,
                'is_default' => false,
                'level' => 1,
                'priority' => 0,
                'status' => 'actif',
            ]
        );

        // ============================================================
        // Rôle Client
        // ============================================================
        Role::firstOrCreate(
            ['code' => 'client'],
            [
                'libelle' => 'Client',
                'description' => 'Rôle attribué aux clients de la plateforme. Accès à l\'espace client et aux fonctionnalités de souscription.',
                'is_system' => true,
                'is_super_admin' => false,
                'is_default' => true, // Rôle par défaut pour les nouvelles inscriptions
                'level' => 10,
                'priority' => 1,
                'status' => 'actif',
            ]
        );

        // ============================================================
        // Rôle Administrateur (optionnel)
        // ============================================================
        Role::firstOrCreate(
            ['code' => 'admin'],
            [
                'libelle' => 'Administrateur',
                'description' => 'Rôle administrateur disposant de droits étendus mais inférieurs au Super Admin.',
                'is_system' => true,
                'is_super_admin' => false,
                'is_default' => false,
                'level' => 2,
                'priority' => 2,
                'status' => 'actif',
            ]
        );

        Role::firstOrCreate(
            ['code' => 'admin_rdv'],
            [
                'libelle' => 'Administrateur Rendez-vous',
                'description' => 'Rôle administrateur rendez-vous disposant de droits étendus pour la gestion et suppression des rendez-vous.',
                'is_system' => true,
                'is_super_admin' => false,
                'is_default' => false,
                'level' => 3,
                'priority' => 3,
                'status' => 'actif',
            ]
        );

        // ============================================================
        // Rôle Gestionnaire (optionnel)
        // ============================================================
        Role::firstOrCreate(
            ['code' => 'gestionnaire_rdv'],
            [
                'libelle' => 'Gestionnaire Rendez-vous',
                'description' => 'Rôle gestionnaire rendez-vous disposant de droits étendus pour la gestion et traitement des rendez-vous.',
                'is_system' => false,
                'is_super_admin' => false,
                'is_default' => false,
                'level' => 4,
                'priority' => 4,
                'status' => 'actif',
            ]
        );
    }
}


    