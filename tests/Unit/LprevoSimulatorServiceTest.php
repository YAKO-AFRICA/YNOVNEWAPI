<?php

namespace Tests\Unit;

use App\Models\Api\Ynov\parameter\ProduitGarantie;
use App\Services\Api\Ynov\Simulateur\LprevoSimulatorService;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\CreatesApplication;

class LprevoSimulatorServiceTest extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'lprevo_simulator_test']);
    }

    public function test_it_calculates_fixed_premiums_fees_and_pathology_surcharge(): void
    {
        $simulator = $this->simulator();

        $result = $simulator->simulate([
            'Capital' => 250000,
            'BonneSante' => false,
            'Pathologies' => ['Diabète', 'Hypertension'],
        ]);

        $this->assertSame('DTC/IAD', $result['garantieData'][0]['codeGarantie']);
        $this->assertSame(2500, $result['garantieData'][0]['prime']);
        $this->assertSame(250000, $result['garantieData'][0]['capital']);
        $this->assertSame(['Diabète', 'Hypertension'], $result['pathologies']);
        $this->assertSame(11000, $result['infoSimulation']['primePathologies']);
        $this->assertSame(19000, $result['infoSimulation']['primeFinal']);
        $this->assertSame(5500, $result['infoSimulation']['fraisadhesion']);
    }

    public function test_it_clears_pathologies_when_the_insured_declares_good_health(): void
    {
        $result = $this->simulator()->simulate([
            'Capital' => 100000,
            'BonneSante' => true,
            'Pathologies' => ['Cancer'],
        ]);

        $this->assertSame([], $result['pathologies']);
        $this->assertSame(0, $result['infoSimulation']['primePathologies']);
        $this->assertSame(6500, $result['infoSimulation']['primeFinal']);
        $this->assertTrue($result['infoSimulation']['bonneSante']);
    }

    public function test_it_uses_the_expected_base_premium_for_the_highest_capital(): void
    {
        $result = $this->simulator()->simulate([
            'Capital' => 500000,
            'BonneSante' => true,
            'Pathologies' => [],
        ]);

        $this->assertSame(5000, $result['infoSimulation']['primepricipale']);
        $this->assertSame(10500, $result['infoSimulation']['primeFinal']);
    }

    private function simulator(): LprevoSimulatorService
    {
        return new class extends LprevoSimulatorService
        {
            protected function getMainGuarantee(): ?ProduitGarantie
            {
                return new ProduitGarantie([
                    'code_produit_garantie' => 'DTC/IAD',
                    'libelle' => 'DECES TOUTES CAUSES INVALIDITE ABSOLUE ET DEFINITIVE',
                ]);
            }
        };
    }
}
