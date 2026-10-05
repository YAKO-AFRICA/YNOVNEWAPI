<?php
// app/Http/Controllers/Api/Ynov/PartnerController.php
namespace App\Http\Controllers\Api\Ynov;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Ynov\StorePartnerRequest;
use App\Http\Requests\Api\Ynov\UpdatePartnerRequest;
use App\Http\Resources\Api\Ynov\PartnerResource;
use App\Models\Api\Ynov\Esouscription\Document;
use App\Models\Api\Ynov\parameter\Partner;
use App\Services\Api\Ynov\Documents\DocumentService;
use App\Services\Api\Ynov\PartnerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PartnerController extends Controller
{
    public function __construct(
        private PartnerService $partnerService,
        private DocumentService $documentService,
    ) {}



    /**
     * Liste des partenaires
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'status', 'is_active', 'type', 'categorie', 'code_branche', 'search', 'not_expired'
        ]);
        
        $perPage = $request->integer('per_page', 20);
        $partners = $this->partnerService->getPartners($filters, $perPage);

        return response()->json([
            'success' => true,
            'message' => 'Liste des partenaires récupérée.',
            'code' => 'PARTNERS_LISTED',
            'data' => PartnerResource::collection($partners),
            'meta' => [
                'current_page' => $partners->currentPage(),
                'per_page' => $partners->perPage(),
                'total' => $partners->total(),
                'last_page' => $partners->lastPage(),
            ]
        ]);
    }

    /**
     * Créer un partenaire
     */
    public function store(Request $request): JsonResponse
    {
        Log::info('PartnerController@store - données reçues', $request->all());

        $validator = Validator::make($request->all(), [
            'code'        => 'required|string|max:55|unique:partners,code',
            'code_contractant' => 'required|string|max:255',
            'designation' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
            'logo' => 'nullable|file|max:' . config('documents.max_size'),
            'is_active' => 'nullable|boolean',
            'status' => 'nullable|string|max:100',
            'created_by' => 'nullable|string|max:255',
        ]);


        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $partner = $this->partnerService->create(
            $validator->validated(),
            $request->user()->uuid_user
        );

        if ($partner) {
            // ==================== 4. Documents ====================

            $DocumentDatas = [
                [
                    'file'    => $request->file('logo'),  
                    'libelle' => 'Logo_' . $partner->code,
                ]
            ];

            Log::info('Données des documents', $DocumentDatas);

            $payload = [
                'reference_uuid' => $partner->uuid_partner,
                'source'         => 'E-SOUSCRIPTION',
                'created_by'     => $request->user()->uuid_user,
                'documents'      => $DocumentDatas,
            ];

            $documentStore = $this->documentService->createDocument($payload);

            Log::info('[DocumentService] copieDirecte tttttttt', $documentStore);

            if (!$documentStore) {
                throw new \Exception("Échec de l'enregistrement des documents.");
            }

            $chemin = $documentStore['chemin_relatif']
                ?? $documentStore['copieDirecte'][0]['chemin_relatif']
                ?? $documentStore['copieDirecte']['chemin_relatif']
                ?? null;

            $partner->logo = $chemin;
            $partner->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Partenaire créé avec succès.',
            'code'    => 'PARTNER_CREATED',
            'data'    => new PartnerResource($partner),
        ], 201);
    }

    

    /**
     * Détails d'un partenaire
     */
    public function showPartenaire(string $uuid_partner): JsonResponse
    {
        $partner = Partner::where('uuid_partner', $uuid_partner)
            ->with(['reseaux', 'reseaux.agences', 'reseaux.agences.horaires', 'users'])
            ->firstOrFail();

            $document = Document::withoutTrashed()->where('reference_uuid', $uuid_partner)->first();

            if (!$document) {
                return response()->json([
                    'success' => false,
                    'message' => 'Document introuvable.',
                ], 404);
            }

            $url = url('preview/doc/' . $document->nom_fichier);

        return response()->json([
            'success' => true,
            'message' => 'Détails du partenaire.',
            'code' => 'PARTNER_FOUND',
            'data' => new PartnerResource($partner),
            'document_url' => $url,
        ]);
    }

    /**
     * Mettre à jour un partenaire
     */
    public function update(UpdatePartnerRequest $request, string $uuid_partner): JsonResponse
    {
        $partner = Partner::where('uuid_partner', $uuid_partner)->firstOrFail();
        
        $updated = $this->partnerService->update(
            $partner,
            $request->validated(),
            $request->user()->uuid_user
        );

        return response()->json([
            'success' => true,
            'message' => 'Partenaire mis à jour avec succès.',
            'code' => 'PARTNER_UPDATED',
            'data' => new PartnerResource($updated),
        ]);
    }

    /**
     * Supprimer un partenaire
     */
    public function destroy(Request $request, string $uuid_partner): JsonResponse
    {
        $partner = Partner::where('uuid_partner', $uuid_partner)->firstOrFail();
        
        try {
            $this->partnerService->delete($partner, $request->user()->uuid_user);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'code' => 'PARTNER_HAS_RESEAVX',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Partenaire supprimé avec succès.',
            'code' => 'PARTNER_DELETED',
        ]);
    }

    /**
     * Récupérer les réseaux d'un partenaire
     */
    public function reseaux(string $uuid_partner): JsonResponse
    {
        $partner = Partner::where('uuid_partner', $uuid_partner)->firstOrFail();
        $reseaux = $partner->reseaux()->with('agences')->get();

        return response()->json([
            'success' => true,
            'message' => 'Réseaux du partenaire récupérés.',
            'code' => 'PARTNER_RESEAUX_LISTED',
            'data' => $reseaux,
        ]);
    }
}