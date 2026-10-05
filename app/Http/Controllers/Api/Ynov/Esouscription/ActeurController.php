<?php

namespace App\Http\Controllers\Api\Ynov\Esouscription;

use App\Http\Controllers\Controller;
use App\Services\Api\Ynov\Esouscription\ActeurService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

use Illuminate\Support\Facades\Validator;
use Throwable;

class ActeurController extends Controller
{
    public function __construct(private ActeurService $acteurService)
    {

    }

    /**
     * Liste des acteurs
     */
    public function getActeurs(Request $request)
    {
        try {
            $acteurs = $this->acteurService->getActeurs(
                $request->only(['uuid_acteur', 'idClient', 'code', 'nom', 'prenoms', 'email', 'nni', 'etat']),
                (int) $request->input('per_page', 10)
            );

            return response()->json([
                'success' => true,
                'message' => 'Liste des acteurs récupérée avec succès',
                'code' => 200,
                'total' => $acteurs->total(),
                'data' => $acteurs,
            ], 200);

        } catch (Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des acteurs',
                'code' => 500,
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Afficher un acteur
     */
    public function showActeur($uuid)
    {
        try {

            $acteur = $this->acteurService->findByUuid($uuid);

            if (!$acteur) {
                return response()->json([
                    'success' => false,
                    'message' => 'Acteur introuvable',
                    'code' => 404,
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Acteur récupéré avec succès',
                'code' => 200,
                'data' => $acteur,
            ], 200);

        } catch (Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de l’acteur',
                'code' => 500,
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Créer un acteur
     */

    public function storeActeur(Request $request)
    {

        Log::info('validatedDataaaaaaaaaaaaaaaaaaaaaaaaaaaaa    avant ');

        $validatedData = $request->validate([

            'civilite' => 'nullable|string|max:25',
            'genre' => 'nullable|string|max:10',
            'nom' => 'nullable|string|max:100',
            'prenoms' => 'nullable|string|max:255',
            'date_naissance' => 'nullable|date',
            'lieunaissance_code' => 'nullable|string|max:100',

            'email' => 'nullable|email|max:260',
            'mobile' => 'nullable|string|max:20',
            'telephone' => 'nullable|string|max:20',

            'numero_piece' => 'nullable|string|max:100',
            'nni' => 'nullable|string|max:100',
            'nature_piece' => 'nullable|string|max:100',

            'situation_matrimoniale' => 'nullable|string|max:255',
            'profession_code' => 'nullable|string|max:100',
            'employeur' => 'nullable|string|max:100',

            'lieuresidence_code' => 'nullable|string|max:100',
            'pays_code' => 'nullable|string|max:50',
            'integration_key' => 'nullable|string|max:255',
            'created_by' => 'nullable|string|max:255',
        ]);

        Log::info('validatedDataaaaaaaaaaaaaaaaaaaaaaaaaaaaa');
        Log::info($validatedData);

        $validatedData['uuid_acteur'] = Str::uuid();

        try {
            $acteur = $this->acteurService->create($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Acteur créé avec succès',
                'code' => 201,
                'data' => $acteur,
            ], 201);

        } catch (Throwable $e) {
            Log::error("Error de creation de l'acteur: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de l’acteur',
                'code' => 500,
                'error' => $e->getMessage(),
            ], 500);
        }
    }





    /**
     * Modifier un acteur
     */
    public function updateActeur(Request $request, $uuid)
    {
        $acteur = $this->acteurService->findByUuid($uuid);

        if (!$acteur) {
            return response()->json([
                'success' => false,
                'message' => 'Acteur introuvable',
                'code' => 404,
            ], 404);
        }

        $validatedData = $request->validate([

            'idClient' => 'sometimes|nullable|string|max:50',

            'civilite' => 'sometimes|nullable|string|max:25',
            'genre' => 'sometimes|nullable|string|max:10',
            'nom' => 'sometimes|nullable|string|max:100',
            'prenoms' => 'sometimes|nullable|string|max:255',
            'date_naissance' => 'sometimes|nullable|date',
            'lieunaissance_code' => 'sometimes|nullable|string|max:100',

            'email' => 'sometimes|nullable|email|max:260',
            'mobile' => 'sometimes|nullable|string|max:20',
            'telephone' => 'sometimes|nullable|string|max:20',

            'numero_piece' => 'sometimes|nullable|string|max:100',
            'nni' => 'sometimes|nullable|string|max:100',
            'nature_piece' => 'sometimes|nullable|string|max:100',

            'situation_matrimoniale' => 'sometimes|nullable|string|max:255',
            'profession_code' => 'sometimes|nullable|string|max:100',
            'employeur' => 'sometimes|nullable|string|max:100',

            'lieuresidence_code' => 'sometimes|nullable|string|max:100',
            'pays_code' => 'sometimes|nullable|string|max:50',

            'integration_key' => 'sometimes|nullable|string|max:255',

            'updated_by' => 'sometimes|nullable|string|max:255',
        ]);

        Log::info('validatedDataaaaaaaaaaaaaaaaaaaaaaaaaaaaa');
        Log::info($validatedData);

        try {
            $acteur = $this->acteurService->update($acteur, $validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Acteur mis à jour avec succès',
                'code' => 200,
                'data' => $acteur->fresh(),
            ], 200);

        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de l’acteur',
                'code' => 500,
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Suppression logique ou définitive
     *
     * DELETE normal :
     *      → soft delete
     *
     * DELETE avec deleting=full :
     *      → suppression définitive
     */
    public function destroy(Request $request, $uuid)
    {
        $acteur = $this->acteurService->findWithTrashedByUuid($uuid);

        if (!$acteur) {
            return response()->json([
                'success' => false,
                'message' => 'Acteur introuvable',
                'code' => 404,
            ], 404);
        }

        try {
            $isForceDelete = $request->input('deleting') === 'full';
            $this->acteurService->delete(
                $acteur,
                $isForceDelete,
                $request->input('deleted_by')
            );

            if ($isForceDelete) {
                return response()->json([
                    'success' => true,
                    'message' => 'Acteur supprimé définitivement',
                    'code' => 200,
                    'data' => null,
                ], 200);
            }

            return response()->json([
                'success' => true,
                'message' => 'Acteur supprimé avec succès',
                'code' => 200,
                'data' => null,
            ], 200);

        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de l’acteur',
                'code' => 500,
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Restaurer un acteur supprimé
     */
    public function restoreActeur($uuid)
    {
        $acteur = $this->acteurService->findTrashedByUuid($uuid);

        if (!$acteur) {
            return response()->json([
                'success' => false,
                'message' => 'Acteur supprimé introuvable',
                'code' => 404,
            ], 404);
        }

        try {
            $acteur = $this->acteurService->restore($acteur);

            return response()->json([
                'success' => true,
                'message' => 'Acteur restauré avec succès',
                'code' => 200,
                'data' => $acteur->fresh(),
            ], 200);

        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la restauration de l’acteur',
                'code' => 500,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
