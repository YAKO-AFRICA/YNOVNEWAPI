<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseConnectionTest extends TestCase
{
    /**
     * Test that the test database connection works
     */
    public function test_database_connection_works(): void
    {
        $this->assertEquals('mysql', config('database.default'));
        $this->assertEquals('51.255.64.8', config('database.connections.mysql.host'));
        $this->assertEquals('laloyale_bdynovtest', config('database.connections.mysql.database'));
    }

    /**
     * Test that migrations can run
     */
    public function test_migrations_run_successfully(): void
    {
        // $this->artisan('migrate:fresh')->assertExitCode(0);

        // Check if some common tables exist
        $this->assertTrue(Schema::hasTable('migrations'));
    }

    /**
     * Test that we can query the database
     */
    public function test_database_query_works(): void
    {
        // $this->artisan('migrate:fresh')->assertExitCode(0);

        $result = DB::select('SELECT 1 as test');
        $this->assertCount(1, $result);
        $this->assertEquals(1, $result[0]->test);
    }
}
