<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\Ynov\Esouscription\ValidationController;
use Illuminate\Http\Request;
use Tests\TestCase;

class ValidationRouteTest extends TestCase
{
    public function test_distinct_partners_route_uses_validation_controller(): void
    {
        $route = $this->app['router']->getRoutes()->match(
            Request::create('/api/v1/esouscription/get-partners-distinct')
        );

        $this->assertSame(ValidationController::class . '@getPartnerDistinct', $route->getActionName());
    }

    public function test_partner_contracts_route_uses_validation_controller(): void
    {
        $route = $this->app['router']->getRoutes()->match(
            Request::create('/api/v1/esouscription/get-contrats-by-partner/123e4567-e89b-12d3-a456-426614174000')
        );

        $this->assertSame(ValidationController::class . '@getContratsByPartner', $route->getActionName());
    }
}
