<?php
// app/Http/Controllers/Api/Ynov/ProduitController.php

namespace App\Http\Controllers\Api\Ynov;

use App\Http\Controllers\Controller;
use App\Models\Api\Ynov\parameter\Produit;
use App\Models\Api\Ynov\parameter\ProduitFormule;
use App\Models\Api\Ynov\parameter\ProduitPrestation;
use App\Models\Api\Ynov\parameter\TypePrestation;
use App\Services\Api\Ynov\ProduitService;
use App\Services\Api\Ynov\ProduitFormuleService;
use App\Services\Api\Ynov\PrestationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ProduitController extends Controller
{
    public function __construct(
        private ProduitService $produitService,
        private ProduitFormuleService $formuleService,
        private PrestationService $prestationService
    ) {}

    /**
     * Liste des produits
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['statut', 'code_branche', 'type_produit_uuid', 'search']);
        $perPage = $request->integer('per_page', 20);

        $produits = $this->produitService->getProduits($filters, $perPage);

        return response()->json([
            'success' => true,
            'message' => 'Liste des produits récupérée.',
            'code' => 'PRODUITS_LISTED',
            'data' => $produits,
            'meta' => [
                'current_page' => $produits->currentPage(),
                'per_page' => $produits->perPage(),
                'total' => $produits->total(),
                'last_page' => $produits->lastPage(),
            ]
        ]);
    }

    /**
     * Créer un produit
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'libelle' => ['required', 'string', 'max:128'],
                'code' => ['nullable', 'string', 'max:25', 'unique:produits,code'],
                'code_branche' => ['nullable', 'string', 'max:25'],
                'code_produit_nature' => ['nullable', 'string', 'max:25'],
                'description' => ['nullable', 'string'],
                'statut' => ['nullable', 'string', 'in:actif,inactif'],
                'type_produit_uuid' => ['nullable', 'exists:type_produits,uuid_type_produit'],
                'age_mini_adh' => ['nullable', 'integer', 'min:0', 'max:127'],
                'age_maxi_adh' => ['nullable', 'integer', 'min:0', 'max:127'],
                'capital' => ['nullable', 'integer'],
                'vie_entiere' => ['nullable', 'boolean'],
                'code_produit_court' => ['nullable', 'string', 'max:5'],
                'code_marque' => ['nullable', 'string', 'max:20'],
            ]);

            $produit = $this->produitService->createProduit(
                $validated,
                $request->user()->uuid_user
            );

            return response()->json([
                'success' => true,
                'message' => 'Produit créé avec succès.',
                'code' => 'PRODUIT_CREATED',
                'data' => $produit,
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
     * Détails d'un produit
     */
    public function show(string $uuid_produit): JsonResponse
    {
        $produit = $this->produitService->getProduitWithDetails($uuid_produit);

        return response()->json([
            'success' => true,
            'message' => 'Détails du produit.',
            'code' => 'PRODUIT_FOUND',
            'data' => $produit,
        ]);
    }

    /**
     * Mettre à jour un produit
     */
    public function update(Request $request, string $uuid_produit): JsonResponse
    {
        try {
            $produit = Produit::where('uuid_produit', $uuid_produit)->firstOrFail();

            $validated = $request->validate([
                'libelle' => ['nullable', 'string', 'max:128'],
                'code' => ['nullable', 'string', 'max:25', 'unique:produits,code,' . $produit->id],
                'code_branche' => ['nullable', 'string', 'max:25'],
                'code_produit_nature' => ['nullable', 'string', 'max:25'],
                'description' => ['nullable', 'string'],
                'statut' => ['nullable', 'string', 'in:actif,inactif'],
                'type_produit_uuid' => ['nullable', 'exists:type_produits,uuid_type_produit'],
                'age_mini_adh' => ['nullable', 'integer', 'min:0', 'max:127'],
                'age_maxi_adh' => ['nullable', 'integer', 'min:0', 'max:127'],
                'capital' => ['nullable', 'integer'],
                'vie_entiere' => ['nullable', 'boolean'],
                'code_produit_court' => ['nullable', 'string', 'max:5'],
                'code_marque' => ['nullable', 'string', 'max:20'],
            ]);

            $updated = $this->produitService->updateProduit(
                $produit,
                $validated,
                $request->user()->uuid_user
            );

            return response()->json([
                'success' => true,
                'message' => 'Produit mis à jour.',
                'code' => 'PRODUIT_UPDATED',
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
     * Supprimer un produit
     */
    public function destroy(Request $request, string $uuid_produit): JsonResponse
    {
        try {
            $produit = Produit::where('uuid_produit', $uuid_produit)->firstOrFail();
            $this->produitService->deleteProduit($produit, $request->user()->uuid_user);

            return response()->json([
                'success' => true,
                'message' => 'Produit supprimé.',
                'code' => 'PRODUIT_DELETED',
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'code' => 'PRODUIT_DELETE_ERROR',
            ], 422);
        }
    }

    /**
     * Gérer les formules d'un produit
     */
    public function getFormules(string $uuid_produit, Request $request): JsonResponse
    {
        $produit = Produit::where('uuid_produit', $uuid_produit)->firstOrFail();
        $filters = $request->only(['est_actif', 'search']);
        $formules = $this->formuleService->getFormulesForProduit($produit->uuid_produit, $filters);

        return response()->json([
            'success' => true,
            'message' => 'Formules du produit.',
            'code' => 'FORMULES_LISTED',
            'data' => $formules,
        ]);
    }

    /**
     * Créer une formule pour un produit
     */
    public function storeFormule(Request $request, string $uuid_produit): JsonResponse
    {
        try {
            $produit = Produit::where('uuid_produit', $uuid_produit)->firstOrFail();

            $validated = $request->validate([
                'libelle' => ['required', 'string', 'max:128'],
                'code_produit_formule' => ['nullable', 'string', 'max:25'],
                'code_produit' => ['nullable', 'string', 'max:25'],
                'date_debut' => ['nullable', 'date'],
                'date_fin' => ['nullable', 'date', 'after:date_debut'],
                'est_actif' => ['nullable', 'boolean'],
                'fa' => ['nullable', 'numeric'],
                'fg' => ['nullable', 'numeric'],
                'tx' => ['nullable', 'numeric'],
                'code_canal_distribution' => ['nullable', 'string', 'max:25'],
            ]);

            $formule = $this->formuleService->createFormule(
                $produit,
                $validated,
                $request->user()->uuid_user
            );

            return response()->json([
                'success' => true,
                'message' => 'Formule créée.',
                'code' => 'FORMULE_CREATED',
                'data' => $formule,
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
     * Mettre à jour une formule
     */
    public function updateFormule(Request $request, string $uuid_formule): JsonResponse
    {
        try {
            $formule = ProduitFormule::where('uuid_produit_formule', $uuid_formule)->firstOrFail();

            $validated = $request->validate([
                'libelle' => ['nullable', 'string', 'max:128'],
                'code_produit_formule' => ['nullable', 'string', 'max:25'],
                'code_produit' => ['nullable', 'string', 'max:25'],
                'date_debut' => ['nullable', 'date'],
                'date_fin' => ['nullable', 'date', 'after:date_debut'],
                'est_actif' => ['nullable', 'boolean'],
                'fa' => ['nullable', 'numeric'],
                'fg' => ['nullable', 'numeric'],
                'tx' => ['nullable', 'numeric'],
                'code_canal_distribution' => ['nullable', 'string', 'max:25'],
            ]);

            $updated = $this->formuleService->updateFormule(
                $formule,
                $validated,
                $request->user()->uuid_user
            );

            return response()->json([
                'success' => true,
                'message' => 'Formule mise à jour.',
                'code' => 'FORMULE_UPDATED',
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
     * Supprimer une formule
     */
    public function destroyFormule(Request $request, string $uuid_formule): JsonResponse
    {
        $formule = ProduitFormule::where('uuid_produit_formule', $uuid_formule)->firstOrFail();
        $this->formuleService->deleteFormule($formule, $request->user()->uuid_user);

        return response()->json([
            'success' => true,
            'message' => 'Formule supprimée.',
            'code' => 'FORMULE_DELETED',
        ]);
    }

    /**
     * Gérer les prestations d'un produit
     */
    public function getPrestations(string $uuid_produit): JsonResponse
    {
        if (!$uuid_produit) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun paramètre uuid_produit fourni.',
                'code' => 'PARAMETER_ERROR',
            ], 404);
        }
        $produit = Produit::where('uuid_produit', $uuid_produit)->firstOrFail();
        $prestations = $produit->typePrestations()
            ->withPivot(['status', 'uuid_product_prestation', 'produit_type'])
            ->with('category')
            ->orderBy('libelle')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Prestations du produit.',
            'code' => 'PRESTATIONS_LISTED',
            'data' => $prestations,
        ]);
    }


    /**
     * Associer une ou plusieurs prestations à un produit
     */
    public function assignPrestation(Request $request, string $uuid_produit): JsonResponse
    {
        try {
            $produit = Produit::where('uuid_produit', $uuid_produit)->firstOrFail();

            $validated = $request->validate([
                'type_prestation_uuids' => ['required', 'array', 'min:1'],
                'type_prestation_uuids.*' => ['required', 'exists:type_prestations,uuid_type_prestation'],
                'produit_type' => ['nullable', 'string', 'max:55'],
                'status' => ['nullable', 'string', 'in:actif,inactif'],
            ]);

            // Si un seul UUID est fourni, utiliser la méthode simple
            if (count($validated['type_prestation_uuids']) === 1) {
                $typePrestation = TypePrestation::where('uuid_type_prestation', $validated['type_prestation_uuids'][0])->firstOrFail();
                
                $association = $this->prestationService->assignPrestation(
                    $produit,
                    $typePrestation,
                    $validated,
                    $request->user()->uuid_user
                );

                return response()->json([
                    'success' => true,
                    'message' => 'Prestation associée au produit.',
                    'code' => 'PRESTATION_ASSIGNED',
                    'data' => [
                        'associations' => [$association->load(['produit', 'typePrestation'])],
                        'assigned_count' => 1,
                        'skipped_count' => 0,
                    ],
                ], 201);
            }

            // Sinon, utiliser la méthode multiple
            $result = $this->prestationService->assignPrestations(
                $produit,
                $validated,
                $request->user()->uuid_user
            );

            return response()->json([
                'success' => true,
                'message' => $result['assigned_count'] > 0 
                    ? 'Prestations associées au produit avec succès.' 
                    : 'Aucune nouvelle prestation associée.',
                'code' => 'PRESTATIONS_ASSIGNED',
                'data' => [
                    'associations' => collect($result['associations'])->map->load(['produit', 'typePrestation']),
                    'assigned_count' => $result['assigned_count'],
                    'skipped_count' => $result['skipped_count'],
                    'already_assigned' => $result['already_assigned'],
                ],
            ], $result['assigned_count'] > 0 ? 201 : 200);

        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'code' => 'PRESTATION_ASSIGN_ERROR',
            ], 422);
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
     * Retirer une prestation d'un produit
     */
    public function removePrestation(Request $request, string $uuid_association): JsonResponse
    {
        $association = ProduitPrestation::where('uuid_product_prestation', $uuid_association)->firstOrFail();
        $this->prestationService->removePrestation($association, $request->user()->uuid_user);

        return response()->json([
            'success' => true,
            'message' => 'Prestation retirée du produit.',
            'code' => 'PRESTATION_REMOVED',
        ]);
    }

    /**
     * Types de prestations disponibles pour un produit
     */
    public function availablePrestations(string $uuid_produit, Request $request): JsonResponse
    {
        if (!$uuid_produit) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun paramètre uuid_produit fourni.',
                'code' => 'PARAMETER_ERROR',
            ], 404);
        }
        $produit = Produit::where('uuid_produit', $uuid_produit)->firstOrFail();
        $filters = $request->only(['category_uuid', 'search']);
        $types = $this->prestationService->getAvailableTypesForProduit($produit->uuid_produit, $filters);

        return response()->json([
            'success' => true,
            'message' => 'Types de prestations disponibles.',
            'code' => 'AVAILABLE_PRESTATIONS',
            'data' => $types,
        ]);
    }

    /**
     * Statistiques des produits
     */
    public function stats(): JsonResponse
    {
        $stats = $this->produitService->getStats();

        return response()->json([
            'success' => true,
            'message' => 'Statistiques des produits.',
            'code' => 'PRODUIT_STATS',
            'data' => $stats,
        ]);
    }
}