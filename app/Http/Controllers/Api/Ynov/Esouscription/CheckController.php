<?php

namespace App\Http\Controllers\Api\Ynov\Esouscription;

use App\Http\Controllers\Controller;
use App\Models\Api\Ynov\Esouscription\Acteur;
use App\Models\Api\Ynov\Esouscription\ContratActeur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CheckController extends Controller
{
    public function getPersonByNni(Request $request)
    {
        $validated = $request->validate([
            'nni' => ['required', 'string'],
        ]);

        try {
            $url = rtrim(config('services.rnpp.base_url'), '/')
                . '/api/rnpp/persons/' . rawurlencode($validated['nni']);

            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'X-Api-Key' => config('services.rnpp.api_key'),
            ])
                ->timeout(15)
                ->get($url);

                
                // Log du contenu brut pour debug
                Log::info("RNPP API status: " . $response->status());
                Log::info("RNPP API body: " . $response->body());

                $data = $response->json();

                if ($response->status() === 400) {
                    return response()->json([
                        'success' => false,
                        'code'    => 'CHECK_PERSON_BY_NNI_ERROR',
                        'message' => $data['message'] ?? 'Erreur de validation RNPP',
                    ], 400);
                }

                if ($response->failed()) {
                    return response()->json([
                        'success' => false,
                        'code'    => 'RNPP_API_ERROR',
                        'message' => $data['message'] ?? 'Erreur API RNPP',
                    ], $response->status());
                }


            return response()->json([
                'success' => true,
                'code' => 'CHECK_PERSON_BY_NNI_SUCCESS',
                'message' => 'Personne trouvée avec le NNI ' . $validated['nni'] . '.',
                'data' => $response->json(),
            ],200);
            
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'code' => 'CHECK_PERSON_BY_NNI_ERROR',
                'message' => 'Impossible de vérifier le NNI.',
            ], 500);
        }
    }

    public function getPersonByIdClient(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_client' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'code'    => 'CHECK_ADHERENT_BY_IDCLIENT_INCONNU',
                'message' => 'Le parametre id_client est obligatoire',
            ], 400);
        }

        $validated = $validator->validated();

        try {

            $clienTrouver = Acteur::with('RelationContrat')->where('idClient', $validated['id_client'])->first();

            if(!$clienTrouver){
                return response()->json([
                    'success' => false,
                    'code' => 'CHECK_ADHERENT_BY_IDCLIENT_ERROR',
                    'message' => 'L\'adherent avec ID ' . $validated['id_client'] . ' n\'existe pas.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'code' => 'CHECK_ADHERENT_BY_IDCLIENT_SUCCESS',
                'message' => 'Le client avec l\'id ' . $validated['id_client'] . ' est trouvée.',
                'data' => $clienTrouver,
            ]);
        } catch (\Throwable $th) {

            Log::error("Error de recuperation du client: " . $th->getMessage());

            return response()->json([
                'success' => false,
                'code' => 'CHECK_ADHERENT_BY_IDCLIENT_ERROR',
                'message' => 'Erreur de recuperation du client.',
            ], 500);
        }
    }

 
}