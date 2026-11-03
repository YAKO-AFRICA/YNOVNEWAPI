<?php

namespace App\Http\Controllers\Api\Ynov\Simulateur;

use App\Http\Controllers\Controller;
use App\Services\Api\Ynov\Simulateur\YapiSimulatorException;
use App\Services\Api\Ynov\Simulateur\YapiSimulatorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class YapiSimulatorController extends Controller
{
    public function __construct(
        private readonly YapiSimulatorService $simulatorService
    ) {}

    /**
     * @OA\Post(
     *     path="/esouscription/simulateurs/yapi",
     *     tags={"Simulateurs"},
     *     summary="Simuler une souscription Yapi",
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"CodeProduit","Capital"},
     *
     *             @OA\Property(property="CodeProduit", type="string", enum={"YAPI"}, example="YAPI"),
     *             @OA\Property(property="Capital", type="integer", enum={500000,1000000}, example=500000)
     *         )
     *     ),
     *
     *     @OA\Response(response=200, description="Simulation Yapi calculée."),
     *     @OA\Response(response=422, description="Paramètres de simulation invalides."),
     *     @OA\Response(response=503, description="Garantie Yapi non configurée.")
     * )
     */
    public function simulate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'CodeProduit' => ['required', 'string', Rule::in([YapiSimulatorService::CODE_PRODUIT])],
            'Capital' => ['required', 'integer', Rule::in([500000, 1000000])],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Les paramètres de simulation Yapi sont invalides.',
                'code' => 'YAPI_SIMULATION_INVALID',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $simulation = $this->simulatorService->simulate($validator->validated());
        } catch (YapiSimulatorException $exception) {
            Log::error('Échec du simulateur Yapi.', [
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Le simulateur Yapi ne peut pas être exécuté actuellement.',
                'code' => 'YAPI_SIMULATION_UNAVAILABLE',
            ], $exception->httpStatus);
        }

        return response()->json([
            'success' => true,
            'message' => 'Simulation Yapi calculée avec succès.',
            'code' => 'YAPI_SIMULATION_SUCCESS',
            'data' => $simulation,
        ]);
    }
}
