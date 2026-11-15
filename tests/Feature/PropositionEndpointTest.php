<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\Ynov\Esouscription\PropositionController;
use App\Services\Api\Ynov\Documents\DocumentService;
use App\Services\Api\Ynov\ESouscription\ActeurService;
use App\Services\Api\Ynov\ESouscription\AssurerGarantieService;
use App\Services\Api\Ynov\ESouscription\ClientNumberGenerator;
use App\Services\Api\Ynov\ESouscription\ContratActeurService;
use App\Services\Api\Ynov\ESouscription\ContratService;
use App\Services\Api\Ynov\ESouscription\DeclarationSanteService;
use App\Services\Api\Ynov\ESouscription\PropositionService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

class PropositionEndpointTest extends TestCase
{
    public function test_get_propositions_forwards_supported_search_filters(): void
    {
        $filters = [
            'per_page' => 20,
            'uuid_contrat' => 'contrat-uuid',
            'id_contrat' => 123,
            'id_proposition' => 456,
            'code_proposition' => 'PROP-',
            'numero_police' => 'POL-',
            'numero_compte' => '12345',
            'date_debut' => '2026-10-01',
            'date_fin' => '2026-10-31',
            'idClient' => 'CLI-',
            'numero_piece' => 'CNI-',
            'nom' => 'Diallo',
            'prenoms' => 'Awa',
            'search' => 'Awa',
        ];
        $paginator = new LengthAwarePaginator([], 0, 20);
        $propositionService = Mockery::mock(PropositionService::class);
        $propositionService
            ->shouldReceive('getForUser')
            ->once()
            ->with('user-uuid', 20, $filters)
            ->andReturn($paginator);

        $controller = $this->makeController($propositionService);
        $request = Request::create('/api/v1/esouscription/get-propositions/user-uuid', 'GET', $filters);
        $request->setUserResolver(fn () => (object) ['uuid_user' => 'user-uuid']);

        $response = $controller->getPropositions($request, 'user-uuid');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(0, $response->getData(true)['total']);
    }

    public function test_get_propositions_rejects_reversed_date_range(): void
    {
        $this->expectException(ValidationException::class);

        $propositionService = Mockery::mock(PropositionService::class);
        $propositionService->shouldNotReceive('getForUser');
        $controller = $this->makeController($propositionService);
        $request = Request::create('/api/v1/esouscription/get-propositions/user-uuid', 'GET', [
            'date_debut' => '2026-10-31',
            'date_fin' => '2026-10-01',
        ]);
        $request->setUserResolver(fn () => (object) ['uuid_user' => 'user-uuid']);

        $controller->getPropositions($request, 'user-uuid');
    }

    public function test_update_proposition_requires_at_least_one_section(): void
    {
        $this->expectException(ValidationException::class);

        $controller = $this->makeController(Mockery::mock(PropositionService::class));
        $controller->updateProposition(
            Request::create('/api/v1/esouscription/update-proposition/contrat-uuid', 'PUT'),
            'contrat-uuid'
        );
    }

    public function test_update_proposition_requires_complete_info_for_a_new_actor(): void
    {
        $this->expectException(ValidationException::class);

        $controller = $this->makeController(Mockery::mock(PropositionService::class));
        $controller->updateProposition(
            Request::create('/api/v1/esouscription/update-proposition/contrat-uuid', 'PUT', [
                'BeneficiaireDatas' => [
                    ['is_adherent' => 'non', 'info' => ['nom' => 'Diallo']],
                ],
            ]),
            'contrat-uuid'
        );
    }

    private function makeController(PropositionService $propositionService): PropositionController
    {
        return new PropositionController(
            Mockery::mock(ClientNumberGenerator::class),
            Mockery::mock(ActeurService::class),
            Mockery::mock(ContratActeurService::class),
            Mockery::mock(DeclarationSanteService::class),
            Mockery::mock(DocumentService::class),
            Mockery::mock(ContratService::class),
            Mockery::mock(AssurerGarantieService::class),
            $propositionService
        );
    }
}
