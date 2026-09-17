<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\Ynov\Prestation\PrestationController;
use App\Http\Controllers\Api\Ynov\Rdv\RdvController;
use Illuminate\Http\Request;
use Tests\TestCase;

class PrestationRouteTest extends TestCase
{
    public function test_prestation_categories_route_is_not_interpreted_as_a_single_prestation(): void
    {
        $route = $this->app['router']->getRoutes()->match(Request::create('/api/v1/prestations/categories'));

        $this->assertSame(PrestationController::class . '@categories', $route->getActionName());
    }

    public function test_rdv_detail_route_is_not_interpreted_as_a_single_rdv_route(): void
    {
        $route = $this->app['router']->getRoutes()->match(Request::create('/api/v1/rdvs/123e4567-e89b-12d3-a456-426614174000/detail-rdv'));

        $this->assertSame(RdvController::class . '@showDetailAdmin', $route->getActionName());
    }
}
