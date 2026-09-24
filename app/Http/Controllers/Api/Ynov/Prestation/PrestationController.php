<?php
// app/Http/Controllers/Api/Ynov/Prestation/PrestationController.php

namespace App\Http\Controllers\Api\Ynov\Prestation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Ynov\Prestation\StorePrestationRequest;
use App\Http\Requests\Api\Ynov\Prestation\UpdatePrestationRequest;
use App\Http\Resources\Api\Ynov\PrestationResource;
use App\Models\Api\Ynov\parameter\CategoryTypePrestation;
use App\Models\Api\Ynov\parameter\TypePrestation;
use App\Services\Api\Ynov\Prestation\PrestationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PrestationController extends Controller
{
    public function __construct(
        private PrestationService $prestationService,
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

        if ($categories->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'Aucune catégorie trouvée.',
                'code' => 'CATEGORIES_EMPTY',
                'data' => [],
                'meta' => [
                    'current_page' => 1,
                    'per_page' => $perPage,
                    'total' => 0,
                    'last_page' => 1,
                ]
            ]);
        }

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

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Catégorie non trouvée.',
                'code' => 'CATEGORY_NOT_FOUND',
                'data' => null,
            ], 404);
        }

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

        if ($types->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'Aucun type de prestation trouvé.',
                'code' => 'TYPES_EMPTY',
                'data' => [],
                'meta' => [
                    'current_page' => 1,
                    'per_page' => $perPage,
                    'total' => 0,
                    'last_page' => 1,
                ]
            ]);
        }

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

        if (!$type) {
            return response()->json([
                'success' => true,
                'message' => 'Aucun details de type de prestation trouvée.',
                'code' => 'TYPE_PRESTATION_EMPTY',
                'data' => $type,
            ]);
        }

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
     * Liste des prestations
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'search',
            'status',
            'client_uuid',
            'type_prestation_uuid',
            'gestionnaire_uuid',
            'partner_uuid',
            'is_migrated',
        ]);

        $perPage = $request->integer('per_page', 20);
        $prestations = $this->prestationService->getPrestations($filters, $perPage);

        if ($prestations->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'Aucune prestation trouvée.',
                'code' => 'PRESTATIONS_EMPTY',
                'data' => [],
                'meta' => [
                    'current_page' => 1,
                    'per_page' => $perPage,
                    'total' => 0,
                    'last_page' => 1,
                ]
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Liste des prestations.',
            'code' => 'PRESTATIONS_LISTED',
            'data' => PrestationResource::collection($prestations),
            'meta' => [
                'current_page' => $prestations->currentPage(),
                'per_page' => $prestations->perPage(),
                'total' => $prestations->total(),
                'last_page' => $prestations->lastPage(),
            ],
        ]);
    }

    /**
     * Créer une prestation
     */
    public function store(StorePrestationRequest $request): JsonResponse
    {
        $prestation = $this->prestationService->createPrestation(
            $request->validated(),
            $request->user()->uuid_user
        );

        if (!$prestation) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la prestation.',
                'code' => 'PRESTATION_CREATION_ERROR',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Prestation créée avec succès.',
            'code' => 'PRESTATION_CREATED',
            'data' => new PrestationResource($prestation),
        ], 201);
    }

    /**
     * Détails d'une prestation
     */
    public function show(string $uuid_prestation): JsonResponse
    {
        $prestation = $this->prestationService->findPrestation($uuid_prestation);

        if (!$prestation) {
            return response()->json([
                'success' => false,
                'message' => 'Prestation non trouvée.',
                'code' => 'PRESTATION_NOT_FOUND',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Détails de la prestation.',
            'code' => 'PRESTATION_FOUND',
            'data' => new PrestationResource($prestation),
        ]);
    }

    /**
     * Mettre à jour une prestation
     */
    public function update(UpdatePrestationRequest $request, string $uuid_prestation): JsonResponse
    {
        $prestation = $this->prestationService->findPrestation($uuid_prestation);
        $updated = $this->prestationService->updatePrestation(
            $prestation,
            $request->validated(),
            $request->user()->uuid_user
        );

        if (!$updated) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de la prestation.',
                'code' => 'PRESTATION_UPDATE_ERROR',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Prestation mise à jour.',
            'code' => 'PRESTATION_UPDATED',
            'data' => new PrestationResource($updated),
        ]);
    }

    /**
     * Supprimer une prestation
     */
    public function destroy(Request $request, string $uuid_prestation): JsonResponse
    {
        $prestation = $this->prestationService->findPrestation($uuid_prestation);
        if (!$prestation) {
            return response()->json([
                'success' => false,
                'message' => 'Prestation non trouvée.',
                'code' => 'PRESTATION_NOT_FOUND',
            ], 404);
        }
        $this->prestationService->deletePrestation($prestation, $request->user()->uuid_user);


        return response()->json([
            'success' => true,
            'message' => 'Prestation supprimée.',
            'code' => 'PRESTATION_DELETED',
        ]);
    }

    /**
     * Statistiques des prestations
     */
    public function prestationStats(): JsonResponse
    {
        $stats = $this->prestationService->getPrestationStats();

        return response()->json([
            'success' => true,
            'message' => 'Statistiques des prestations.',
            'code' => 'PRESTATION_STATS',
            'data' => $stats,
        ]);
    }

    /**
     * Récupérer les motifs de prestations pour un produit avec le montant maximum
     */
    public function motifsWithMaxAmount(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'code_produit' => ['required', 'string'],
                'id_contrat' => ['required', 'integer'],
                'category_uuid' => ['nullable', 'string', 'exists:category_type_prestations,uuid_category_type_prestations'],
            ]);

            $result = $this->prestationService->getMotifsWithMaxAmount(
                $validated['code_produit'],
                $validated['id_contrat'],
                $validated['category_uuid'] ?? null
            );

            return response()->json($result);
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
     * Vérifier si un motif de prestation nécessite une prise de rendez-vous
     */
    public function checkMotifAppointment(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'type_prestation_uuid' => ['required', 'string', 'exists:type_prestations,uuid_type_prestation'],
            ]);

            $result = $this->prestationService->checkMotifRequiresAppointment(
                $validated['type_prestation_uuid']
            );

            return response()->json($result);
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
     * Statistiques des prestations (type / catégorie / association)
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

    /**
     * Vérifier l'éligibilité d'une prestation selon les règles métier
     */
    public function checkEligibility(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'code_produit' => ['required', 'string'],
                'id_contrat' => ['required', 'integer'],
                'type_prestation_uuid' => ['required', 'string', 'exists:type_prestations,uuid_type_prestation'],
            ]);

            $result = $this->prestationService->checkPrestationEligibility(
                $validated['code_produit'],
                $validated['id_contrat'],
                $validated['type_prestation_uuid']
            );

            return response()->json($result);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation.',
                'errors' => $e->errors(),
                'code' => 'VALIDATION_ERROR',
            ], 422);
        }
    }
}
