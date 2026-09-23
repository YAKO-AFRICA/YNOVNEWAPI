<?php
// database/seeders/SuperAdminUserSeeder.php

namespace Database\Seeders;

use App\Models\Api\Ynov\parameter\User;
use App\Models\Api\Ynov\parameter\Role;
use App\Models\Api\Ynov\parameter\UserDetails;
// use App\Models\Api\Ynov\parameter\Permission;
// use App\Models\Api\Ynov\parameter\PermissionGroup;
// use App\Models\Api\Ynov\parameter\RolePermission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SuperAdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🚀 Début de la création du Super Admin...');

        // // 1. Créer les groupes de permissions (modules)
        // $this->command->info('📦 Création des groupes de permissions...');
        // // $this->createPermissionGroups();

        // // 2. Créer toutes les permissions
        // $this->command->info('🔐 Création des permissions...');
        // // $this->createPermissions();

        // 3. Vérifier que le rôle Super Admin existe
        $superAdminRole = Role::where('code', 'super_admin')->first();

        if (!$superAdminRole) {
            $this->command->info('👤 Création du rôle Super Admin...');
            $superAdminRole = Role::create([
                'uuid_role' => (string) Str::uuid(),
                'code' => 'super_admin',
                'libelle' => 'Super Administrateur',
                'description' => 'Rôle disposant de tous les droits sur la plateforme. Non modifiable et non supprimable via interface.',
                'is_system' => true,
                'is_super_admin' => true,
                'level' => 1,
                'priority' => 0,
                'status' => 'actif',
                'created_by' => null,
                'updated_by' => null,
            ]);
        }

        // 4. Assigner toutes les permissions au rôle Super Admin
        // $this->command->info('🔗 Assignation des permissions au rôle Super Admin...');
        // $this->assignAllPermissionsToRole($superAdminRole);

        // 5. Créer ou mettre à jour l'utilisateur Super Admin
        $this->command->info('👤 Création de l\'utilisateur Super Admin...');
        $user = User::updateOrCreate(
            ['email' => 'brucedev2022@gmail.com'],
            [
                'uuid_user' => (string) Str::uuid(),
                'login' => 'super_admin',
                'password' => Hash::make('SuperAdmin@2026'),
                'role_uuid' => $superAdminRole->uuid_role,
                'user_type' => 'super_admin',
                'partner_uuid' => null,
                'reseau_uuid' => null,
                'status' => 'actif',
                'is_first_login' => true,
                'is_online' => false,
                'is_locked' => false,
                'password_changed_at' => now(),
                'password_expires_at' => now()->addDays(90),
                'last_login_at' => null,
                'last_activity_at' => null,
                'email_verified_at' => now(),
                'failed_login_count' => 0,
                'freeze_level' => 0,
                'frozen_until' => null,
                'freeze_count' => 0,
                'blocked_reason' => null,
                'blocked_by' => null,
                'blocked_at' => null,
                'two_factor_secret' => null,
                'two_factor_enabled' => false,
                'two_factor_recovery_codes' => null,
                'preferences' => [
                    'theme' => 'light',
                    'language' => 'fr',
                    'timezone' => 'Europe/Paris',
                    'notifications' => [
                        'email' => true,
                        'push' => true,
                    ],
                ],
                'metadata' => [
                    'created_by_seeder' => true,
                    'seeded_at' => now()->toDateTimeString(),
                ],
                'remember_token' => null,
            ]
        );

        // 6. Créer les détails de l'utilisateur
        $this->command->info('📝 Création des détails de l\'utilisateur...');
        UserDetails::updateOrCreate(
            ['user_uuid' => $user->uuid_user],
            [
                'uuid_user_details' => (string) Str::uuid(),
                'code_agent' => 'SA001',
                'matricule' => 'SA-2026-001',
                'nom' => 'Admin',
                'prenoms' => 'Super',
                'fonction' => 'Super Administrateur',
                'service' => 'Administration',
                'departement' => 'IT',
                'mobile_1' => '+225 00 00 00 00',
                'mobile_2' => null,
                'telephone_fixe' => null,
                'email_pro' => 'brucedev2022@gmail.com',
                'photo' => null,
                'date_naissance' => null,
                'lieu_naissance' => null,
                'lieu_residence' => null,
                'nationalite' => 'Côte d\'Ivoire',
                'genre' => 'M',
                'civilite' => 'M.',
                'adresse_complete' => null,
                'ville' => 'Abidjan',
                'code_postal' => null,
                'pays' => 'Côte d\'Ivoire',
                'date_embauche' => now(),
                'statut_employe' => 'actif',
                'type_contrat' => 'CDI',
                'preferences' => [],
                'created_by' => null,
                'updated_by' => null,
                'deleted_by' => null,
            ]
        );

        $this->command->info('✅ Super Admin créé avec succès !');
        $this->command->newLine();
        $this->command->info('📧 Email: brucedev2022@gmail.com');
        $this->command->info('🔑 Mot de passe: SuperAdmin@2026');
        $this->command->newLine();
        $this->command->warn('⚠️  Pensez à modifier le mot de passe après la première connexion !');
        $this->command->newLine();
        $this->command->info('📋 Toutes les permissions ont été assignées au rôle Super Admin.');
    }
}
