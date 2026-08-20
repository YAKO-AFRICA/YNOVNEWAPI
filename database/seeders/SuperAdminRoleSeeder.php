<?php
namespace Database\Seeders;

use App\Models\Api\Ynov\parameter\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SuperAdminRoleSeeder extends Seeder
{
    public function run(): void
    {
            Role::firstOrCreate(
            ['code' => 'super_admin'],
            [
                'libelle' => 'Super Administrateur',
                'description' => 'Rôle disposant de tous les droits sur la plateforme. Non modifiable et non supprimable via interface.',
                'is_system' => true,
                'is_super_admin' => true,
                'level' => 1,
                'priority' => 0,
                'status' => 'actif',
            ]
        );
    }
}


    