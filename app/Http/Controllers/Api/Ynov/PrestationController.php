<?php
// app/Http/Controllers/Api/Ynov/PrestationController.php

namespace App\Http\Controllers\Api\Ynov;

use App\Http\Controllers\Controller;
use App\Models\Api\Ynov\parameter\CategoryTypePrestation;
use App\Models\Api\Ynov\parameter\TypePrestation;
use App\Services\Api\Ynov\PrestationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PrestationController extends Controller
{
    public function __construct(
        private PrestationService $prestationService
    ) {}

    // ============================================================
    // CATÉGORIES DE PRESTATIONS
    // ============================================================

    /**
     * Liste des catégories
     */
    public function categories(Request $request): JsonResponse
    {
        $filters = $request->only(['status', 'search']);
        $perPage = $request->integer('per_page', 20);

        $categories = $this->prestationService->getCategoriesWithTypes($filters, $perPage);

        return response()->json([
            'success' => true,
            'message' => 'Liste des catégories de prestations.',
            'code' => 'CATEGORIES_LISTED',
            'data' => $categories,
            'meta' => [
                'current_page' => $categories->currentPage(),
                'per_page' => $categories->perPage(),
                'total' => $categories->total(),
                'last_page' => $categories->lastPage(),
            ]
        ]);
    }

    /**
     * Créer une catégorie
     */
    public function storeCategory(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'libelle' => ['required', 'string', 'max:90'],
                'code' => ['nullable', 'string', 'max:45', 'unique:category_type_prestations,code'],
                'description' => ['nullable', 'string'],
                'status' => ['nullable', 'string', 'in:actif,inactif'],
            ]);

            $category = $this->prestationService->createCategory(
                $validated,
                $request->user()->uuid_user
            );

            return response()->json([
                'success' => true,
                'message' => 'Catégorie créée avec succès.',
                'code' => 'CATEGORY_CREATED',
                'data' => $category,
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation.',
                'errors' => $e->errors(),
                'code' => 'VALIDATION_ERROR',
            ], 422);
        }
    }

    /**
     * Détails d'une catégorie
     */
    public function showCategory(string $uuid_category): JsonResponse
    {
        $category = CategoryTypePrestation::where('uuid_category_type_prestations', $uuid_category)
            ->with(['typePrestations' => function ($q) {
                $q->where('status', 'actif')->orderBy('libelle');
            }])
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'message' => 'Détails de la catégorie.',
            'code' => 'CATEGORY_FOUND',
            'data' => $category,
        ]);
    }

    /**
     * Mettre à jour une catégorie
     */
    public function updateCategory(Request $request, string $uuid_category): JsonResponse
    {
        try {
            $category = CategoryTypePrestation::where('uuid_category_type_prestations', $uuid_category)->firstOrFail();

            $validated = $request->validate([
                'libelle' => ['nullable', 'string', 'max:90'],
                'code' => ['nullable', 'string', 'max:45', 'unique:category_type_prestations,code,' . $category->id],
                'description' => ['nullable', 'string'],
                'status' => ['nullable', 'string', 'in:actif,inactif'],
            ]);

            $updated = $this->prestationService->updateCategory(
                $category,
                $validated,
                $request->user()->uuid_user
            );

            return response()->json([
                'success' => true,
                'message' => 'Catégorie mise à jour.',
                'code' => 'CATEGORY_UPDATED',
                'data' => $updated,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation.',
                'errors' => $e->errors(),
                'code' => 'VALIDATION_ERROR',
            ], 422);
        }
    }

    /**
     * Supprimer une catégorie
     */
    public function deleteCategory(Request $request, string $uuid_category): JsonResponse
    {
        try {
            $category = CategoryTypePrestation::where('uuid_category_type_prestations', $uuid_category)->firstOrFail();
            $this->prestationService->deleteCategory($category, $request->user()->uuid_user);

            return response()->json([
                'success' => true,
                'message' => 'Catégorie supprimée.',
                'code' => 'CATEGORY_DELETED',
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'code' => 'CATEGORY_DELETE_ERROR',
            ], 422);
        }
    }

    // ============================================================
    // TYPES DE PRESTATIONS
    // ============================================================

    /**
     * Liste des types de prestations
     */
    public function types(Request $request): JsonResponse
    {
        $query = TypePrestation::with('category');

        if ($request->has('category_uuid')) {
            $query->where('category_uuid', $request->category_uuid);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $query->search($request->search);
        }

        $perPage = $request->integer('per_page', 20);
        $types = $query->orderBy('libelle')->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Liste des types de prestations.',
            'code' => 'TYPES_LISTED',
            'data' => $types,
            'meta' => [
                'current_page' => $types->currentPage(),
                'per_page' => $types->perPage(),
                'total' => $types->total(),
                'last_page' => $types->lastPage(),
            ]
        ]);
    }

    /**
     * Créer un type de prestation
     */
    public function storeType(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'libelle' => ['required', 'string', 'max:90'],
                'code' => ['nullable', 'string', 'max:45', 'unique:type_prestations,code'],
                'description' => ['nullable', 'string'],
                'category_uuid' => ['required', 'exists:category_type_prestations,uuid_category_type_prestations'],
                'impact' => ['nullable', 'string', 'in:0,1'],
                'delai_traitement' => ['nullable', 'integer', 'min:0'],
                'status' => ['nullable', 'string', 'in:actif,inactif'],
            ]);

            $type = $this->prestationService->createTypePrestation(
                $validated,
                $request->user()->uuid_user
            );

            return response()->json([
                'success' => true,
                'message' => 'Type de prestation créé avec succès.',
                'code' => 'TYPE_PRESTATION_CREATED',
                'data' => $type,
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation.',
                'errors' => $e->errors(),
                'code' => 'VALIDATION_ERROR',
            ], 422);
        }
    }

    /**
     * Détails d'un type de prestation
     */
    public function showType(string $uuid_type): JsonResponse
    {
        $type = TypePrestation::where('uuid_type_prestation', $uuid_type)
            ->with(['category', 'produits' => function ($q) {
                $q->where('statut', 'actif')->orderBy('libelle');
            }])
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'message' => 'Détails du type de prestation.',
            'code' => 'TYPE_PRESTATION_FOUND',
            'data' => $type,
        ]);
    }

    /**
     * Mettre à jour un type de prestation
     */
    public function updateType(Request $request, string $uuid_type): JsonResponse
    {
        try {
            $type = TypePrestation::where('uuid_type_prestation', $uuid_type)->firstOrFail();

            $validated = $request->validate([
                'libelle' => ['nullable', 'string', 'max:90'],
                'code' => ['nullable', 'string', 'max:45', 'unique:type_prestations,code,' . $type->id],
                'description' => ['nullable', 'string'],
                'category_uuid' => ['nullable', 'exists:category_type_prestations,uuid_category_type_prestations'],
                'impact' => ['nullable', 'string', 'in:0,1'],
                'delai_traitement' => ['nullable', 'integer', 'min:0'],
                'status' => ['nullable', 'string', 'in:actif,inactif'],
            ]);

            $updated = $this->prestationService->updateTypePrestation(
                $type,
                $validated,
                $request->user()->uuid_user
            );

            return response()->json([
                'success' => true,
                'message' => 'Type de prestation mis à jour.',
                'code' => 'TYPE_PRESTATION_UPDATED',
                'data' => $updated,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation.',
                'errors' => $e->errors(),
                'code' => 'VALIDATION_ERROR',
            ], 422);
        }
    }

    /**
     * Supprimer un type de prestation
     */
    public function deleteType(Request $request, string $uuid_type): JsonResponse
    {
        try {
            $type = TypePrestation::where('uuid_type_prestation', $uuid_type)->firstOrFail();
            $this->prestationService->deleteTypePrestation($type, $request->user()->uuid_user);

            return response()->json([
                'success' => true,
                'message' => 'Type de prestation supprimé.',
                'code' => 'TYPE_PRESTATION_DELETED',
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'code' => 'TYPE_PRESTATION_DELETE_ERROR',
            ], 422);
        }
    }

    /**
     * Statistiques des prestations
     */
    public function stats(): JsonResponse
    {
        $stats = $this->prestationService->getStats();

        return response()->json([
            'success' => true,
            'message' => 'Statistiques des prestations.',
            'code' => 'PRESTATION_STATS',
            'data' => $stats,
        ]);
    }
}