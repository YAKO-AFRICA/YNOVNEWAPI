<?php

namespace App\Http\Controllers\Api\Ynov\Esouscription;

use App\Http\Controllers\Controller;
use App\Models\Api\Ynov\parameter\Filiation;
use App\Models\Api\Ynov\parameter\Partner;
use App\Models\Api\Ynov\parameter\Produit;
use App\Models\Api\Ynov\parameter\ProduitFormule;
use App\Models\Api\Ynov\parameter\Reseau;
use App\Models\Api\Ynov\parameter\ReseauProduct;
use App\Models\Api\Ynov\parameter\User;
use App\Services\Api\Ynov\ESouscription\BanqueAgenceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Throwable;

class ParamController extends Controller
{
    public function __construct(private BanqueAgenceService $banqueAgenceService)
    {
    }

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

    // function pour avoir tout les produit commercialisé dans un reseau 
    public function getProductByReseau(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'reseau_uuid' => ['required', 'string'],
        ]);


        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'code'    => 'RESEAU_UUID_REQUIRED',
                'message' => 'Le parametre reseau_uuid est obligatoire',
            ], 400);
        }

        $validated = $validator->validated();

        try {
            $reseau = Reseau::where('uuid_reseau', $validated['reseau_uuid'])->first();

            if (!$reseau) {
                return response()->json([
                    'success' => false,
                    'code' => 'RESEAU_NOT_FOUND',
                    'message' => 'Reseau introuvable',
                ], 404);
            }
            
            $productByReseau = ReseauProduct::where('reseau_uuid', $validated['reseau_uuid'])->get();

            if (!$productByReseau) {
                return response()->json([
                    'success' => false,
                    'code' => 'RESEAU_PRODUCT_NOT_FOUND',
                    'message' => 'Paramettrage des produits pour le réseau est introuvable',
                ], 404);
            }

            $pluckedProductUuids = $productByReseau->pluck('product_uuid')->toArray();
            $pluckedFormuleUuids = $productByReseau->pluck('formule_uuid')->toArray();

            $products = Produit::whereIn('uuid_produit', $pluckedProductUuids)->get();
            $formules = ProduitFormule::whereIn('uuid_produit_formule', $pluckedFormuleUuids)->get();

            $formulesByUuid = $formules->keyBy('uuid_produit_formule');
            $reseauProductsByProductUuid = $productByReseau->keyBy('product_uuid');
            $products->each(function (Produit $product) use ($reseauProductsByProductUuid, $formulesByUuid) {
                $reseauProduct = $reseauProductsByProductUuid->get($product->uuid_produit);
                $product->setAttribute(
                    'formule',
                    $reseauProduct ? $formulesByUuid->get($reseauProduct->formule_uuid) : null
                );
            });

            if (!$products) {
                return response()->json([
                    'success' => false,
                    'code' => 'PRODUCT_NOT_FOUND',
                    'message' => 'Aucun produit correspondant aux paramètres trouvés pour le réseau',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'code' => 'GET_RESEAU_PRODUCT_SUCCESS',
                'message' => 'Liste des produits par réseau récupérée avec succès',
                'data' => [
                    'reseau' => $reseau,
                    'products' => $products,
                    'details_reseau_product' => $productByReseau,
                ]
            ],200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'code' => 'RESEAU_PRODUCT_ERROR',
                'message' => $e->errors(),
            ], 500);
        }
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


    public function getProductByPartner(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'code_partner' => ['required', 'string'],
        ]);


        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'code'    => 'CODE_PARTNER_REQUIRED',
                'message' => 'Le parametre code_partner est obligatoire',
            ], 400);
        }

        $validated = $validator->validated();

        try {

            $partner = Partner::where('code_contractant', $validated['code_partner'])->first();

            if (!$partner) {
                return response()->json([
                    'success' => false,
                    'code' => 'PARTNER_NOT_FOUND',
                    'message' => 'Partenaire introuvable',
                ], 404);
            }
            

            $reseau = Reseau::where('partner_uuid', $partner->uuid_partner)->first();

            if (!$reseau) {
                return response()->json([
                    'success' => false,
                    'code' => 'RESEAU_NOT_FOUND',
                    'message' => 'Reseau introuvable',
                ], 404);
            }
            
            $productByReseau = ReseauProduct::where('reseau_uuid', $reseau->uuid_reseau)->get();

            if (!$productByReseau) {
                return response()->json([
                    'success' => false,
                    'code' => 'RESEAU_PRODUCT_NOT_FOUND',
                    'message' => 'Paramettrage des produits pour le réseau est introuvable',
                ], 404);
            }

            $pluckedProductUuids = $productByReseau->pluck('product_uuid')->toArray();
            $pluckedFormuleUuids = $productByReseau->pluck('formule_uuid')->toArray();

            $products = Produit::whereIn('uuid_produit', $pluckedProductUuids)->get();
            $formules = ProduitFormule::whereIn('uuid_produit_formule', $pluckedFormuleUuids)->get();

            $formulesByUuid = $formules->keyBy('uuid_produit_formule');
            $reseauProductsByProductUuid = $productByReseau->keyBy('product_uuid');
            $products->each(function (Produit $product) use ($reseauProductsByProductUuid, $formulesByUuid) {
                $reseauProduct = $reseauProductsByProductUuid->get($product->uuid_produit);
                $product->setAttribute(
                    'formule',
                    $reseauProduct ? $formulesByUuid->get($reseauProduct->formule_uuid) : null
                );
            });

            if (!$products) {
                return response()->json([
                    'success' => false,
                    'code' => 'PRODUCT_NOT_FOUND',
                    'message' => 'Aucun produit correspondant aux paramètres trouvés pour le réseau',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'code' => 'GET_RESEAU_PRODUCT_SUCCESS',
                'message' => 'Liste des produits par réseau récupérée avec succès',
                'data' => [
                    'reseau' => $reseau,
                    'products' => $products,
                    'details_reseau_product' => $productByReseau,
                ]
            ],200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'code' => 'RESEAU_PRODUCT_ERROR',
                'message' => $e->errors(),
            ], 500);
        }
    }

    public function checkUserByPartner(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code_partner' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'code'    => 'CODE_PARTNER_REQUIRED',
                'message' => 'Le parametre code_partner est obligatoire',
            ], 400);
        }

        $validated = $validator->validated();

        // 1. Retrouver le partenaire
        $partner = Partner::where('code_contractant', $validated['code_partner'])->first();

        if (!$partner) {
            return response()->json([
                'success' => false,
                'code'    => 'PARTNER_NOT_FOUND',
                'message' => 'Partenaire introuvable',
            ], 404);
        }

        // 2. Premier utilisateur lié à ce partenaire
        $user = User::where('partner_uuid', $partner->uuid_partner)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'code'    => 'USER_NOT_FOUND',
                'message' => 'Aucun utilisateur associé à ce partenaire n\'a été trouvé',
            ], 404);
        }

        
        //    (supprime les anciens tokens pour n'en garder qu'un)
        $user->tokens()->delete();

        $token = $user->createToken('WEB')->plainTextToken; // 3. Connexion + génération du token Sanctum

        // 4. Réponse : données user + token (même format qu'une API de login)
        return response()->json([
            'success' => true,
            'code'    => 'CHECK_USER_BY_PARTNER_SUCCESS',
            'message' => 'Utilisateur connecté avec succès',
            'data'    => [
                'user'         => $user,
                'partner'      => $partner,
                'access_token' => $token,
                'token_type'   => 'Bearer',
            ],
        ], 200);
    }


    

    // gestion des villes NSIL

    public function getNsilVilles()
    {
        try {
            $response = Http::timeout(15)
                ->get('https://api.yakoafricassur.com/enov/villes');


            if ($response->successful()) {
                
                return $response->json();
            }

            return response()->json([
                'success' => false,
                'message' => 'Impossible de récupérer les villes.',
                'status' => $response->status(),
            ], $response->status());

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la connexion au service des villes.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function getNsilProfession()
    {
        try {
            $response = Http::timeout(15)
                ->get('https://api.yakoafricassur.com/enov/professions');


            if ($response->successful()) {

                return $response->json();
            }

            return response()->json([
                'success' => false,
                'message' => 'Impossible de récupérer les professions.',
                'status' => $response->status(),
            ], $response->status());

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la connexion au service des professions.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getFiliation()
    {
        try {
            $filiations = Filiation::where('etat', 'actif')->get();

            if ($filiations->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune filiation trouvée.',
                ], 404);
            }


            if ($filiations) {
                return response()->json([
                    'success' => true,
                    'message' => 'Liste des filiations récupérée avec succès.',
                    'data' => $filiations
                ], 200);
            }

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la connexion au service des filiations.',
                'error' => $e->getMessage(),
            ], 500);
        }

    }
    public function getSigleBanque()
    {

        try {

            $banqueSigle = $this->banqueAgenceService->getSigleBanques();

            if ($banqueSigle->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'code' => 'BANQUE_NOT_FOUND',
                    'message' => 'Aucune banque-agence trouvée.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'code' => 'BANQUES_LISTED',
                'message' => 'Liste des banques-agences récupérée avec succès.',
                'data' => $banqueSigle,
            ], 200);

        } catch (Throwable $e) {
            Log::error('Erreur lors de la récupération des banques-agences.', [
                'exception' => $e,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la connexion au service des banques-agences.',
            ], 500);
        }
    }

    public function getGuichetByBanque(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code_banque' => ['required', 'string', 'max:50'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'code'    => 'CODE_BANQUE_REQUIRED',
                'message' => 'Le parametre code_banque est obligatoire',
            ], 400);
        }

        $validated = $validator->validated();

        try {
            $guichets = $this->banqueAgenceService->getGuichetsByCodeBanque($validated['code_banque']);

            if ($guichets->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'code' => 'GUICHET_NOT_FOUND',
                    'message' => 'Aucun guichet actif trouvé pour cette banque.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'code' => 'GUICHETS_LISTED',
                'message' => 'Liste des guichets récupérée avec succès.',
                'data' => $guichets,
            ], 200);
        } catch (Throwable $e) {
            Log::error('Erreur lors de la récupération des guichets de la banque.', [
                'code_banque' => $validated['code_banque'],
                'exception' => $e,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des guichets de la banque.',
            ], 500);
        }
    }
}
