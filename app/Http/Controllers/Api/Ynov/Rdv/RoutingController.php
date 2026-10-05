<?php

namespace App\Http\Controllers\Api\Ynov\Rdv;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Ynov\Rdv\Traitement\ReassignerGestionnaireRequest;
use App\Http\Requests\Api\Ynov\Rdv\Traitement\ReassignerMultipleGestionnaireRequest;
use App\Models\Api\Ynov\Rdv;
use App\Services\Api\Ynov\Rdv\RoutingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoutingController extends Controller
{
    public function __construct(
        private RoutingService $routingService
    ) {}

    /**
     * Assignation automatique de tous les RDV en attente (Endpoint public)
     * À appeler 3 minutes après la création d'un RDV
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

        $result = $this->routingService->assignerTousLesRdvsEnAttente();

        return response()->json([
            'success' => true,
            'message' => 'Assignation automatique terminée.',
            'code' => 'AUTO_ASSIGN_DONE',
            'data' => $result,
        ]);
    }

    /**
     * Gestion des RDV expirés (Endpoint public)
     * À appeler tous les jours à minuit (ou via un cron)
     */
    public function gererExpires(Request $request): JsonResponse
    {
        $token = $request->get('token');
        $expectedToken = config('services.auto_assign.token', 'auto_assign_secret_token');
        
        if ($token !== $expectedToken) {
            return response()->json([
                'success' => false,
                'message' => 'Token invalide.',
                'code' => 'INVALID_TOKEN',
            ], 401);
        }

        $result = $this->routingService->gererRdvsExpires();

        return response()->json([
            'success' => true,
            'message' => 'Gestion des RDV expirés terminée.',
            'code' => 'EXPIRATION_HANDLED',
            'data' => $result,
        ]);
    }

    /**
     * Rééquilibrer la charge des gestionnaires
     */
    public function reequilibrer(Request $request): JsonResponse
    {
        $request->validate([
            'agence_uuid' => ['required', 'exists:agences,uuid_agence'],
            'date_rdv' => ['required', 'date'],
        ]);

        $result = $this->routingService->reequilibrerCharge(
            $request->agence_uuid,
            $request->date_rdv
        );

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'] ?? 'Rééquilibrage terminé.',
            'code' => 'REBALANCE_DONE',
            'data' => $result,
        ]);
    }

    /**
     * Réassigner un RDV à un autre gestionnaire (manuel)
     */
    public function reassigner(ReassignerGestionnaireRequest $request, string $uuid_rdvs): JsonResponse
    {
        $rdv = Rdv::where('uuid_rdvs', $uuid_rdvs)->firstOrFail();

        $result = $this->routingService->reassignerManuellement(
            $rdv,
            $request->validated(),
            $request->user()->uuid_user
        );

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'code' => $result['code'],
            'data' => $result['data'] ?? null,
        ], $result['status'] ?? 200);
    }

    /**
     * Réassigner manuellement plusieurs RDV à un autre gestionnaire
     * avec une seule notification groupée
     */
    public function reassignerMultiple(ReassignerMultipleGestionnaireRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $result = $this->routingService->reassignerManuellementMultiple(
                $validated['rdv_uuids'],
                $validated['gestionnaire_uuid'],
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
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la réassignation multiple.',
                'error' => $e->getMessage(),
                'code' => 'REASSIGN_ERROR',
            ], 500);
        }
    }
}
