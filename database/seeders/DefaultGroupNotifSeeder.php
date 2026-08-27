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
        // Créer le groupe de notification "Sécurité"
        $securityGroup = GroupNotif::firstOrCreate(
            ['code' => 'securite'],
            [
                'uuid_group_notif' => (string) Str::uuid(),
                'libelle' => 'Sécurité',
                'description' => 'Notifications liées à la sécurité du compte : gel, dégel, alertes de connexion, etc.',
                'channels' => ['database', 'email'],
                'preferences' => [
                    'email_enabled' => true,
                    'database_enabled' => true,
                    'push_enabled' => false,
                ],
                'status' => 'actif',
            ]
        );

        // Créer le groupe "Compte"
        GroupNotif::firstOrCreate(
            ['code' => 'compte'],
            [
                'uuid_group_notif' => (string) Str::uuid(),
                'libelle' => 'Compte',
                'description' => 'Notifications liées à la gestion du compte : modifications, vérifications, etc.',
                'channels' => ['database', 'email'],
                'preferences' => [
                    'email_enabled' => true,
                    'database_enabled' => true,
                    'push_enabled' => false,
                ],
                'status' => 'actif',
            ]
        );

        // Créer le groupe "Sinistres"
        GroupNotif::firstOrCreate(
            ['code' => 'sinistres'],
            [
                'uuid_group_notif' => (string) Str::uuid(),
                'libelle' => 'Sinistres',
                'description' => 'Notifications liées aux sinistres et réclamations.',
                'channels' => ['database', 'email'],
                'preferences' => [
                    'email_enabled' => true,
                    'database_enabled' => true,
                    'push_enabled' => true,
                ],
                'status' => 'actif',
            ]
        );

        // Créer le groupe "Paiements"
        GroupNotif::firstOrCreate(
            ['code' => 'paiements'],
            [
                'uuid_group_notif' => (string) Str::uuid(),
                'libelle' => 'Paiements',
                'description' => 'Notifications liées aux paiements, factures et échéances.',
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
    }
}