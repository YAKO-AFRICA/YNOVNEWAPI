<?php
// database/seeders/DefaultGroupNotifSeeder.php

namespace Database\Seeders;

use App\Models\Api\Ynov\parameter\GroupNotif;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DefaultGroupNotifSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ============================================================
        // 1) GROUPE SÉCURITÉ
        // ============================================================
        GroupNotif::firstOrCreate(
            ['code' => 'securite'],
            [
                'uuid_group_notif' => (string) Str::uuid(),
                'libelle' => 'Sécurité',
                'description' => 'Notifications liées à la sécurité du compte : gel, dégel, alertes de connexion, tentative de connexion échouée, blocage, déblocage, 2FA, etc.',
                'channels' => ['database', 'email'],
                'preferences' => [
                    'email_enabled' => true,
                    'database_enabled' => true,
                    'push_enabled' => false,
                    'sms_enabled' => false,
                ],
                'status' => 'actif',
            ]
        );

        // ============================================================
        // 2) GROUPE COMPTE
        // ============================================================
        GroupNotif::firstOrCreate(
            ['code' => 'compte'],
            [
                'uuid_group_notif' => (string) Str::uuid(),
                'libelle' => 'Compte',
                'description' => 'Notifications liées à la gestion du compte : création, modification, suppression, mise à jour du profil, changement de mot de passe, etc.',
                'channels' => ['database', 'email'],
                'preferences' => [
                    'email_enabled' => true,
                    'database_enabled' => true,
                    'push_enabled' => false,
                    'sms_enabled' => false,
                ],
                'status' => 'actif',
            ]
        );

        // ============================================================
        // 3) GROUPE BIENVENUE
        // ============================================================
        GroupNotif::firstOrCreate(
            ['code' => 'welcome'],
            [
                'uuid_group_notif' => (string) Str::uuid(),
                'libelle' => 'Bienvenue',
                'description' => 'Notifications de bienvenue pour les nouveaux utilisateurs : création de compte, premier paiement, etc.',
                'channels' => ['database', 'email'],
                'preferences' => [
                    'email_enabled' => true,
                    'database_enabled' => true,
                    'push_enabled' => false,
                    'sms_enabled' => false,
                ],
                'status' => 'actif',
            ]
        );

        // ============================================================
        // 4) GROUPE DOCUMENTS
        // ============================================================
        GroupNotif::firstOrCreate(
            ['code' => 'documents'],
            [
                'uuid_group_notif' => (string) Str::uuid(),
                'libelle' => 'Documents',
                'description' => 'Notifications liées aux documents : téléchargement, validation, expiration, etc.',
                'channels' => ['database', 'email'],
                'preferences' => [
                    'email_enabled' => true,
                    'database_enabled' => true,
                    'push_enabled' => false,
                    'sms_enabled' => false,
                ],
                'status' => 'actif',
            ]
        );

        // ============================================================
        // 5) GROUPE SINISTRES
        // ============================================================
        GroupNotif::firstOrCreate(
            ['code' => 'sinistres'],
            [
                'uuid_group_notif' => (string) Str::uuid(),
                'libelle' => 'Sinistres',
                'description' => 'Notifications liées aux sinistres et réclamations : déclaration, suivi, validation, etc.',
                'channels' => ['database', 'email'],
                'preferences' => [
                    'email_enabled' => true,
                    'database_enabled' => true,
                    'push_enabled' => true,
                    'sms_enabled' => false,
                ],
                'status' => 'actif',
            ]
        );

        // ============================================================
        // 6) GROUPE PAIEMENTS
        // ============================================================
        GroupNotif::firstOrCreate(
            ['code' => 'paiements'],
            [
                'uuid_group_notif' => (string) Str::uuid(),
                'libelle' => 'Paiements',
                'description' => 'Notifications liées aux paiements, factures et échéances : paiement effectué, facture impayée, échéance à venir, etc.',
                'channels' => ['database', 'email', 'sms'],
                'preferences' => [
                    'email_enabled' => true,
                    'database_enabled' => true,
                    'sms_enabled' => true,
                    'push_enabled' => true,
                ],
                'status' => 'actif',
            ]
        );

        // ============================================================
        // 7) GROUPE CONTRATS
        // ============================================================
        GroupNotif::firstOrCreate(
            ['code' => 'contrats'],
            [
                'uuid_group_notif' => (string) Str::uuid(),
                'libelle' => 'Contrats',
                'description' => 'Notifications liées aux contrats : souscription, échéance, renouvellement, modification, etc.',
                'channels' => ['database', 'email'],
                'preferences' => [
                    'email_enabled' => true,
                    'database_enabled' => true,
                    'push_enabled' => false,
                    'sms_enabled' => false,
                ],
                'status' => 'actif',
            ]
        );

        // ============================================================
        // 8) GROUPE PROMOTIONS
        // ============================================================
        GroupNotif::firstOrCreate(
            ['code' => 'promotions'],
            [
                'uuid_group_notif' => (string) Str::uuid(),
                'libelle' => 'Promotions',
                'description' => 'Notifications liées aux offres promotionnelles, réductions et avantages.',
                'channels' => ['database', 'email'],
                'preferences' => [
                    'email_enabled' => true,
                    'database_enabled' => false,
                    'push_enabled' => true,
                    'sms_enabled' => false,
                ],
                'status' => 'actif',
            ]
        );

        // ============================================================
        // 9) GROUPE SYSTEME
        // ============================================================
        GroupNotif::firstOrCreate(
            ['code' => 'systeme'],
            [
                'uuid_group_notif' => (string) Str::uuid(),
                'libelle' => 'Système',
                'description' => 'Notifications système : maintenance, mise à jour, nouvelles fonctionnalités, etc.',
                'channels' => ['database', 'email'],
                'preferences' => [
                    'email_enabled' => true,
                    'database_enabled' => true,
                    'push_enabled' => false,
                    'sms_enabled' => false,
                ],
                'status' => 'actif',
            ]
        );

        // ============================================================
        // 10) GROUPE RENDEZ-VOUS
        // ============================================================
        GroupNotif::firstOrCreate(
            ['code' => 'rendezvous'],
            [
                'uuid_group_notif' => (string) Str::uuid(),
                'libelle' => 'Rendez-vous',
                'description' => 'Notifications liées aux rendez-vous : confirmation, rappel, annulation, etc.',
                'channels' => ['database', 'email', 'sms'],
                'preferences' => [
                    'email_enabled' => true,
                    'database_enabled' => true,
                    'sms_enabled' => true,
                    'push_enabled' => true,
                ],
                'status' => 'actif',
            ]
        );

        $this->command->info('✅ Groupes de notification créés avec succès !');
        $this->command->info('📋 Liste des groupes :');
        $this->command->info('   - securite (Sécurité)');
        $this->command->info('   - compte (Compte)');
        $this->command->info('   - welcome (Bienvenue)');
        $this->command->info('   - documents (Documents)');
        $this->command->info('   - sinistres (Sinistres)');
        $this->command->info('   - paiements (Paiements)');
        $this->command->info('   - contrats (Contrats)');
        $this->command->info('   - promotions (Promotions)');
        $this->command->info('   - systeme (Système)');
        $this->command->info('   - rendezvous (Rendez-vous)');
    }
}