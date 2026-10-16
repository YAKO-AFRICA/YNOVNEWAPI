<?php

namespace App\Http\Controllers\Api\Ynov\Esouscription;

use App\Http\Controllers\Controller;
use App\Services\Api\Ynov\ESouscription\ContratService;
use Illuminate\Http\Request;
use Throwable;

class ValidationController extends Controller
{
    public function __construct(private ContratService $contratService)
    {
    }

    public function acceptContrat(Request $request, string $uuid)
    {
        $contrat = $this->contratService->findByUuid($uuid);

        if (!$contrat) {
            return response()->json([
                'success' => false,
                'message' => 'Contrat introuvable',
                'code' => 404,
            ], 404);
        }

        try {
            $contrat = $this->contratService->accept(
                $contrat,
                (string) $request->user()->uuid_user
            );

            return response()->json([
                'success' => true,
                'message' => 'Contrat accepté avec succès',
                'code' => 200,
                'data' => $contrat,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l’acceptation du contrat',
                'code' => 500,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function rejectContrat(Request $request, string $uuid)
    {
        $contrat = $this->contratService->findByUuid($uuid);

        if (!$contrat) {
            return response()->json([
                'success' => false,
                'message' => 'Contrat introuvable',
                'code' => 404,
            ], 404);
        }

        $validated = $request->validate([
            'motif_rejet' => ['required', 'array', 'min:1'],
            'motif_rejet.*' => ['required', 'string', 'distinct'],
        ]);

        try {
            $contrat = $this->contratService->reject(
                $contrat,
                $validated['motif_rejet'],
                (string) $request->user()->uuid_user
            );

            return response()->json([
                'success' => true,
                'message' => 'Contrat rejeté avec succès',
                'code' => 200,
                'data' => $contrat,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du rejet du contrat',
                'code' => 500,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
