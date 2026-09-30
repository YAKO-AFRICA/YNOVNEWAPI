<?php

namespace Tests\Traits;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

trait WithTestDatabase
{
    use RefreshDatabase;

    /**
     * Setup the test database
     */
    protected function setupTestDatabase(): void
    {
        // Ensure we're using MySQL for testing as configured in .env.testing
        config([
            'database.default' => 'mysql',
            'database.connections.mysql.host' => env('DB_HOST', '51.255.64.8'),
            'database.connections.mysql.port' => env('DB_PORT', '3306'),
            'database.connections.mysql.database' => env('DB_DATABASE', 'laloyale_bdynovtest'),
            'database.connections.mysql.username' => env('DB_USERNAME', 'tyeeézrygerueazrgyazeza'),
            'database.connections.mysql.password' => env('DB_PASSWORD', 'zaryztguzerfbuzy'),
        ]);

        // Run migrations
        // Artisan::call('migrate:fresh');
    }

    /**
     * Seed the database with specific seeders
     */
    protected function seedTestDatabase(array $seeders = []): void
    {
        foreach ($seeders as $seeder) {
            Artisan::call('db:seed', ['--class' => $seeder]);
        }
    }

    /**
     * Run all seeders
     */
    protected function seedAll(): void
    {
        Artisan::call('db:seed');
    }
}
