<?php

namespace App\Http\Controllers\Api\Ynov;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Ynov\StoreMotifTraitementRequest;
use App\Http\Requests\Api\Ynov\UpdateMotifTraitementRequest;
use App\Services\Api\Ynov\MotifTraitementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MotifTraitementController extends Controller
{
    public function __construct(
        private MotifTraitementService $motifTraitementService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'status', 'type', 'module']);
        $perPage = $request->integer('per_page', 15);

        $motifs = $this->motifTraitementService->list($filters, $perPage);

        return response()->json([
            'success' => true,
            'message' => 'Liste des motifs de traitement récupérée.',
            'code' => 'MOTIF_TRAITEMENTS_LISTED',
            'data' => $motifs->items(),
            'meta' => [
                'current_page' => $motifs->currentPage(),
                'per_page' => $motifs->perPage(),
                'total' => $motifs->total(),
                'last_page' => $motifs->lastPage(),
            ],
        ]);
    }

    public function actives(): JsonResponse
    {
        $motifs = $this->motifTraitementService->getActifs();

        return response()->json([
            'success' => true,
            'message' => 'Motifs de traitement actifs récupérés.',
            'code' => 'MOTIF_TRAITEMENTS_ACTIVE_LISTED',
            'data' => $motifs,
        ]);
    }

    public function suggestedTypes(): JsonResponse
    {
        $types = $this->motifTraitementService->getSuggestedTypes();

        return response()->json([
            'success' => true,
            'message' => 'Types de motifs de traitement suggérés.',
            'code' => 'MOTIF_TRAITEMENT_TYPES_SUGGESTED',
            'data' => $types,
        ]);
    }

    public function show(string $uuid): JsonResponse
    {
        $motif = $this->motifTraitementService->getByUuid($uuid);

        return response()->json([
            'success' => true,
            'message' => 'Détails du motif de traitement.',
            'code' => 'MOTIF_TRAITEMENT_FOUND',
            'data' => $motif,
        ]);
    }

    public function store(StoreMotifTraitementRequest $request): JsonResponse
    {
        $motif = $this->motifTraitementService->create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Motif de traitement créé avec succès.',
            'code' => 'MOTIF_TRAITEMENT_CREATED',
            'data' => $motif,
        ], 201);
    }

    public function update(UpdateMotifTraitementRequest $request, string $uuid): JsonResponse
    {
        $motif = $this->motifTraitementService->update($uuid, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Motif de traitement mis à jour avec succès.',
            'code' => 'MOTIF_TRAITEMENT_UPDATED',
            'data' => $motif,
        ]);
    }

    public function toggle(string $uuid): JsonResponse
    {
        $motif = $this->motifTraitementService->toggleStatus($uuid);

        return response()->json([
            'success' => true,
            'message' => 'Statut du motif de traitement mis à jour.',
            'code' => 'MOTIF_TRAITEMENT_TOGGLED',
            'data' => $motif,
        ]);
    }

    public function destroy(string $uuid): JsonResponse
    {
        $deleted = $this->motifTraitementService->delete($uuid);

        if (!$deleted) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de supprimer ce motif de traitement.',
                'code' => 'MOTIF_TRAITEMENT_DELETE_FAILED',
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Motif de traitement supprimé avec succès.',
            'code' => 'MOTIF_TRAITEMENT_DELETED',
        ]);
    }
}
