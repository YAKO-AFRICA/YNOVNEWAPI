<?php

namespace App\Http\Controllers\Api\Ynov\Esouscription;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

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
}
