<?php

namespace App\Http\Controllers\Api\Ynov\Esouscription;

use App\Http\Controllers\Controller;
use App\Models\Api\Ynov\Esouscription\Acteur;
use App\Models\Api\Ynov\Esouscription\ContratActeur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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

            return response()->json($response->json(), $response->status());
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Impossible de vérifier le NNI.',
            ], 500);
        }
    }

    public function getPersonByIdClient(Request $request)
    {
        $validated = $request->validate([
            'id_client' => ['required', 'string'],
        ]);

        if(!$request->has('id_client')){
            return response()->json([
                'message' => 'Le parametre id_client est obligatoire.',
            ], 404);
        }

        try {

            $clienTrouver = Acteur::with('RelationContrat')->where('idClient', $validated['id_client'])->first();

            if(!$clienTrouver){
                return response()->json([
                    'message' => 'Le client n\'existe pas.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Le client avec l\'id ' . $validated['id_client'] . ' est trouvée.',
                'data' => $clienTrouver,
            ]);
        } catch (\Throwable $th) {

            Log::error("Error de recuperation du client: " . $th->getMessage());

            return response()->json([
                'message' => 'Le client n\'existe pas.',
            ], 404);
        }
    }
}
