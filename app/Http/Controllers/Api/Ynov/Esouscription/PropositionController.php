<?php

namespace App\Http\Controllers\Api\Ynov\Esouscription;

use App\Http\Controllers\Controller;
use App\Services\Api\Ynov\Esouscription\ActeurService;
use App\Services\Api\Ynov\Esouscription\ClientNumberGenerator;
use DateTimeImmutable;
use Illuminate\Http\Request;

class PropositionController extends Controller
{

    public function __construct(
        private ClientNumberGenerator $clientNumberGenerator,
        private ActeurService $acteurService
    ) {}
    public function storeSouscription(Request $request)
    {
        try {

            $adherentData = $request->all();

            $numeroClient = $this->clientNumberGenerator->generate(
                (int) $adherentData['genre'],
                new DateTimeImmutable($adherentData['date_naissance']),
            );

            $adherentStore = $this->acteurService->create([
                'idClient' => $numeroClient,
                'civilite' => $adherentData['civilite'],
                'genre' => $adherentData['genre'],
                'nom' => $adherentData['nom'],
                'prenoms' => $adherentData['prenoms'],
                'date_naissance' => $adherentData['date_naissance'],
                'lieunaissance_code' => $adherentData['lieunaissance_code'],
                'email' => $adherentData['email'],
                'mobile' => $adherentData['mobile'],
                'telephone' => $adherentData['telephone'],
                'numero_piece' => $adherentData['numero_piece'],
                'nni' => $adherentData['nni'],
                'nature_piece' => $adherentData['nature_piece'],
                'situation_matrimoniale' => $adherentData['situation_matrimoniale'],
                'profession_code' => $adherentData['profession_code'],
                'employeur' => $adherentData['employeur'],
                'lieuresidence_code' => $adherentData['lieuresidence_code'],
                'pays_code' => $adherentData['pays_code'],
                'integration_key' => $adherentData['integration_key'],
                'created_by' => $adherentData['created_by'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Souscription crée avec succès',
                'code' => 200,
                'data' => $adherentStore
            ]);

            
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}


