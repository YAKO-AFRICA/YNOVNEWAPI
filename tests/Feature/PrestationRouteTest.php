<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\Ynov\Prestation\PrestationController;
use Illuminate\Http\Request;
use Tests\TestCase;

class PrestationRouteTest extends TestCase
{
    public function test_prestation_categories_route_is_not_interpreted_as_a_single_prestation(): void
    {
        $route = $this->app['router']->getRoutes()->match(Request::create('/api/v1/prestations/categories'));

        $this->assertSame(PrestationController::class . '@categories', $route->getActionName());
    }
}
