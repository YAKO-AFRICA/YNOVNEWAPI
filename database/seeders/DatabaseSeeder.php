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
            PermissionSeeder::class,
            SuperAdminUserSeeder::class,
            FaqCategorySeeder::class,
        ]);
    }
}