<?php

namespace App\Http\Controllers\Api\Ynov;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Ynov\StoreMotifTraitementRequest;
use App\Http\Requests\Api\Ynov\UpdateMotifTraitementRequest;
use App\Models\Api\Ynov\parameter\ActivityLog;
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

        ActivityLog::log([
            'user_uuid' => $request->user()->uuid_user,
            'action' => 'create',
            'action_type' => 'crud',
            'module' => 'motif_traitements',
            'description' => "Création du motif de traitement : {$motif->libelle}",
            'resource_type' => 'motif_traitement',
            'resource_id' => $motif->uuid_motif_traitements,
            'new_values' => $motif->toArray(),
            'level' => 'info',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Motif de traitement créé avec succès.',
            'code' => 'MOTIF_TRAITEMENT_CREATED',
            'data' => $motif,
        ], 201);
    }

    public function update(UpdateMotifTraitementRequest $request, string $uuid): JsonResponse
    {
        $oldValues = $this->motifTraitementService->getByUuid($uuid)->toArray();
        $motif = $this->motifTraitementService->update($uuid, $request->validated());

        ActivityLog::log([
            'user_uuid' => $request->user()->uuid_user,
            'action' => 'update',
            'action_type' => 'crud',
            'module' => 'motif_traitements',
            'description' => "Mise à jour du motif de traitement : {$motif->libelle}",
            'resource_type' => 'motif_traitement',
            'resource_id' => $motif->uuid_motif_traitements,
            'old_values' => $oldValues,
            'new_values' => $motif->toArray(),
            'level' => 'info',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Motif de traitement mis à jour avec succès.',
            'code' => 'MOTIF_TRAITEMENT_UPDATED',
            'data' => $motif,
        ]);
    }

    public function toggle(Request $request, string $uuid): JsonResponse
    {
        $oldValues = $this->motifTraitementService->getByUuid($uuid)->toArray();
        $motif = $this->motifTraitementService->toggleStatus($uuid);

        ActivityLog::log([
            'user_uuid' => $request->user()->uuid_user,
            'action' => 'toggle_status',
            'action_type' => 'crud',
            'module' => 'motif_traitements',
            'description' => "Changement du statut du motif : {$motif->libelle}",
            'resource_type' => 'motif_traitement',
            'resource_id' => $motif->uuid_motif_traitements,
            'old_values' => $oldValues,
            'new_values' => $motif->toArray(),
            'level' => 'info',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Statut du motif de traitement mis à jour.',
            'code' => 'MOTIF_TRAITEMENT_TOGGLED',
            'data' => $motif,
        ]);
    }

    public function destroy(Request $request, string $uuid): JsonResponse
    {
        $motif = $this->motifTraitementService->getByUuid($uuid);
        $oldValues = $motif->toArray();
        $deleted = $this->motifTraitementService->delete($uuid);

        if (!$deleted) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de supprimer ce motif de traitement.',
                'code' => 'MOTIF_TRAITEMENT_DELETE_FAILED',
            ], 400);
        }

        ActivityLog::log([
            'user_uuid' => $request->user()->uuid_user,
            'action' => 'delete',
            'action_type' => 'crud',
            'module' => 'motif_traitements',
            'description' => "Suppression du motif de traitement : {$motif->libelle}",
            'resource_type' => 'motif_traitement',
            'resource_id' => $motif->uuid_motif_traitements,
            'old_values' => $oldValues,
            'level' => 'warning',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Motif de traitement supprimé avec succès.',
            'code' => 'MOTIF_TRAITEMENT_DELETED',
        ]);
    }
}
