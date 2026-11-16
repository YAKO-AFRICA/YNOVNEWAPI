<?php

namespace Tests\Unit;

use App\Models\Api\Ynov\parameter\ProduitGarantie;
use App\Services\Api\Ynov\Simulateur\YakoEterniteSimulatorService;
use App\Services\Api\Ynov\Simulateur\YakoEterniteTarificationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\CreatesApplication;

class YakoEterniteSimulatorServiceTest extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.yako_tarification.base_url' => 'https://tarification.test/enov',
            'services.yako_tarification.authorization' => 'Bearer test-token',
        ]);
    }

    public function test_it_calculates_the_guarantees_and_allows_a_custom_senior_premium(): void
    {
        Http::fake(function (Request $request) {
            $premium = $request->data()['codeGarantie'] === 'HOMMAGE' ? 12000 : 2000;

            return Http::response([[
                'Prime' => $premium,
                'Capital' => $request->data()['capitalSouscrit'],
            ]]);
        });

        $dateOfBirth = Carbon::now()->subYears(30)->format('Y-m-d');
        $result = $this->simulator()->simulate([
            'CodeProduit' => 'yke_2026',
            'CodePeriodicite' => 'M',
            'Capital' => 1000000,
            'DateNaissance' => $dateOfBirth,
            'DateEffet' => '2026-12-01',
            'IsAssure' => 'oui',
            'AjouterGarantieSenior' => true,
            'PrimeSenior' => 4000,
        ]);

        $this->assertSame(2, count($result['garantieData']['garantieObligatoire']));
        $this->assertSame(1, count($result['garantieData']['garantieOptionnel']));
        $this->assertSame(12000.0, $result['garantieData']['garantieObligatoire'][0]['prime']);
        $this->assertSame(2000.0, $result['garantieData']['garantieObligatoire'][1]['prime']);
        $this->assertSame(4000.0, $result['garantieData']['garantieOptionnel'][0]['prime']);
        $this->assertSame(18000.0, $result['infoSimulation']['primepricipale']);
        $this->assertSame(25500.0, $result['infoSimulation']['primeFinal']);
        $this->assertSame('yke_2026', $result['infoSimulation']['codeProduit']);

        Http::assertSentCount(2);
        Http::assertSent(fn (Request $request) => $request->hasHeader(
            'Authorization',
            'Bearer test-token'
        ) && $request->data()['CodeProduit'] === 'yke_2026'
            && (int) $request->data()['duree'] === 5
            && (int) $request->data()['age'] === Carbon::parse($dateOfBirth)->age);
    }

    public function test_it_uses_the_selected_2008_and_2018_product_codes_for_tarification(): void
    {
        Http::fake([
            '*' => Http::response([['Prime' => 1000, 'Capital' => 500000]]),
        ]);

        foreach (['yke_2008', 'yke_2018'] as $productCode) {
            $result = $this->simulator()->simulate([
                'CodeProduit' => $productCode,
                'CodePeriodicite' => 'A',
                'Capital' => 500000,
                'DateNaissance' => Carbon::now()->subYears(30)->format('Y-m-d'),
                'DateEffet' => '2026-12-01',
                'IsAssure' => 'oui',
                'AjouterGarantieSenior' => false,
            ]);

            $this->assertSame($productCode, $result['infoSimulation']['codeProduit']);
            Http::assertSent(fn (Request $request) => $request->data()['CodeProduit'] === $productCode);
        }

        Http::assertSentCount(6);
    }

    public function test_it_fails_when_the_tarification_response_has_no_valid_premium(): void
    {
        Http::fake([
            '*' => Http::response([['Prime' => null, 'Capital' => 1000000]]),
        ]);

        try {
            $this->simulator()->simulate([
                'CodeProduit' => 'yke_2026',
                'CodePeriodicite' => 'A',
                'Capital' => 1000000,
                'DateNaissance' => Carbon::now()->subYears(30)->format('Y-m-d'),
                'DateEffet' => '2026-12-01',
                'IsAssure' => 'non',
                'AjouterGarantieSenior' => false,
            ]);
            $this->fail('Expected a tarification exception.');
        } catch (YakoEterniteTarificationException $exception) {
            $this->assertStringContainsString('HOMMAGE', $exception->getMessage());
        }
    }

    private function simulator(): YakoEterniteSimulatorService
    {
        return new class extends YakoEterniteSimulatorService
        {
            protected function getGuarantees(string $productCode): Collection
            {
                return new Collection([
                    new ProduitGarantie([
                        'uuid_produit_garantie' => 'hommage-uuid',
                        'code_produit_garantie' => 'HOMMAGE',
                        'libelle' => 'Hommage',
                        'est_obligatoire' => true,
                    ]),
                    new ProduitGarantie([
                        'uuid_produit_garantie' => 'surete-uuid',
                        'code_produit_garantie' => 'SURETE',
                        'libelle' => 'Sureté',
                        'est_obligatoire' => true,
                    ]),
                    new ProduitGarantie([
                        'uuid_produit_garantie' => 'senior-uuid',
                        'code_produit_garantie' => 'SENIOR',
                        'libelle' => 'Sénior',
                        'est_obligatoire' => false,
                    ]),
                ]);
            }
        };
    }
}
