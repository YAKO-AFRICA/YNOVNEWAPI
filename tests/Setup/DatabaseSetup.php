<?php

namespace Tests\Setup;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

trait DatabaseSetup
{
    // use RefreshDatabase;

    protected function setUpDatabase(): void
    {
        // Setup SQLite in-memory database for testing
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);

        // Run migrations
        // $this->artisan('migrate:fresh');

        // Run seeders if needed
        // $this->artisan('db:seed');
    }

    protected function tearDownDatabase(): void
    {
        // Clean up if needed
    }
}
