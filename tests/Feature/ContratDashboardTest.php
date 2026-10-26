<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\Ynov\Esouscription\ContratController;
use App\Services\Api\Ynov\ESouscription\ContratService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

class ContratDashboardTest extends TestCase
{
    public function test_dashboard_accepts_and_forwards_its_filters(): void
    {
        $filters = [
            'date_debut' => '2026-10-01',
            'date_fin' => '2026-10-20',
            'commercial_uuid' => 'commercial-uuid',
            'agence_uuid' => 'agence-uuid',
            'reseau_uuid' => 'reseau-uuid',
            'partner_uuid' => 'partner-uuid',
            'code_produit' => 'PRODUIT-1',
            'statut' => 'valide',
            'granularite' => 'trimestre',
        ];

        $service = Mockery::mock(ContratService::class);
        $service->shouldReceive('getDashboard')->once()->with($filters)->andReturn(['indicateurs' => []]);

        $response = (new ContratController($service))->dashboard(
            Request::create('/api/v1/esouscription/dashboard-contrats', 'GET', $filters)
        );

        $this->assertSame(['indicateurs' => []], $response->getData(true)['data']);
    }

    public function test_dashboard_accepts_a_single_date_and_all_requested_granularities(): void
    {
        foreach (['semaine', 'mois', 'trimestre', 'semestre', 'annee'] as $granularity) {
            $filters = ['date' => '2026-10-20', 'granularite' => $granularity];
            $service = Mockery::mock(ContratService::class);
            $service->shouldReceive('getDashboard')->once()->with($filters)->andReturn([]);

            (new ContratController($service))->dashboard(
                Request::create('/api/v1/esouscription/dashboard-contrats', 'GET', $filters)
            );
        }
    }

    public function test_dashboard_rejects_a_reversed_date_range(): void
    {
        $this->expectException(ValidationException::class);

        $service = Mockery::mock(ContratService::class);
        $service->shouldNotReceive('getDashboard');

        (new ContratController($service))->dashboard(
            Request::create('/api/v1/esouscription/dashboard-contrats', 'GET', [
                'date_debut' => '2026-10-20',
                'date_fin' => '2026-10-01',
            ])
        );
    }
}
