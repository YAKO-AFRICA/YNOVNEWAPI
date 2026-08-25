<?php
// app/Http/Controllers/Api/Ynov/FaqController.php
namespace App\Http\Controllers\Api\Ynov;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Ynov\StoreFaqRequest;
use App\Http\Requests\Api\Ynov\UpdateFaqRequest;
use App\Http\Resources\Api\Ynov\FaqResource;
use App\Models\Api\Ynov\parameter\Faq;
use App\Services\Api\Ynov\FaqService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FaqController extends Controller
{
    public function __construct(
        private FaqService $faqService
    ) {}

    /**
     * Liste des FAQs (publique)
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'faq_category_uuid', 'category', 'is_active', 'is_featured', 'search'
        ]);
        
        // Par défaut, on ne montre que les FAQs actives au public
        if (!isset($filters['is_active'])) {
            $filters['is_active'] = true;
        }
        
        $perPage = $request->integer('per_page', 20);
        $faqs = $this->faqService->getFaqs($filters, $perPage);

        return response()->json([
            'success' => true,
            'message' => 'Liste des FAQs récupérée.',
            'code' => 'FAQS_LISTED',
            'data' => FaqResource::collection($faqs),
            'meta' => [
                'current_page' => $faqs->currentPage(),
                'per_page' => $faqs->perPage(),
                'total' => $faqs->total(),
                'last_page' => $faqs->lastPage(),
            ]
        ]);
    }

    /**
     * Détails d'une FAQ (public - incrémente les vues)
     */
    public function show(string $uuid_faq): JsonResponse
    {
        $faq = Faq::where('uuid_faq', $uuid_faq)->active()->firstOrFail();
        
        // Incrémenter le compteur de vues
        $this->faqService->incrementViews($faq);

        return response()->json([
            'success' => true,
            'message' => 'Détails de la FAQ.',
            'code' => 'FAQ_FOUND',
            'data' => new FaqResource($faq->load('faqCategory')),
        ]);
    }

    /**
     * Rechercher dans les FAQs
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['required', 'string', 'min:2'],
        ]);

        $faqs = Faq::active()->search($request->q)->orderBy('order')->get();

        return response()->json([
            'success' => true,
            'message' => 'Résultats de recherche.',
            'code' => 'FAQ_SEARCH_RESULTS',
            'data' => FaqResource::collection($faqs),
        ]);
    }

    // ============================================================
    // ROUTES ADMIN
    // ============================================================

    /**
     * [Admin] Créer une FAQ
     */
    public function store(StoreFaqRequest $request): JsonResponse
    {
        $faq = $this->faqService->create(
            $request->validated(),
            $request->user()->uuid_user
        );

        return response()->json([
            'success' => true,
            'message' => 'FAQ créée avec succès.',
            'code' => 'FAQ_CREATED',
            'data' => new FaqResource($faq->load('faqCategory')),
        ], 201);
    }

    /**
     * [Admin] Mettre à jour une FAQ
     */
    public function update(UpdateFaqRequest $request, string $uuid_faq): JsonResponse
    {
        $faq = Faq::where('uuid_faq', $uuid_faq)->firstOrFail();
        
        $updated = $this->faqService->update(
            $faq,
            $request->validated(),
            $request->user()->uuid_user
        );

        return response()->json([
            'success' => true,
            'message' => 'FAQ mise à jour avec succès.',
            'code' => 'FAQ_UPDATED',
            'data' => new FaqResource($updated->load('faqCategory')),
        ]);
    }

    /**
     * [Admin] Supprimer une FAQ
     */
    public function destroy(Request $request, string $uuid_faq): JsonResponse
    {
        $faq = Faq::where('uuid_faq', $uuid_faq)->firstOrFail();
        
        $this->faqService->delete($faq, $request->user()->uuid_user);

        return response()->json([
            'success' => true,
            'message' => 'FAQ supprimée avec succès.',
            'code' => 'FAQ_DELETED',
        ]);
    }

    /**
     * [Admin] Activer/Désactiver une FAQ
     */
    public function toggle(Request $request, string $uuid_faq): JsonResponse
    {
        $faq = Faq::where('uuid_faq', $uuid_faq)->firstOrFail();
        
        $faq->update([
            'is_active' => !$faq->is_active,
            'updated_by' => $request->user()->uuid_user,
        ]);

        return response()->json([
            'success' => true,
            'message' => $faq->is_active ? 'FAQ activée.' : 'FAQ désactivée.',
            'code' => 'FAQ_TOGGLED',
            'data' => new FaqResource($faq),
        ]);
    }
}