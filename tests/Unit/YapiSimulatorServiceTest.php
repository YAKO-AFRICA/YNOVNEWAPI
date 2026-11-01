<?php

namespace Tests\Unit;

use App\Models\Api\Ynov\parameter\ProduitGarantie;
use App\Services\Api\Ynov\Simulateur\YapiSimulatorException;
use App\Services\Api\Ynov\Simulateur\YapiSimulatorService;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\TestCase;

class YapiSimulatorServiceTest extends TestCase
{
    public function test_it_returns_the_premium_for_a_500000_capital(): void
    {
        $result = $this->simulator()->simulate(['Capital' => 500000]);

        $this->assertSame([
            'garantieObligatoire' => [[
                'uuid_garantie' => 'required-guarantee',
                'codeGarantie' => 'ASSFUN_ADH',
                'libelle' => 'ASSISTANCE FUNERAILLES ADHERANT',
                'capital' => 500000,
                'prime' => 30000,
            ]],
            'garantieOptionnel' => [[
                'uuid_garantie' => 'optional-guarantee',
                'codeGarantie' => 'ASSFUN_CONJT',
                'libelle' => 'ASSISTANCE FUNERAILLES CONJOINT',
                'capital' => 500000,
                'prime' => 0,
            ]],
        ], $result['garantieData']);
        $this->assertSame([
            'codeProduit' => 'YAPI',
            'capital' => 500000,
            'primepricipale' => 30000,
            'primeFinale' => 30000,
        ], $result['infoSimulation']);
    }

    public function test_it_returns_the_premium_for_a_1000000_capital(): void
    {
        $result = $this->simulator()->simulate(['Capital' => 1000000]);

        $this->assertSame([
            'codeProduit' => 'YAPI',
            'capital' => 1000000,
            'primepricipale' => 58000,
            'primeFinale' => 58000,
        ], $result['infoSimulation']);
        $this->assertSame(58000, $result['garantieData']['garantieObligatoire'][0]['prime']);
    }

    public function test_it_requires_a_configured_mandatory_guarantee(): void
    {
        $simulator = new class extends YapiSimulatorService
        {
            protected function getGuarantees(): Collection
            {
                return new Collection;
            }
        };

        $this->expectException(YapiSimulatorException::class);
        $simulator->simulate(['Capital' => 500000]);
    }

    private function simulator(): YapiSimulatorService
    {
        return new class extends YapiSimulatorService
        {
            protected function getGuarantees(): Collection
            {
                return new Collection([
                    new ProduitGarantie([
                        'uuid_produit_garantie' => 'required-guarantee',
                        'code_produit_garantie' => 'ASSFUN_ADH',
                        'libelle' => 'ASSISTANCE FUNERAILLES ADHERANT',
                        'est_obligatoire' => true,
                    ]),
                    new ProduitGarantie([
                        'uuid_produit_garantie' => 'optional-guarantee',
                        'code_produit_garantie' => 'ASSFUN_CONJT',
                        'libelle' => 'ASSISTANCE FUNERAILLES CONJOINT',
                        'est_obligatoire' => false,
                    ]),
                ]);
            }
        };
    }
}
