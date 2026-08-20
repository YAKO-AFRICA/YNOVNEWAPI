<?php

namespace App\Http\Controllers\Api\Ynov\EspaceClient;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class CustomerController extends Controller
{

    public function index(Request $request)
    {
        return response()->json(['type' => 'success']);
    }
    
    public function getContratDetails(Request $request)
    {
        $idcontrat = $request->input('idcontrat');
        if (!$idcontrat) {
            return response()->json([
                'status' => 'error',
                'message' => 'Aucun contrat sélectionné.',
            ], 400);
        }

        try {
            // Utiliser Guzzle directement pour un meilleur contrôle
            $response = Http::withOptions([
                'timeout' => 60,  // Augmenter le délai d'attente
            ])->post(config('services.api.encaissement_bis'), [
                'idContrat' => $idcontrat,
            ]);

            if ($response->successful()) {
                return response()->json([
                    'status' => 'success',
                    'data' => $response->json(),
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Impossible de récupérer les informations du contrat.',
                ], $response->status());
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Une erreur s\'est produite : ' . $e->getMessage(),
            ], 500);
        }
    }
}
