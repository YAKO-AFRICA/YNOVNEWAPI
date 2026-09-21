<?php

namespace App\Http\Controllers\Api\Ynov\Esouscription;

use App\Http\Controllers\Controller;
use App\Models\Api\Ynov\parameter\ReseauProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class ParamController extends Controller
{
    // ================== SCRIPT CRUD POUR LES PRODUITS COMMERCIALISES DANS UN RESEAU ==================

    /**
     * Récupérer les produits par réseau avec des paramètres de filtre
     */
    public function getReseauProducts(Request $request)
    {
        $query = ReseauProduct::query();

        if ($request->has('reseau_uuid')) {
            $query->where('reseau_uuid', $request->input('reseau_uuid'));
        }

        if ($request->has('product_uuid')) {
            $query->where('product_uuid', $request->input('product_uuid'));
        }

        if ($request->has('formule_uuid')) {
            $query->where('formule_uuid', $request->input('formule_uuid'));
        }

        $productByReseau = $query->paginate(10);

        return response()->json([
            'success' => true,
            'message' => 'Liste des produits par réseau récupérée avec succès',
            'code' => 200,
            'total' => $productByReseau->total(),
            'data' => $productByReseau,
        ]);
    }

    /**
     * Ajouter un produit à un réseau
     */
    public function storeReseauProduct(Request $request)
    {
        $validatedData = $request->validate([
            'reseau_uuid' => 'required|string',
            'product_uuid' => 'required|string',
            'formule_uuid' => 'required|string',
        ]);

        DB::beginTransaction();

        try {

            $reseauProduct = ReseauProduct::create([
                'uuid' => Str::uuid(),
                'reseau_uuid' => $validatedData['reseau_uuid'],
                'product_uuid' => $validatedData['product_uuid'],
                'formule_uuid' => $validatedData['formule_uuid'],
                'etat' => 'actif',
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Produit ajouté au réseau avec succès',
                'code' => 201,
                'data' => $reseauProduct,
            ], 201);

        } catch (Throwable $e) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de l\'ajout du produit au réseau',
                'code' => 500,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mettre à jour un produit d'un réseau
     */
    public function updateReseauProduct(Request $request, $uuid)
    {
        $validatedData = $request->validate([

            'reseau_uuid' => 'sometimes|required|string',
            'product_uuid' => 'sometimes|required|string',
            'formule_uuid' => 'sometimes|required|string',
        ]);

        DB::beginTransaction();

        try {

            $reseauProduct = ReseauProduct::where('uuid',$uuid)->first();

            if (!$reseauProduct) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Produit du réseau introuvable',
                    'code' => 404,
                ], 404);
            }

            $reseauProduct->update([
                'reseau_uuid' => $validatedData['reseau_uuid'] ?? $reseauProduct->reseau_uuid,
                'product_uuid' => $validatedData['product_uuid'] ?? $reseauProduct->product_uuid,
                'formule_uuid' => $validatedData['formule_uuid'] ?? $reseauProduct->formule_uuid,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Produit du réseau mis à jour avec succès',
                'code' => 200,
                'data' => $reseauProduct->fresh(),
            ], 200);

        } catch (Throwable $e) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la mise à jour du produit',
                'code' => 500,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Supprimer un produit d'un réseau
     *
     * Par défaut :
     *      etat = inactif
     *
     * Avec deleting=full :
     *      suppression définitive de la base
     */
    public function deleteReseauProduct(Request $request, $uuid)
    {
        $validatedData = $request->validate([
            'deleting' => 'sometimes|string|in:full',
        ]);

        DB::beginTransaction();

        try {

            $reseauProduct = ReseauProduct::where(
                'uuid',
                $uuid
            )->first();

            if (!$reseauProduct) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Produit du réseau introuvable',
                    'code' => 404,
                ], 404);
            }

            /*
             * Suppression définitive
             *
             * deleting=full
             */
            if ($request->input('deleting') === 'full') {

                $reseauProduct->delete();

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Produit du réseau supprimé définitivement',
                    'code' => 200,
                    'data' => null,
                ], 200);
            }

            /*
             * Suppression logique
             *
             * Par défaut, sofdelete
             * le produit du réseau.
             */
            $reseauProduct->update([
                'etat' => 'inactif',
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Produit du réseau désactivé avec succès',
                'code' => 200,
                'data' => $reseauProduct->fresh(),
            ], 200);

        } catch (Throwable $e) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la suppression du produit',
                'code' => 500,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}