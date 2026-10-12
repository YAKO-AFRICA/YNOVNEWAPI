<?php

namespace App\Http\Controllers\Api\Ynov\Simulateur;

use App\Http\Controllers\Controller;
use App\Services\Api\Ynov\Simulateur\DoihooSimulatorService;
use App\Services\Api\Ynov\Simulateur\DoihooTarificationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class DoihooSimulatorController extends Controller
{
    public function __construct(
        private readonly DoihooSimulatorService $simulatorService
    ) {}

    /**
     * @OA\Post(
     *     path="/esouscription/simulateurs/doihoo",
     *     tags={"Simulateurs"},
     *     summary="Simuler une souscription Doihoo",
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"CodeProduit","CodePeriodicite","Capital","AgeAssure","Duree"},
     *
     *             @OA\Property(property="CodeProduit", type="string", example="DOIHOO_2020_IND"),
     *             @OA\Property(property="CodePeriodicite", type="string", example="MENSUELLE"),
     *             @OA\Property(property="Capital", type="number", example=1000000),
     *             @OA\Property(property="AgeAssure", type="integer", minimum=18, maximum=99, example=35),
     *             @OA\Property(property="Duree", type="integer", enum={8}, example=8)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Simulation calculée, primes par garantie, frais d’adhésion et total à payer.",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="code", type="string", example="DOIHOO_SIMULATION_SUCCESS"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *
     *     @OA\Response(response=422, description="Paramètres de simulation invalides."),
     *     @OA\Response(response=502, description="Service de tarification indisponible.")
     * )
     */
    public function simulate(Request $request): JsonResponse
    {

        Log::info('Requête de simulation Doihoo reçue.', [
            'request_data' => $request->all(),
        ]);
        $validator = Validator::make($request->all(), [
            'CodeProduit' => ['required', 'string', Rule::in([DoihooSimulatorService::CODE_PRODUIT])],
            'CodePeriodicite' => ['required', 'string', 'max:30'],
            'Capital' => ['required', 'numeric', 'gt:0'],
            'AgeAssure' => ['required', 'integer', 'between:18,99'],
            'Duree' => ['required', 'integer', Rule::in([DoihooSimulatorService::DUREE_CONTRAT])],
        ]);

        Log::info('Paramètres de simulation validés.', [
            'validated_data' => $validator->validated(),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'code'    => 'DOIHOO_SIMULATION_VALIDATION_ERROR',
                'message' => 'Les paramètres de simulation sont invalides.',
            ], 400);
        }

        $validated = $validator->validated();

        

        try {
            $simulation = $this->simulatorService->simulate($validated);

            return response()->json([
                'success' => true,
                'message' => 'Simulation Doihoo calculée avec succès.',
                'code' => 'DOIHOO_SIMULATION_SUCCESS',
                'data' => $simulation,
            ]);
        } catch (DoihooTarificationException $exception) {
            Log::error('Échec du simulateur Doihoo auprès du service de tarification.', [
                'message' => $exception->getMessage(),
                'exception' => $exception,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Le service de tarification est indisponible. Veuillez réessayer.',
                'code' => 'DOIHOO_TARIFF_UNAVAILABLE',
            ], $exception->httpStatus);
        }

        
    }
}
