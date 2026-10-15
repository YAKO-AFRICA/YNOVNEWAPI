<?php

namespace Tests\Unit;

use App\Services\Api\Ynov\Simulateur\YapiSimulatorService;
use PHPUnit\Framework\TestCase;

class YapiSimulatorServiceTest extends TestCase
{
    public function test_it_returns_the_premium_for_a_500000_capital(): void
    {
        $result = (new YapiSimulatorService())->simulate(['Capital' => 500000]);

        $this->assertSame([
            'codeProduit' => 'YAPI',
            'capital' => 500000,
            'prime' => 30000,
        ], $result);
    }

    public function test_it_returns_the_premium_for_a_1000000_capital(): void
    {
        $result = (new YapiSimulatorService())->simulate(['Capital' => 1000000]);

        $this->assertSame([
            'codeProduit' => 'YAPI',
            'capital' => 1000000,
            'prime' => 58000,
        ], $result);
    }
}
