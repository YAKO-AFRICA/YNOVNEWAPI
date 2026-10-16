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
            'status', 'is_active', 'code_branche', 'code_contractant', 'search'
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
    public function store(StorePartnerRequest $request): JsonResponse
    {
        Log::info('PartnerController@store - données reçues', $request->all());

        $partner = $this->partnerService->create(
            $request->validated(),
            $request->user()->uuid_user
        );

        if ($partner) {
            if ($request->hasFile('logo')) {
            // ==================== 4. Documents ====================

                $DocumentDatas = [
                    [
                        'file'    => $request->file('logo'),  
                        'libelle' => 'Logo du partenaire ' . $partner->designation . ' (' . $partner->code . ')',
                    ]
                ];

                Log::info('Données des documents', $DocumentDatas);

                $payload = [
                    'reference_uuid' => $partner->uuid_partner,
                    'source'         => 'PARANTER_PARTNER_LOGO',
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
        }

        return response()->json([
            'success' => true,
            'message' => 'Partenaire créé avec succès.',
            'code'    => 'PARTNER_CREATED',
            'data'    => new PartnerResource($partner->fresh()),
        ], 201);
    }

    

    /**
     * Détails d'un partenaire
     */
    public function showPartenaire(string $uuid_partner): JsonResponse
    {
        $partner = Partner::where('uuid_partner', $uuid_partner)
            ->with(['reseaux', 'reseaux.agences', 'reseaux.agences.horaires', 'users', 'logoDocuments'])
            ->firstOrFail();

            $document = Document::withoutTrashed()->where('reference_uuid', $uuid_partner)->first();

            $url = ($document) ? env('APP_URL') .'/preview/doc/' . $document->nom_fichier : null;

        return response()->json([
            'success' => true,
            'message' => 'Détails du partenaire récupérés.',
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

        Log::info('PartnerController@update - données reçues', $request->all());
        
        // Récupérer les données validées
        $validatedData = $request->validated();
        
        // Gestion du logo si un nouveau fichier est envoyé
        if ($request->hasFile('logo')) {
            try {
                // Récupérer le document existant associé au partenaire
                $document = Document::withoutTrashed()
                    ->where('chemin', $partner->logo)
                    ->where('reference_uuid', $uuid_partner)
                    ->where('source', 'PARANTER_PARTNER_LOGO')
                    ->first();

                if ($document) {
                    // Mettre à jour le document existant
                    $documentResult = $this->documentService->updateDocument(
                        $document->uuid_document,
                        $request->file('logo'),
                        [
                            'libelle' => 'Logo du partenaire ' . $partner->designation . ' (' . $partner->code . ')',
                            'updated_by' => $request->user()->uuid_user,
                        ]
                    );

                    // Remplacer le logo par le chemin dans les données validées
                    $validatedData['logo'] = $documentResult['chemin_relatif'];
                } else {
                    // Créer un nouveau document si aucun n'existe
                    $documentData = [
                        'file' => $request->file('logo'),
                        'libelle' => 'Logo du partenaire ' . $partner->designation . ' (' . $partner->code . ')',
                    ];

                    $payload = [
                        'reference_uuid' => $uuid_partner,
                        'source' => 'PARANTER_PARTNER_LOGO',
                        'created_by' => $request->user()->uuid_user,
                        'documents' => [$documentData],
                    ];

                    $documentStore = $this->documentService->createDocument($payload);

                    $chemin = $documentStore['chemin_relatif']
                        ?? $documentStore['copieDirecte'][0]['chemin_relatif']
                        ?? $documentStore['copieDirecte']['chemin_relatif']
                        ?? null;

                    if ($chemin) {
                        $validatedData['logo'] = $chemin;
                    }
                }
            } catch (\Exception $e) {
                Log::error('[PartnerController] Erreur mise à jour logo', [
                    'uuid_partner' => $uuid_partner,
                    'erreur' => $e->getMessage(),
                ]);
                // Ne pas bloquer la mise à jour si le logo échoue
            }
        }
        
        $updated = $this->partnerService->update(
            $partner,
            $validatedData,
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