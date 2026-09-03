<?php
// app/Http/Controllers/Api/Ynov/TypeProduitController.php

namespace App\Http\Controllers\Api\Ynov;

use App\Http\Controllers\Controller;
use App\Models\Api\Ynov\parameter\TypeProduit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TypeProduitController extends Controller
{
    /**
     * Liste des types de produits
     */
    public function index(Request $request): JsonResponse
    {
        $query = TypeProduit::query();

        if ($request->has('search')) {
            $query->search($request->search);
        }

        $perPage = $request->integer('per_page', 20);
        $types = $query->orderBy('libelle')->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Liste des types de produits récupérée.',
            'code' => 'TYPE_PRODUITS_LISTED',
            'data' => $types,
        ]);
    }

    /**
     * Créer un type de produit
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['nullable', 'string', 'max:45', 'unique:type_produits,code'],
            'libelle' => ['required', 'string', 'max:90'],
        ]);

        

        if (TypeProduit::where('code', $validated['code'])->exists()) {
            throw ValidationException::withMessages([
                'code' => ['Ce code est déjà utilisé.']
            ]);
        }

        $type = TypeProduit::create([
            'uuid_type_produit' => (string) Str::uuid(),
            'code' => $validated['code'],
            'libelle' => $validated['libelle'],
            'created_by' => $request->user()->uuid_user,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Type de produit créé avec succès.',
            'code' => 'TYPE_PRODUIT_CREATED',
            'data' => $type,
        ], 201);
    }

    /**
     * Détails d'un type de produit
     */
    public function show(string $uuid_type_produit): JsonResponse
    {
        $type = TypeProduit::where('uuid_type_produit', $uuid_type_produit)
            ->with(['produits' => function ($q) {
                $q->where('statut', 'actif')->orderBy('libelle');
            }])
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'message' => 'Détails du type de produit.',
            'code' => 'TYPE_PRODUIT_FOUND',
            'data' => $type,
        ]);
    }

    /**
     * Mettre à jour un type de produit
     */
    public function update(Request $request, string $uuid_type_produit): JsonResponse
    {
        $type = TypeProduit::where('uuid_type_produit', $uuid_type_produit)->firstOrFail();

        $validated = $request->validate([
            'code' => ['nullable', 'string', 'max:45', 'unique:type_produits,code,' . $type->id],
            'libelle' => ['nullable', 'string', 'max:90'],
        ]);

        $type->update([
            'code' => $validated['code'] ?? $type->code,
            'libelle' => $validated['libelle'] ?? $type->libelle,
            'updated_by' => $request->user()->uuid_user,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Type de produit mis à jour.',
            'code' => 'TYPE_PRODUIT_UPDATED',
            'data' => $type->fresh(),
        ]);
    }

    /**
     * Supprimer un type de produit
     */
    public function destroy(Request $request, string $uuid_type_produit): JsonResponse
    {
        $type = TypeProduit::where('uuid_type_produit', $uuid_type_produit)->firstOrFail();

        // Vérifier si le type est utilisé par des produits
        if ($type->produits()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Ce type de produit est associé à des produits et ne peut pas être supprimé.',
                'code' => 'TYPE_PRODUIT_IN_USE',
            ], 422);
        }

        $type->update([
            'deleted_by' => $request->user()->uuid_user,
        ]);

        $type->delete();

        return response()->json([
            'success' => true,
            'message' => 'Type de produit supprimé.',
            'code' => 'TYPE_PRODUIT_DELETED',
        ]);
    }

    /**
     * Liste des types de produits pour sélection (select)
     */
    public function select(Request $request): JsonResponse
    {
        $types = TypeProduit::orderBy('libelle')
            ->get(['uuid_type_produit as value', 'libelle as label', 'code']);

        return response()->json([
            'success' => true,
            'message' => 'Types de produits pour sélection.',
            'code' => 'TYPE_PRODUITS_SELECT',
            'data' => $types,
        ]);
    }
}