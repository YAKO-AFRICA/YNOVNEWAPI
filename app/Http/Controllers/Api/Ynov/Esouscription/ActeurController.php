<?php

namespace App\Http\Controllers\Api\Ynov\Esouscription;

use App\Http\Controllers\Controller;
use App\Models\Api\Ynov\Esouscription\Acteur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class ActeurController extends Controller
{
    /**
     * Liste des acteurs
     */
    public function getActeurs(Request $request)
    {
        try {
            $query = Acteur::query();

            // Filtres
            if ($request->filled('uuid_acteur')) {
                $query->where('uuid_acteur', $request->uuid_acteur);
            }

            if ($request->filled('idClient')) {
                $query->where('idClient', $request->idClient);
            }

            if ($request->filled('code')) {
                $query->where('code', $request->code);
            }

            if ($request->filled('nom')) {
                $query->where('nom', 'like', '%' . $request->nom . '%');
            }

            if ($request->filled('prenoms')) {
                $query->where('prenoms', 'like', '%' . $request->prenoms . '%');
            }

            if ($request->filled('email')) {
                $query->where('email', $request->email);
            }

            if ($request->filled('nni')) {
                $query->where('nni', $request->nni);
            }

            if ($request->filled('etat')) {
                if ($request->etat === 'actif') {
                    $query->whereNull('deleted_at');
                }

                if ($request->etat === 'supprime') {
                    $query->onlyTrashed();
                }
            }

            $acteurs = $query
                ->orderBy('created_at', 'desc')
                ->paginate($request->input('per_page', 10));

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

            $acteur = Acteur::where('uuid_acteur', $uuid)->first();

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

        Log::info('storeActeur request: ' . json_encode($request->all()));
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
        ]);

        Log::info('storeActeur request vbalidation: ' . json_encode($validatedData));

        DB::beginTransaction();

        try {

            $code = Refgenerate(Acteur::class, 'AC', 'code');

            Log::info('debut insertion code : ' . $code);

            $acteur = Acteur::create([
                'uuid_acteur' => Str::uuid(),
                'code' => $code,
                
                'civilite' => $validatedData['civilite'] ?? null,
                'genre' => $validatedData['genre'] ?? null,
                'nom' => $validatedData['nom'] ?? null,
                'prenoms' => $validatedData['prenoms'] ?? null,
                'date_naissance' => $validatedData['date_naissance'] ?? null,
                'lieunaissance_code' => $validatedData['lieunaissance_code'] ?? null,
                'email' => $validatedData['email'] ?? null,
                'mobile' => $validatedData['mobile'] ?? null,
                'telephone' => $validatedData['telephone'] ?? null,
                'numero_piece' => $validatedData['numero_piece'] ?? null,
                'nni' => $validatedData['nni'] ?? null,
                'nature_piece' => $validatedData['nature_piece'] ?? null,
                'situation_matrimoniale' => $validatedData['situation_matrimoniale'] ?? null,
                'profession_code' => $validatedData['profession_code'] ?? null,
                'employeur' => $validatedData['employeur'] ?? null,
                'lieuresidence_code' => $validatedData['lieuresidence_code'] ?? null,
                'pays_code' => $validatedData['pays_code'] ?? null,
                'integration_key' => $validatedData['integration_key'] ?? null,
                'created_by' => $validatedData['created_by'] ?? null,
            ]);

            Log::info('fin insertion code : ');

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Acteur créé avec succès',
                'code' => 201,
                'data' => $acteur,
            ], 201);

        } catch (Throwable $e) {
            Log::error('Erreur lors de la création de l’acteur: ' . $e->getMessage());

            DB::rollBack();

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
    public function update(Request $request, $uuid)
    {
        $acteur = Acteur::where('uuid_acteur', $uuid)->first();

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

            'updated_by' => 'sometimes|nullable|uuid',
        ]);

        DB::beginTransaction();

        try {

            $acteur->update($validatedData);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Acteur mis à jour avec succès',
                'code' => 200,
                'data' => $acteur->fresh(),
            ], 200);

        } catch (Throwable $e) {

            DB::rollBack();

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
        $acteur = Acteur::withTrashed()
            ->where('uuid_acteur', $uuid)
            ->first();

        if (!$acteur) {
            return response()->json([
                'success' => false,
                'message' => 'Acteur introuvable',
                'code' => 404,
            ], 404);
        }

        DB::beginTransaction();

        try {

            /*
             * Suppression définitive
             */
            if ($request->input('deleting') === 'full') {

                $acteur->forceDelete();

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Acteur supprimé définitivement',
                    'code' => 200,
                    'data' => null,
                ], 200);
            }

            /*
             * Suppression logique
             */
            $acteur->update([
                'deleted_by' => $request->input('deleted_by'),
            ]);

            $acteur->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Acteur supprimé avec succès',
                'code' => 200,
                'data' => null,
            ], 200);

        } catch (Throwable $e) {

            DB::rollBack();

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
        $acteur = Acteur::onlyTrashed()
            ->where('uuid_acteur', $uuid)
            ->first();

        if (!$acteur) {
            return response()->json([
                'success' => false,
                'message' => 'Acteur supprimé introuvable',
                'code' => 404,
            ], 404);
        }

        DB::beginTransaction();

        try {

            $acteur->restore();

            $acteur->update([
                'deleted_by' => null,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Acteur restauré avec succès',
                'code' => 200,
                'data' => $acteur->fresh(),
            ], 200);

        } catch (Throwable $e) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la restauration de l’acteur',
                'code' => 500,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
