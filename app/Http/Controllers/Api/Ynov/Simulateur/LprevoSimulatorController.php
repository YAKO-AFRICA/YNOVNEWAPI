<?php

namespace App\Http\Controllers\Api\Ynov\Simulateur;

use App\Http\Controllers\Controller;
use App\Services\Api\Ynov\Simulateur\LprevoSimulatorException;
use App\Services\Api\Ynov\Simulateur\LprevoSimulatorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class LprevoSimulatorController extends Controller
{
    public function __construct(
        private readonly LprevoSimulatorService $simulatorService
    ) {}

    /**
     * @OA\Post(
     *     path="/simulateurs/lprevo",
     *     tags={"Simulateurs"},
     *     summary="Simuler une souscription LPREVO",
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"CodeProduit","Capital","BonneSante","Pathologies"},
     *
     *             @OA\Property(property="CodeProduit", type="string", enum={"LPREVO"}, example="LPREVO"),
     *             @OA\Property(property="Capital", type="integer", enum={100000,250000,500000}, example=100000),
     *             @OA\Property(property="BonneSante", type="boolean", example=false),
     *             @OA\Property(
     *                 property="Pathologies",
     *                 type="array",
     *
     *                 @OA\Items(type="string", enum={"Diabète","AVC","Cancer","Insuffisance Rénale","Hypertension"}),
     *                 example={"Diabète","Hypertension"}
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=200, description="Simulation LPREVO calculée."),
     *     @OA\Response(response=422, description="Paramètres de simulation invalides."),
     *     @OA\Response(response=503, description="Garantie LPREVO non configurée.")
     * )
     */
    public function simulate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'CodeProduit' => ['required', 'string', Rule::in([LprevoSimulatorService::CODE_PRODUIT])],
            'Capital' => ['required', 'integer', Rule::in([100000, 250000, 500000])],
            'BonneSante' => ['required', 'boolean'],
            'Pathologies' => ['required', 'array'],
            'Pathologies.*' => ['required', 'string', 'distinct', Rule::in(LprevoSimulatorService::PATHOLOGIES)],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Les paramètres de simulation LPREVO sont invalides.',
                'code' => 'LPREVO_SIMULATION_INVALID',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();
        if ($validated['BonneSante'] && $validated['Pathologies'] !== []) {
            return response()->json([
                'success' => false,
                'message' => 'Aucune pathologie ne peut être déclarée lorsque l’assuré se déclare en bonne santé.',
                'code' => 'LPREVO_HEALTH_DECLARATION_CONFLICT',
                'errors' => [
                    'Pathologies' => [
                        'La liste doit être vide lorsque BonneSante vaut true.',
                    ],
                ],
            ], 422);
        }

        try {
            $simulation = $this->simulatorService->simulate($validated);
        } catch (LprevoSimulatorException $exception) {
            Log::error('Échec du simulateur LPREVO.', [
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Le simulateur LPREVO ne peut pas être exécuté actuellement.',
                'code' => 'LPREVO_SIMULATION_UNAVAILABLE',
            ], $exception->httpStatus);
        }

        return response()->json([
            'success' => true,
            'message' => 'Simulation LPREVO calculée avec succès.',
            'code' => 'LPREVO_SIMULATION_SUCCESS',
            'data' => $simulation,
        ]);
    }
}
