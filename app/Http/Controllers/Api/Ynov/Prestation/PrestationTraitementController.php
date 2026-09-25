<?php

namespace App\Http\Controllers\Api\Ynov\Prestation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Ynov\Prestation\Traitement\AnnulerPrestationRequest;
use App\Http\Requests\Api\Ynov\Prestation\Traitement\TraiterPrestationRequest;
use App\Models\Api\Ynov\Prestation;
use App\Services\Api\Ynov\Prestation\PrestationTraitementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PrestationTraitementController extends Controller
{
    public function __construct(
        private PrestationTraitementService $traitementService
    ) {}

    /**
     * Traiter une prestation (accepter ou rejeter)
     */
    public function traiter(TraiterPrestationRequest $request, string $uuid_prestation): JsonResponse
    {
        try {
            $prestation = Prestation::where('uuid_prestation', $uuid_prestation)->firstOrFail();

            Log::info('PrestationTraitementController@traiter: Prestation trouvée', ['uuid_prestation' => $uuid_prestation, 'prestation' => $prestation]);

            $result = $this->traitementService->traiter(
                $prestation,
                $request->validated(),
                $request->user()->uuid_user
            );

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                    'code' => $result['code'],
                ], $result['status'] ?? 400);
            }

            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'code' => $result['code'],
                'data' => $result['data'] ?? null,
            ], $result['status'] ?? 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Prestation non trouvée.',
                'code' => 'PRESTATION_NOT_FOUND',
            ], 404);
        }
    }

    /**
     * Annuler une prestation (admin)
     */
    public function annuler(AnnulerPrestationRequest $request, string $uuid_prestation): JsonResponse
    {
        try {
            $prestation = Prestation::where('uuid_prestation', $uuid_prestation)->firstOrFail();

            $result = $this->traitementService->annuler(
                $prestation,
                $request->validated(),
                $request->user()->uuid_user
            );

            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'code' => $result['code'],
                'data' => $result['data'] ?? null,
            ], $result['status'] ?? 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Prestation non trouvée.',
                'code' => 'PRESTATION_NOT_FOUND',
            ], 404);
        }
    }

    /**
     * Historique des traitements d'une prestation
     */
    public function historique(Request $request, string $uuid_prestation): JsonResponse
    {
        try {
            $prestation = Prestation::where('uuid_prestation', $uuid_prestation)->firstOrFail();

            $historique = $this->traitementService->getHistorique($prestation);

            return response()->json([
                'success' => true,
                'message' => 'Historique des traitements récupéré avec succès.',
                'code' => 'HISTORIQUE_PRESTATION',
                'data' => $historique,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Prestation non trouvée.',
                'code' => 'PRESTATION_NOT_FOUND',
            ], 404);
        }
    }
}