<?php

namespace Tests\Unit;

use App\Models\Api\Ynov\parameter\ProduitGarantie;
use App\Services\Api\Ynov\Simulateur\DoihooSimulatorService;
use App\Services\Api\Ynov\Simulateur\DoihooTarificationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\CreatesApplication;

class DoihooSimulatorServiceTest extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'doihoo_simulator_test',
            'services.yako_tarification.base_url' => 'https://tarification.test/enov',
            'services.yako_tarification.authorization' => 'Bearer test-token',
        ]);
    }

    public function test_it_calculates_each_doihoo_guarantee_and_the_total_due(): void
    {
        Http::fake(function (Request $request) {
            if (str_ends_with($request->url(), '/get-table-prime-web')) {
                return Http::response([
                    'dataTablePrime' => [
                        [
                            'CodeProduitGarantie' => 'INV_2020',
                            'CodeGRoupeIntervalle' => 'GROUP-1',
                            'codeTable' => 'TABLE-1',
                        ],
                        [
                            'CodeProduitGarantie' => 'DOI_2020',
                            'CodeGRoupeIntervalle' => 'GROUP-2',
                            'codeTable' => 'TABLE-2',
                        ],
                    ],
                ]);
            }

            return Http::response([
                'dataTablePrimeRes' => [[
                    'Prime' => $request->data()['codeTable'] === 'TABLE-1' ? 12000 : 2500,
                ]],
            ]);
        });

        $simulator = new class extends DoihooSimulatorService
        {
            protected function getGuarantees(): Collection
            {
                return new Collection([
                    new ProduitGarantie([
                        'code_produit' => 'DOIHOO',
                        'code_produit_garantie' => 'INV_2020',
                        'libelle' => 'INVEST',
                        'branche' => 'IND',
                    ]),
                    new ProduitGarantie([
                        'code_produit' => 'DOIHOO',
                        'code_produit_garantie' => 'DOI_2020',
                        'libelle' => 'DOIHOO',
                        'branche' => 'IND',
                    ]),
                ]);
            }
        };

        $result = $simulator->simulate([
            'CodeProduit' => DoihooSimulatorService::CODE_PRODUIT,
            'CodePeriodicite' => 'MENSUELLE',
            'Capital' => 1000000,
            'AgeAssure' => 35,
            'Duree' => DoihooSimulatorService::DUREE_CONTRAT,
        ]);

        $this->assertCount(2, $result['garantieData']);
        $this->assertSame(12000.0, $result['garantieData'][0]['prime']);
        $this->assertSame(2500.0, $result['garantieData'][1]['prime']);
        $this->assertSame(14500.0, $result['infoSimulation']['primepricipale']);
        $this->assertSame(7500, $result['infoSimulation']['fraisAdhesion']);
        $this->assertSame(22000.0, $result['infoSimulation']['primeFinale']);

        Http::assertSentCount(3);
        Http::assertSent(fn (Request $request) => $request->hasHeader('Authorization', 'Bearer test-token'));
    }

    public function test_it_reports_the_tarification_error_message_without_logging_credentials(): void
    {
        Http::fake(function (Request $request) {
            if (str_ends_with($request->url(), '/get-table-prime-web')) {
                return Http::response([
                    'dataTablePrime' => [[
                        'CodeProduitGarantie' => 'INV_2020',
                        'CodeGRoupeIntervalle' => 'GROUP-1',
                        'codeTable' => 'TABLE-1',
                    ]],
                ]);
            }

            return Http::response([
                'error' => true,
                'message' => 'Aucun tarif trouvé pour la périodicité.',
            ]);
        });

        $simulator = new class extends DoihooSimulatorService
        {
            protected function getGuarantees(): Collection
            {
                return new Collection([
                    new ProduitGarantie([
                        'code_produit_garantie' => 'INV_2020',
                        'libelle' => 'INVEST',
                    ]),
                ]);
            }
        };

        try {
            $simulator->simulate([
                'CodeProduit' => DoihooSimulatorService::CODE_PRODUIT,
                'CodePeriodicite' => 'M',
                'Capital' => 1000000,
                'AgeAssure' => 35,
                'Duree' => DoihooSimulatorService::DUREE_CONTRAT,
            ]);
            $this->fail('Expected a tarification exception.');
        } catch (DoihooTarificationException $exception) {
            $this->assertStringContainsString(
                'Aucun tarif trouvé pour la périodicité.',
                $exception->getMessage()
            );
            $this->assertStringNotContainsString(
                'test-token',
                $exception->getMessage()
            );
        }
    }
}
