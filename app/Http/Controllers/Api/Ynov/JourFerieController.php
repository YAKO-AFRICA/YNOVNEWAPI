<?php
// app/Http/Controllers/Api/Ynov/JourFerieController.php

namespace App\Http\Controllers\Api\Ynov;

use App\Http\Controllers\Controller;
use App\Models\Api\Ynov\parameter\JourFerie;
use App\Services\Api\Ynov\JourFerieService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class JourFerieController extends Controller
{
    public function __construct(
        private JourFerieService $jourFerieService
    ) {}

    /**
     * Liste des jours fériés
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['year', 'est_recurrent', 'search']);
        $perPage = $request->integer('per_page', 20);

        $feries = $this->jourFerieService->getFeries($filters, $perPage);

        return response()->json([
            'success' => true,
            'message' => 'Liste des jours fériés récupérée.',
            'code' => 'FERIES_LISTED',
            'data' => $feries,
            'meta' => [
                'current_page' => $feries->currentPage(),
                'per_page' => $feries->perPage(),
                'total' => $feries->total(),
                'last_page' => $feries->lastPage(),
            ]
        ]);
    }

    /**
     * Créer un jour férié
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'date' => ['required', 'date', 'unique:jour_feries,date'],
                'libelle' => ['required', 'string', 'max:255'],
                'est_recurrent' => ['nullable', 'boolean'],
                'code' => ['nullable', 'string', 'max:50'],
                'description' => ['nullable', 'string'],
            ]);

            $ferie = $this->jourFerieService->create(
                $validated,
                $request->user()->uuid_user
            );

            return response()->json([
                'success' => true,
                'message' => 'Jour férié créé avec succès.',
                'code' => 'FERIE_CREATED',
                'data' => $ferie,
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
     * Détails d'un jour férié
     */
    public function show(string $uuid_jour_ferie): JsonResponse
    {
        $ferie = JourFerie::where('uuid_jour_ferie', $uuid_jour_ferie)->firstOrFail();

        return response()->json([
            'success' => true,
            'message' => 'Détails du jour férié.',
            'code' => 'FERIE_FOUND',
            'data' => $ferie,
        ]);
    }

    /**
     * Mettre à jour un jour férié
     */
    public function update(Request $request, string $uuid_jour_ferie): JsonResponse
    {
        try {
            $ferie = JourFerie::where('uuid_jour_ferie', $uuid_jour_ferie)->firstOrFail();

            $validated = $request->validate([
                'date' => ['nullable', 'date', 'unique:jour_feries,date,' . $ferie->id],
                'libelle' => ['nullable', 'string', 'max:255'],
                'est_recurrent' => ['nullable', 'boolean'],
                'code' => ['nullable', 'string', 'max:50'],
                'description' => ['nullable', 'string'],
            ]);

            $updated = $this->jourFerieService->update(
                $ferie,
                $validated,
                $request->user()->uuid_user
            );

            return response()->json([
                'success' => true,
                'message' => 'Jour férié mis à jour avec succès.',
                'code' => 'FERIE_UPDATED',
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
     * Supprimer un jour férié
     */
    public function destroy(Request $request, string $uuid_jour_ferie): JsonResponse
    {
        $ferie = JourFerie::where('uuid_jour_ferie', $uuid_jour_ferie)->firstOrFail();

        $this->jourFerieService->delete($ferie, $request->user()->uuid_user);

        return response()->json([
            'success' => true,
            'message' => 'Jour férié supprimé avec succès.',
            'code' => 'FERIE_DELETED',
        ]);
    }

    /**
     * Vérifier si une date est un jour férié
     */
    public function verifier(Request $request): JsonResponse
    {
        $request->validate([
            'date' => ['required', 'date'],
        ]);

        $date = $request->date;
        $isFerie = $this->jourFerieService->isFerie($date);

        return response()->json([
            'success' => true,
            'message' => $isFerie ? 'Cette date est un jour férié.' : 'Cette date n\'est pas un jour férié.',
            'code' => $isFerie ? 'DATE_FERIE' : 'DATE_NON_FERIE',
            'data' => [
                'date' => $date,
                'est_ferie' => $isFerie,
            ],
        ]);
    }

    /**
     * Récupérer les jours fériés d'une année
     */
    // public function annee(Request $request): JsonResponse
    // {
    //     $request->validate([
    //         'year' => ['required', 'integer', 'min:2000', 'max:2100'],
    //     ]);

    //     $feries = $this->jourFerieService->getFeriesForYear($request->year);

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Jours fériés de l\'année.',
    //         'code' => 'FERIES_YEAR',
    //         'data' => $feries,
    //     ]);
    // }

    /**
     * Récupérer les jours fériés d'une année
     */
    public function annee(Request $request, int $year): JsonResponse
    {
        // Validation de l'année
        if ($year < 2000 || $year > 2100) {
            return response()->json([
                'success' => false,
                'message' => 'L\'année doit être comprise entre 2000 et 2100.',
                'code' => 'INVALID_YEAR',
            ], 422);
        }

        $feries = $this->jourFerieService->getFeriesForYear($year);

        return response()->json([
            'success' => true,
            'message' => 'Jours fériés de l\'année ' . $year . '.',
            'code' => 'FERIES_YEAR',
            'data' => $feries,
        ]);
    }

    /**
     * Récupérer les prochains jours ouvrés
     */
    public function prochainsJoursOuvres(Request $request): JsonResponse
    {
        $request->validate([
            'nb_jours' => ['nullable', 'integer', 'min:1', 'max:365'],
            'date_debut' => ['nullable', 'date'],
        ]);

        $nbJours = $request->integer('nb_jours', 30);
        $dateDebut = $request->date_debut ?? Carbon::today()->format('Y-m-d');

        $jours = $this->jourFerieService->getProchainsJoursOuvres($nbJours, $dateDebut);

        return response()->json([
            'success' => true,
            'message' => 'Prochains jours ouvrés.',
            'code' => 'PROCHAINS_JOURS_OUVRES',
            'data' => $jours,
        ]);
    }

    /**
     * Statistiques des jours fériés
     */
    public function stats(): JsonResponse
    {
        $stats = $this->jourFerieService->getStats();

        return response()->json([
            'success' => true,
            'message' => 'Statistiques des jours fériés.',
            'code' => 'FERIES_STATS',
            'data' => $stats,
        ]);
    }
}