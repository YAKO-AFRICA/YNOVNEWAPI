<?php

namespace App\Http\Controllers\Api\Ynov\Esouscription;

use App\Http\Controllers\Controller;
use App\Services\Api\Ynov\Esouscription\DeclarationSanteService;
use Illuminate\Http\Request;
use Throwable;

class SanteController extends Controller
{
    public function __construct(private DeclarationSanteService $declarationSanteService)
    {
    }

    public function getSanteData(Request $request)
    {
        try {
            $donneesSante = $this->declarationSanteService->getAll(
                $request->only(['contrat_uuid', 'assure_uuid', 'etat']),
                (int) $request->input('per_page', 15)
            );

            return response()->json([
                'success' => true,
                'message' => 'Données de santé récupérées avec succès.',
                'data' => $donneesSante,
            ]);
        } catch (Throwable $e) {
            return $this->errorResponse('Erreur lors de la récupération des données de santé.', $e);
        }
    }

    public function storeSante(Request $request)
    {
        $validated = $request->validate($this->rules());

        try {
            $donneesSante = $this->declarationSanteService->create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Données de santé enregistrées avec succès.',
                'data' => $donneesSante,
            ], 201);
        } catch (Throwable $e) {
            return $this->errorResponse('Erreur lors de l’enregistrement des données de santé.', $e);
        }
    }

    public function showSante(string $uuid)
    {
        try {
            $donneesSante = $this->declarationSanteService->findByUuid($uuid);

            if (!$donneesSante) {
                return $this->notFoundResponse();
            }

            return response()->json([
                'success' => true,
                'message' => 'Données de santé récupérées avec succès.',
                'data' => $donneesSante,
            ]);
        } catch (Throwable $e) {
            return $this->errorResponse('Erreur lors de la récupération des données de santé.', $e);
        }
    }

    public function updateSante(Request $request, string $uuid)
    {
        $donneesSante = $this->declarationSanteService->findByUuid($uuid);

        if (!$donneesSante) {
            return $this->notFoundResponse();
        }

        $validated = $request->validate($this->rules(true));

        try {
            $donneesSante = $this->declarationSanteService->update($donneesSante, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Données de santé mises à jour avec succès.',
                'data' => $donneesSante,
            ]);
        } catch (Throwable $e) {
            return $this->errorResponse('Erreur lors de la modification des données de santé.', $e);
        }
    }

    public function destroySante(Request $request, string $uuid)
    {
        $donneesSante = $this->declarationSanteService->findWithTrashedByUuid($uuid);

        if (!$donneesSante) {
            return $this->notFoundResponse();
        }

        try {
            $force = $request->input('deleting') === 'full';
            $this->declarationSanteService->delete(
                $donneesSante,
                $force,
                $request->input('deleted_by')
            );

            return response()->json([
                'success' => true,
                'message' => $force
                    ? 'Données de santé supprimées définitivement.'
                    : 'Données de santé supprimées avec succès.',
            ]);
        } catch (Throwable $e) {
            return $this->errorResponse('Erreur lors de la suppression des données de santé.', $e);
        }
    }

    public function restoreSante(string $uuid)
    {
        $donneesSante = $this->declarationSanteService->findTrashedByUuid($uuid);

        if (!$donneesSante) {
            return $this->notFoundResponse();
        }

        try {
            $donneesSante = $this->declarationSanteService->restore($donneesSante);

            return response()->json([
                'success' => true,
                'message' => 'Données de santé restaurées avec succès.',
                'data' => $donneesSante,
            ]);
        } catch (Throwable $e) {
            return $this->errorResponse('Erreur lors de la restauration des données de santé.', $e);
        }
    }

    public function getTrashedSante()
    {
        try {
            $donneesSante = $this->declarationSanteService->getTrashed();

            return response()->json([
                'success' => true,
                'message' => 'Données de santé supprimées récupérées avec succès.',
                'data' => $donneesSante,
            ]);
        } catch (Throwable $e) {
            return $this->errorResponse('Erreur lors de la récupération des données supprimées.', $e);
        }
    }

    private function rules(bool $partial = false): array
    {
        $required = $partial ? 'sometimes|nullable' : 'required';

        return [
            'contrat_uuid' => $required . '|string|max:255',
            'assure_uuid' => $required . '|string|max:255',
            'taille' => 'sometimes|nullable|numeric|min:0',
            'poids' => 'sometimes|nullable|numeric|min:0',
            'tension_min' => 'sometimes|nullable|numeric|min:0',
            'tension_max' => 'sometimes|nullable|numeric|min:0',
            'tabagisme' => 'sometimes|nullable|string|max:255',
            'alcool' => 'sometimes|nullable|string|max:255',
            'sport' => 'sometimes|nullable|string|max:255',
            'accident' => 'sometimes|nullable|string|max:255',
            'traitement' => 'sometimes|nullable|string|max:255',
            'transfusion_sanguine' => 'sometimes|nullable|string|max:255',
            'intervention_chirurgicale' => 'sometimes|nullable|string|max:255',
            'prochaine_intervention_chirurgicale' => 'sometimes|nullable|string|max:255',
            'diabete' => 'sometimes|nullable|string|max:255',
            'hypertension' => 'sometimes|nullable|string|max:255',
            'drepanocytose' => 'sometimes|nullable|string|max:255',
            'cirrhose_foie' => 'sometimes|nullable|string|max:255',
            'maladie_pulmonaire' => 'sometimes|nullable|string|max:255',
            'cancer' => 'sometimes|nullable|string|max:255',
            'anemie' => 'sometimes|nullable|string|max:255',
            'insuffisance_renale' => 'sometimes|nullable|string|max:255',
            'avc' => 'sometimes|nullable|string|max:255',
            'created_by' => 'sometimes|nullable|string|max:255',
            'update_by' => 'sometimes|nullable|string|max:255',
        ];
    }

    private function notFoundResponse()
    {
        return response()->json([
            'success' => false,
            'message' => 'Données de santé introuvables.',
        ], 404);
    }

    private function errorResponse(string $message, Throwable $exception)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error' => $exception->getMessage(),
        ], 500);
    }
}