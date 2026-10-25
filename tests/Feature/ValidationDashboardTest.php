<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\Ynov\Esouscription\ValidationController;
use App\Services\Api\Ynov\ESouscription\ContratService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ValidationDashboardTest extends TestCase
{
    public function test_dashboard_rejects_a_reversed_date_range(): void
    {
        $this->expectException(ValidationException::class);

        (new ValidationController(new ContratService()))->dashboard(
            Request::create('/api/v1/esouscription/dashboard-validation', 'GET', [
                'date_debut' => '2026-10-20',
                'date_fin' => '2026-10-01',
            ])
        );
    }
}
