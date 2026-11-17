<?php

namespace App\Http\Controllers\Api\Ynov\Simulateur;

use App\Http\Controllers\Controller;
use App\Services\Api\Ynov\Simulateur\YakoEterniteSimulatorService;
use App\Services\Api\Ynov\Simulateur\YakoEterniteTarificationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class YakoEterniteSimulatorController extends Controller
{
    public function __construct(
        private readonly YakoEterniteSimulatorService $simulatorService
    ) {}

    /**
     * @OA\Post(
     *     path="/esouscription/simulateurs/yako-eternite",
     *     tags={"Simulateurs"},
     *     summary="Simuler une souscription Yako Eternité",
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"CodeProduit","CodePeriodicite","Capital","DateNaissance","DateEffet","IsAssure","AjouterGarantieSenior"},
     *
     *             @OA\Property(property="CodeProduit", type="string", enum={"yke_2008","yke_2018","yke_2026"}, example="yke_2026"),
     *             @OA\Property(property="CodePeriodicite", type="string", enum={"M","T","S","A"}, example="M"),
     *             @OA\Property(property="Capital", type="integer", enum={500000,750000,1000000,1250000,1500000,2000000,3000000,4000000,5000000}, example=500000),
     *             @OA\Property(property="DateNaissance", type="string", format="date", example="1990-04-15"),
     *             @OA\Property(property="DateEffet", type="string", format="date", example="2026-12-01"),
     *             @OA\Property(property="IsAssure", type="string", enum={"oui","non"}, example="oui"),
     *             @OA\Property(property="AjouterGarantieSenior", type="boolean", example=false),
     *             @OA\Property(property="PrimeSenior", type="integer", example=10000)
     *         )
     *     ),
     *
     *     @OA\Response(response=200, description="Simulation Yako Eternité calculée."),
     *     @OA\Response(response=422, description="Paramètres de simulation invalides."),
     *     @OA\Response(response=502, description="Service de tarification indisponible."),
     *     @OA\Response(response=503, description="Configuration du simulateur indisponible.")
     * )
     */
    public function simulate(Request $request): JsonResponse
    {
        Log::info("debut de simulateion Simule ");
        $validator = Validator::make($request->all(), [
            'CodeProduit' => ['required', 'string', Rule::in(YakoEterniteSimulatorService::CODE_PRODUITS)],
            'CodePeriodicite' => ['required', 'string', Rule::in(YakoEterniteSimulatorService::PERIODICITES)],
            'Capital' => ['required', 'integer', Rule::in(YakoEterniteSimulatorService::CAPITAUX)],
            'DateNaissance' => ['required', 'date_format:Y-m-d', 'before:today'],
            'DateEffet' => ['required', 'date_format:Y-m-d'],
            'IsAssure' => ['required', 'string', Rule::in(['oui', 'non'])],
            'AjouterGarantieSenior' => ['required', 'boolean'],
            'PrimeSenior' => [
                Rule::requiredIf(fn () => filter_var(
                    $request->input('AjouterGarantieSenior'),
                    FILTER_VALIDATE_BOOLEAN
                )),
                'nullable',
                'integer',
                'gt:0',
            ],
        ]);

        Log::info("debut de simulateion validation ==== {
            'CodeProduit' => $request->input('CodeProduit'),
            'CodePeriodicite' => $request->input('CodePeriodicite'),
            'Capital' => $request->input('Capital'),
            'DateNaissance' => $request->input('DateNaissance'),
            'DateEffet' => $request->input('DateEffet'),
            'IsAssure' => $request->input('IsAssure'),
            'AjouterGarantieSenior' => $request->input('AjouterGarantieSenior'),
            'PrimeSenior' => $request->input('PrimeSenior'),
        }");

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Les paramètres de simulation Yako Eternité sont invalides.',
                'code' => 'YAKO_ETERNITE_SIMULATION_INVALID',
                'errors' => $validator->errors(),
            ], 422);
        }

        Log::info("after validation ==== ");

        try {
            $simulation = $this->simulatorService->simulate($validator->validated());
        } catch (YakoEterniteTarificationException $exception) {
            Log::error('Échec du simulateur Yako Eternité.', [
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Le simulateur Yako Eternité ne peut pas être exécuté actuellement.',
                'code' => 'YAKO_ETERNITE_SIMULATION_UNAVAILABLE',
            ], $exception->httpStatus);
        }

        return response()->json([
            'success' => true,
            'message' => 'Simulation Yako Eternité calculée avec succès.',
            'code' => 'YAKO_ETERNITE_SIMULATION_SUCCESS',
            'data' => $simulation,
        ]);
    }
}
