<?php
// app/Http/Controllers/Api/Ynov/FaqCategoryController.php
namespace App\Http\Controllers\Api\Ynov;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Ynov\StoreFaqCategoryRequest;
use App\Http\Requests\Api\Ynov\UpdateFaqCategoryRequest;
use App\Http\Resources\Api\Ynov\FaqCategoryResource;
use App\Models\Api\Ynov\parameter\FaqCategory;
use App\Services\Api\Ynov\FaqCategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class FaqCategoryController extends Controller
{
    public function __construct(
        private FaqCategoryService $faqCategoryService
    ) {}

    /**
     * Liste des catégories (publique)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $onlyActive = $request->boolean('only_active', true);
        $categories = $this->faqCategoryService->getCategoriesWithCount($onlyActive);

        return response()->json([
            'success' => true,
            'message' => 'Catégories de FAQs récupérées.',
            'code' => 'FAQ_CATEGORIES_LISTED',
            'data' => $categories,
        ]);
    }

    /**
     * [Admin] Créer une catégorie
     * 
     * @param StoreFaqCategoryRequest $request
     * @return JsonResponse
     */
    public function store(StoreFaqCategoryRequest $request): JsonResponse
    {
        try {
            $category = $this->faqCategoryService->create(
                $request->validated(),
                $request->user()->uuid_user
            );

            return response()->json([
                'success' => true,
                'message' => 'Catégorie créée avec succès.',
                'code' => 'FAQ_CATEGORY_CREATED',
                'data' => new FaqCategoryResource($category),
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la création de la catégorie.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * [Admin] Mettre à jour une catégorie
     * 
     * @param UpdateFaqCategoryRequest $request
     * @param string $uuid_faq_category
     * @return JsonResponse
     */
    public function update(UpdateFaqCategoryRequest $request, string $uuid_faq_category): JsonResponse
    {
        try {
            $category = FaqCategory::where('uuid_faq_category', $uuid_faq_category)->firstOrFail();
            
            $updated = $this->faqCategoryService->update(
                $category,
                $request->validated(),
                $request->user()->uuid_user
            );

            return response()->json([
                'success' => true,
                'message' => 'Catégorie mise à jour avec succès.',
                'code' => 'FAQ_CATEGORY_UPDATED',
                'data' => new FaqCategoryResource($updated),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Catégorie non trouvée.',
                'code' => 'FAQ_CATEGORY_NOT_FOUND',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la mise à jour de la catégorie.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * [Admin] Supprimer une catégorie
     * 
     * @param Request $request
     * @param string $uuid_faq_category
     * @return JsonResponse
     */
    public function destroy(Request $request, string $uuid_faq_category): JsonResponse
    {
        try {
            $category = FaqCategory::where('uuid_faq_category', $uuid_faq_category)->firstOrFail();
            
            $this->faqCategoryService->delete($category, $request->user()->uuid_user);

            return response()->json([
                'success' => true,
                'message' => 'Catégorie supprimée avec succès.',
                'code' => 'FAQ_CATEGORY_DELETED',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Catégorie non trouvée.',
                'code' => 'FAQ_CATEGORY_NOT_FOUND',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la suppression de la catégorie.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * [Admin] Activer/Désactiver une catégorie
     * 
     * @param Request $request
     * @param string $uuid_faq_category
     * @return JsonResponse
     */
    public function toggle(Request $request, string $uuid_faq_category): JsonResponse
    {
        try {
            $category = FaqCategory::where('uuid_faq_category', $uuid_faq_category)->firstOrFail();
            
            $category->update([
                'is_active' => !$category->is_active,
                'updated_by' => $request->user()->uuid_user,
            ]);

            return response()->json([
                'success' => true,
                'message' => $category->is_active ? 'Catégorie activée avec succès.' : 'Catégorie désactivée avec succès.',
                'code' => 'FAQ_CATEGORY_TOGGLED',
                'data' => new FaqCategoryResource($category->fresh()),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Catégorie non trouvée.',
                'code' => 'FAQ_CATEGORY_NOT_FOUND',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de l\'activation/désactivation de la catégorie.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * [Admin] Réordonner les catégories
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function reorder(Request $request): JsonResponse
    {
        $request->validate([
            'uuids' => ['required', 'array'],
            'uuids.*' => ['required', 'string', 'exists:faq_categories,uuid_faq_category'],
        ]);

        try {
            $this->faqCategoryService->reorder($request->uuids);

            return response()->json([
                'success' => true,
                'message' => 'Catégories réordonnées avec succès.',
                'code' => 'FAQ_CATEGORIES_REORDERED',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors du réordonnancement des catégories.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * [Admin] Dupliquer une catégorie
     * 
     * @param Request $request
     * @param string $uuid_faq_category
     * @return JsonResponse
     */
    public function duplicate(Request $request, string $uuid_faq_category): JsonResponse
    {
        try {
            $category = FaqCategory::where('uuid_faq_category', $uuid_faq_category)->firstOrFail();
            
            $newCategory = $this->faqCategoryService->duplicate(
                $category,
                $request->user()->uuid_user
            );

            return response()->json([
                'success' => true,
                'message' => 'Catégorie dupliquée avec succès.',
                'code' => 'FAQ_CATEGORY_DUPLICATED',
                'data' => new FaqCategoryResource($newCategory),
            ], 201);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Catégorie non trouvée.',
                'code' => 'FAQ_CATEGORY_NOT_FOUND',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la duplication de la catégorie.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * [Admin] Obtenir les statistiques des catégories
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function stats(Request $request): JsonResponse
    {
        try {
            $stats = $this->faqCategoryService->getStats();

            return response()->json([
                'success' => true,
                'message' => 'Statistiques des catégories récupérées.',
                'code' => 'FAQ_CATEGORY_STATS',
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la récupération des statistiques.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * [Admin] Obtenir une catégorie par son UUID
     * 
     * @param string $uuid_faq_category
     * @return JsonResponse
     */
    public function show(string $uuid_faq_category): JsonResponse
    {
        try {
            $category = FaqCategory::where('uuid_faq_category', $uuid_faq_category)
                ->with(['faqs' => function ($query) {
                    $query->where('is_active', true)->orderBy('order');
                }])
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'message' => 'Catégorie récupérée avec succès.',
                'code' => 'FAQ_CATEGORY_FOUND',
                'data' => new FaqCategoryResource($category),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Catégorie non trouvée.',
                'code' => 'FAQ_CATEGORY_NOT_FOUND',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la récupération de la catégorie.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * [Admin] Obtenir les catégories pour un select (dropdown)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function forSelect(Request $request): JsonResponse
    {
        $onlyActive = $request->boolean('only_active', true);
        $categories = $this->faqCategoryService->getCategoriesForSelect($onlyActive);

        return response()->json([
            'success' => true,
            'message' => 'Catégories pour sélection récupérées.',
            'code' => 'FAQ_CATEGORIES_SELECT',
            'data' => $categories,
        ]);
    }
}