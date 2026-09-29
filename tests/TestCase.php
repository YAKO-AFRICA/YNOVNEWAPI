<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Traits\WithTestDatabase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    use WithTestDatabase;

    /**
     * Setup the test environment
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Setup test database for each test
        $this->setupTestDatabase();
    }
}
