<?php

namespace App\Http\Controllers\Api\Ynov\Esouscription;

use App\Http\Controllers\Controller;
use App\Services\Api\Ynov\Esouscription\ContratActeurService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Throwable;

class ContratActeurController extends Controller
{
    public function __construct(private ContratActeurService $contratActeurService)
    {
    }

    public function index(Request $request)
    {
        try {
            $contratsActeurs = $this->contratActeurService->getAll(
                $request->only([
                    'contrat_uuid',
                    'acteur_uuid',
                    'type_acteur',
                    'type_beneficiaire',
                    'etat',
                ]),
                (int) $request->input('per_page', 15)
            );

            return response()->json([
                'success' => true,
                'message' => 'Relations contrat-acteur récupérées avec succès',
                'code' => 200,
                'total' => $contratsActeurs->total(),
                'data' => $contratsActeurs,
            ]);
        } catch (Throwable $e) {
            return $this->errorResponse('Erreur lors de la récupération des relations contrat-acteur', $e);
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules());

        try {
            $contratActeur = $this->contratActeurService->create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Relation contrat-acteur créée avec succès',
                'code' => 201,
                'data' => $contratActeur,
            ], 201);
        } catch (Throwable $e) {
            return $this->errorResponse('Erreur lors de la création de la relation contrat-acteur', $e);
        }
    }

    public function show(string $uuid)
    {
        try {
            $contratActeur = $this->contratActeurService->findByUuid($uuid);

            if (!$contratActeur) {
                return $this->notFoundResponse();
            }

            return response()->json([
                'success' => true,
                'message' => 'Relation contrat-acteur récupérée avec succès',
                'code' => 200,
                'data' => $contratActeur,
            ]);
        } catch (Throwable $e) {
            return $this->errorResponse('Erreur lors de la récupération de la relation contrat-acteur', $e);
        }
    }

    public function update(Request $request, string $uuid)
    {
        $contratActeur = $this->contratActeurService->findByUuid($uuid);

        if (!$contratActeur) {
            return $this->notFoundResponse();
        }

        $validated = $request->validate($this->rules(true));

        try {
            $contratActeur = $this->contratActeurService->update($contratActeur, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Relation contrat-acteur mise à jour avec succès',
                'code' => 200,
                'data' => $contratActeur,
            ]);
        } catch (Throwable $e) {
            return $this->errorResponse('Erreur lors de la mise à jour de la relation contrat-acteur', $e);
        }
    }

    public function destroy(Request $request, string $uuid)
    {
        $contratActeur = $this->contratActeurService->findWithTrashedByUuid($uuid);

        if (!$contratActeur) {
            return $this->notFoundResponse();
        }

        try {
            $force = $request->input('deleting') === 'full';
            $this->contratActeurService->delete($contratActeur, $force, $request->input('deleted_by'));

            return response()->json([
                'success' => true,
                'message' => $force
                    ? 'Relation contrat-acteur supprimée définitivement'
                    : 'Relation contrat-acteur supprimée avec succès',
                'code' => 200,
                'data' => null,
            ]);
        } catch (Throwable $e) {
            return $this->errorResponse('Erreur lors de la suppression de la relation contrat-acteur', $e);
        }
    }

    public function restore(string $uuid)
    {
        $contratActeur = $this->contratActeurService->findTrashedByUuid($uuid);

        if (!$contratActeur) {
            return $this->notFoundResponse('Relation contrat-acteur supprimée introuvable');
        }

        try {
            $contratActeur = $this->contratActeurService->restore($contratActeur);

            return response()->json([
                'success' => true,
                'message' => 'Relation contrat-acteur restaurée avec succès',
                'code' => 200,
                'data' => $contratActeur,
            ]);
        } catch (Throwable $e) {
            return $this->errorResponse('Erreur lors de la restauration de la relation contrat-acteur', $e);
        }
    }

    private function rules(bool $partial = false): array
    {
        $required = $partial ? 'sometimes|nullable' : 'required';

        return [
            'uuid_contrat_acteur' => Str::uuid(),
            'contrat_uuid' => $required . '|string|max:255',
            'acteur_uuid' => $required . '|string|max:255',
            'type_acteur' => 'sometimes|nullable|string|max:100',
            'type_beneficiaire' => 'sometimes|nullable|string|max:100',
            'integration_key' => 'sometimes|nullable|string|max:255',
            'created_by' => 'sometimes|nullable|string|max:255',
            'updated_by' => 'sometimes|nullable|string|max:255',
        ];
    }

    private function notFoundResponse(string $message = 'Relation contrat-acteur introuvable')
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'code' => 404,
        ], 404);
    }

    private function errorResponse(string $message, Throwable $exception)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'code' => 500,
            'error' => $exception->getMessage(),
        ], 500);
    }
}