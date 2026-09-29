<?php

namespace App\Http\Controllers\Api\Ynov\Prestation;

use App\Http\Controllers\Controller;
use App\Models\Api\Ynov\Prestation;
use App\Services\Api\Ynov\Prestation\PrestationRoutingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PrestationRoutingController extends Controller
{
    public function __construct(
        private PrestationRoutingService $prestationRoutingService
    ) {}

    /**
     * Assignation automatique de toutes les prestations en attente (Endpoint public)
     * À appeler quelques minutes après la création d'une prestation
     */
    public function autoAssign(Request $request): JsonResponse
    {
        // Vérification d'un token pour sécuriser l'appel
        $token = $request->get('token');
        $expectedToken = config('services.auto_assign.token', 'auto_assign_secret_token');
        
        if ($token !== $expectedToken) {
            return response()->json([
                'success' => false,
                'message' => 'Token invalide.',
                'code' => 'INVALID_TOKEN',
            ], 401);
        }

        $result = $this->prestationRoutingService->assignerToutesLesPrestationsEnAttente();

        return response()->json([
            'success' => true,
            'message' => 'Assignation automatique terminée.',
            'code' => 'AUTO_ASSIGN_DONE',
            'data' => $result,
        ]);
    }

    /**
     * Réassigner manuellement une prestation à un autre gestionnaire
     */
    public function reassign(Request $request, string $uuid_prestation): JsonResponse
    {
        try {
            $validated = $request->validate([
                'gestionnaire_uuid' => ['required', 'string', 'exists:users,uuid_user'],
                'observation' => ['nullable', 'string'],
                'status' => ['nullable', 'string', 'in:en_attente,transmis,accepte,rejete,annule'],
            ]);

            $prestation = Prestation::where('uuid_prestation', $uuid_prestation)->firstOrFail();
            
            $result = $this->prestationRoutingService->reassignerManuellement(
                $prestation,
                $validated,
                $request->user()->uuid_user
            );

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                    'code' => $result['code'] ?? 'REASSIGN_ERROR',
                ], $result['status'] ?? 422);
            }

            return response()->json($result);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Prestation non trouvée.',
                'code' => 'PRESTATION_NOT_FOUND',
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation.',
                'errors' => $e->errors(),
                'code' => 'VALIDATION_ERROR',
            ], 422);
        }
    }

    /**
     * Assigner automatiquement une prestation spécifique
     */
    public function assignSingle(Request $request, string $uuid_prestation): JsonResponse
    {
        try {
            $prestation = Prestation::where('uuid_prestation', $uuid_prestation)->firstOrFail();
            
            $result = $this->prestationRoutingService->assignerAutomatiquement($prestation);

            return response()->json($result);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Prestation non trouvée.',
                'code' => 'PRESTATION_NOT_FOUND',
            ], 404);
        }
    }
}