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

        Role::firstOrCreate(
            ['code' => 'admin_prestation'],
            [
                'libelle' => 'Administrateur Prestation',
                'description' => 'Rôle administrateur prestation disposant de droits étendus pour la gestion et suppression des prestations.',
                'is_system' => true,
                'is_super_admin' => false,
                'is_default' => false,
                'level' => 4,
                'priority' => 4,
                'status' => 'actif',
            ]
        );

        // ============================================================
        // Rôle Gestionnaire Rendez-vous
        // ============================================================
        Role::firstOrCreate(
            ['code' => 'gestionnaire_rdv'],
            [
                'libelle' => 'Gestionnaire Rendez-vous',
                'description' => 'Rôle gestionnaire rendez-vous disposant de droits étendus pour la gestion et traitement des rendez-vous.',
                'is_system' => false,
                'is_super_admin' => false,
                'is_default' => false,
                'level' => 5,
                'priority' => 5,
                'status' => 'actif',
            ]
        );

        // ============================================================
        // Rôle Gestionnaire Prestation
        // ============================================================
        Role::firstOrCreate(
            ['code' => 'gestionnaire_prestation'],
            [
                'libelle' => 'Gestionnaire Prestation',
                'description' => 'Rôle gestionnaire prestation disposant de droits étendus pour la gestion et traitement des prestations.',
                'is_system' => false,
                'is_super_admin' => false,
                'is_default' => false,
                'level' => 6,
                'priority' => 6,
                'status' => 'actif',
            ]
        );

        // ============================================================
        // Rôle Gestionnaire accueil client
        // ============================================================
        Role::firstOrCreate(
            ['code' => 'gestionnaire_accueil'],
            [
                'libelle' => 'Gestionnaire Accueil',
                'description' => "Rôle gestionnaire accueil disposant de droits étendus pour l'accueil des clients.",
                'is_system' => false,
                'is_super_admin' => false,
                'is_default' => false,
                'level' => 7,
                'priority' => 7,
                'status' => 'actif',
            ]
        );
        // ============================================================
        // Rôle Administrateur Souscription
        // ============================================================
        Role::firstOrCreate(
            ['code' => 'admin_souscription'],
            [
                'libelle' => 'Administrateur Souscription',
                'description' => "Rôle administrateur souscription disposant de droits d'Administration pour la gestion des souscriptions.",
                'is_system' => false,
                'is_super_admin' => false,
                'is_default' => false,
                'level' => 8,
                'priority' => 8,
                'status' => 'actif',
            ]
        );
        // ============================================================
        // Rôle Manager Souscription
        // ============================================================
        Role::firstOrCreate(
            ['code' => 'manager_souscription'],
            [
                'libelle' => 'Manager Souscription',
                'description' => "Rôle manager souscription disposant de droits de manager pour la gestion des souscriptions.",
                'is_system' => false,
                'is_super_admin' => false,
                'is_default' => false,
                'level' => 9,
                'priority' => 9,
                'status' => 'actif',
            ]
        );
        // ============================================================
        // Rôle Superviseur Souscription
        // ============================================================
        Role::firstOrCreate(
            ['code' => 'superviseur_souscription'],
            [
                'libelle' => 'Superviseur Souscription',
                'description' => "Rôle superviseur souscription disposant de droits de supervision pour la gestion des souscriptions.",
                'is_system' => false,
                'is_super_admin' => false,
                'is_default' => false,
                'level' => 10,
                'priority' => 10,
                'status' => 'actif',
            ]
        );
        // ============================================================
        // Rôle Conseiller Souscription
        // ============================================================
        Role::firstOrCreate(
            ['code' => 'conseiller_souscription'],
            [
                'libelle' => 'Conseiller Souscription',
                'description' => "Rôle conseiller souscription disposant de conseiller étendus pour la gestion des souscriptions.",
                'is_system' => false,
                'is_super_admin' => false,
                'is_default' => false,
                'level' => 11,
                'priority' => 11,
                'status' => 'actif',
            ]
        );
        // ============================================================
        // Rôle Banccass Souscription
        // ============================================================
        Role::firstOrCreate(
            ['code' => 'banccass_souscription'],
            [
                'libelle' => 'Banccass Souscription',
                'description' => "Rôle banccass Permettant de suivre toute les activitées  bancaire sur la platforme YNOV.",
                'is_system' => false,
                'is_super_admin' => false,
                'is_default' => false,
                'level' => 12,
                'priority' => 12,
                'status' => 'actif',
            ]
        );
        // ============================================================
        // Rôle Producteur Souscription
        // ============================================================
        Role::firstOrCreate(
            ['code' => 'producteur_souscription'],
            [
                'libelle' => 'Producteur Souscription',
                'description' => "Rôle producteur souscription lui permettant de traiter des souscriptions.",
                'is_system' => false,
                'is_super_admin' => false,
                'is_default' => false,
                'level' => 13,
                'priority' => 13,
                'status' => 'actif',
            ]
        );
    }
}


    