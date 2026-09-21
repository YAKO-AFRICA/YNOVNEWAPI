<?php

namespace App\Http\Controllers\Api\Ynov\Esouscription;

use App\Http\Controllers\Controller;
use App\Models\Api\Ynov\Esouscription\DeclarationSante;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SanteController extends Controller
{


    public function getSanteData(Request $request)
    {
        try {
            $query = DeclarationSante::query();

            // Filtrer par contrat
            if ($request->filled('contrat_uuid')) {
                $query->where('contrat_uuid', $request->contrat_uuid);
            }

            // Filtrer par assuré
            if ($request->filled('assure_uuid')) {
                $query->where('assure_uuid', $request->assure_uuid);
            }

            $donneesSante = $query
                ->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'message' => 'Données de santé récupérées avec succès.',
                'data' => $donneesSante
            ], 200);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des données de santé.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Enregistrer les données de santé.
     */
    public function storeSante(Request $request)
    {

        Log::info('Donnees de sante enregistré');
        Log::info($request->all());



        $validated = $request->validate([

            // Clés étrangères
            'contrat_uuid' => 'required|string|max:255',
            'assure_uuid' => 'required|string|max:255',

            // Données de santé
            'taille' => 'nullable|numeric|min:0',
            'poids' => 'nullable|numeric|min:0',
            'tension_min' => 'nullable|numeric|min:0',
            'tension_max' => 'nullable|numeric|min:0',

            // Habitudes de vie
            'tabagisme' => 'nullable|string|max:255',
            'alcool' => 'nullable|string|max:255',
            'sport' => 'nullable|string|max:255',

            // Antécédents médicaux
            'accident' => 'nullable|string|max:255',
            'traitement' => 'nullable|string|max:255',
            'transfusion_sanguine' => 'nullable|string|max:255',
            'intervention_chirurgicale' => 'nullable|string|max:255',
            'prochaine_intervention_chirurgicale' => 'nullable|string|max:255',

            // Maladies chroniques
            'diabete' => 'nullable|string|max:255',
            'hypertension' => 'nullable|string|max:255',
            'drepanocytose' => 'nullable|string|max:255',
            'cirrhose_foie' => 'nullable|string|max:255',
            'maladie_pulmonaire' => 'nullable|string|max:255',
            'cancer' => 'nullable|string|max:255',
            'anemie' => 'nullable|string|max:255',
            'insuffisance_renale' => 'nullable|string|max:255',
            'avc' => 'nullable|string|max:255',
            'created_by' => 'nullable|string|max:255',
        ]);

        Log::info('Donnees de sante validate');
        Log::info($validated);

        try {

            DB::beginTransaction();

            $donneesSante = DeclarationSante::create([
                'contrat_uuid' => $validated['contrat_uuid'],
                'assure_uuid' => $validated['assure_uuid'],
                'taille' => $validated['taille'],             
                'poids' => $validated['poids'],
                'tension_min' => $validated['tension_min'],
                'tension_max' => $validated['tension_max'],
                'tabagisme' => $validated['tabagisme'],
                'alcool' => $validated['alcool'],
                'sport' => $validated['sport'],
                'accident' => $validated['accident'],
                'traitement' => $validated['traitement'],
                'transfusion_sanguine' => $validated['transfusion_sanguine'],
                'intervention_chirurgicale' => $validated['intervention_chirurgicale'],
                'prochaine_intervention_chirurgicale' => $validated['prochaine_intervention_chirurgicale'],
                'diabete' => $validated['diabete'],
                'hypertension' => $validated['hypertension'],
                'drepanocytose' => $validated['drepanocytose'],
                'cirrhose_foie' => $validated['cirrhose_foie'],
                'maladie_pulmonaire' => $validated['maladie_pulmonaire'],
                'cancer' => $validated['cancer'],
                'anemie' => $validated['anemie'],
                'insuffisance_renale' => $validated['insuffisance_renale'],
                'avc' => $validated['avc'],
                'created_by' => $validated['created_by'],
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Données de santé enregistrées avec succès.',
                'data' => $donneesSante
            ], 201);

        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l’enregistrement des données de santé.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function showSante($uuid)
    {
        try {

            $donneesSante = DeclarationSante::find($uuid);

            if (!$donneesSante) {
                return response()->json([
                    'success' => false,
                    'message' => 'Données de santé introuvables.'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Données de santé récupérées avec succès.',
                'data' => $donneesSante
            ], 200);

        } catch (\Throwable $e) {

        Log::error($e);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des données de santé.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateSante(Request $request, $uuid)
    {

        $validated = $request->validate([

            // Clés étrangères
            'contrat_uuid' => 'sometimes|required|string|max:255',
            'assure_uuid' => 'sometimes|required|string|max:255',

            // Données de santé
            'taille' => 'nullable|numeric|min:0',
            'poids' => 'nullable|numeric|min:0',
            'tension_min' => 'nullable|numeric|min:0',
            'tension_max' => 'nullable|numeric|min:0',

            // Habitudes de vie
            'tabagisme' => 'nullable|string|max:255',
            'alcool' => 'nullable|string|max:255',
            'sport' => 'nullable|string|max:255',

            // Antécédents médicaux
            'accident' => 'nullable|string|max:255',
            'traitement' => 'nullable|string|max:255',
            'transfusion_sanguine' => 'nullable|string|max:255',
            'intervention_chirurgicale' => 'nullable|string|max:255',
            'prochaine_intervention_chirurgicale' => 'nullable|string|max:255',

            // Maladies chroniques
            'diabete' => 'nullable|string|max:255',
            'hypertension' => 'nullable|string|max:255',
            'drepanocytose' => 'nullable|string|max:255',
            'cirrhose_foie' => 'nullable|string|max:255',
            'maladie_pulmonaire' => 'nullable|string|max:255',
            'cancer' => 'nullable|string|max:255',
            'anemie' => 'nullable|string|max:255',
            'insuffisance_renale' => 'nullable|string|max:255',
            'avc' => 'nullable|string|max:255',

            'update_by' => 'nullable|string|max:255',
            'updated_at' => 'nullable|string|max:255',
        ]);

        Log::info('updateSante request: ' . json_encode($validated));

        try {

            DB::beginTransaction();

            $donneesSante = DeclarationSante::find($uuid);

            Log::info("donnée trouver avec uuid: " . $uuid);
            Log::info($donneesSante);

            if (!$donneesSante) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Données de santé introuvables.'
                ], 404);
            }


            $donneesSante->update(
                [
                    'taille' => $validated['taille'] ?? $donneesSante->taille,
                    'poids' => $validated['poids'] ?? $donneesSante->poids,
                    'tension_min' => $validated['tension_min'] ?? $donneesSante->tension_min,
                    'tension_max' => $validated['tension_max'] ?? $donneesSante->tension_max,
                    'tabagisme' => $validated['tabagisme'] ?? $donneesSante->tabagisme,
                    'alcool' => $validated['alcool'] ?? $donneesSante->alcool,
                    'sport' => $validated['sport'] ?? $donneesSante->sport,
                    'accident' => $validated['accident'] ?? $donneesSante->accident,
                    'traitement' => $validated['traitement'] ?? $donneesSante->traitement,
                    'transfusion_sanguine' => $validated['transfusion_sanguine'] ?? $donneesSante->transfusion_sanguine,
                    'intervention_chirurgicale' => $validated['intervention_chirurgicale'] ?? $donneesSante->intervention_chirurgicale,
                    'prochaine_intervention_chirurgicale' => $validated['prochaine_intervention_chirurgicale'] ?? $donneesSante->prochaine_intervention_chirurgicale,
                    'diabete' => $validated['diabete'] ?? $donneesSante->diabete,
                    'hypertension' => $validated['hypertension'] ?? $donneesSante->hypertension,
                    'drepanocytose' => $validated['drepanocytose'] ?? $donneesSante->drepanocytose,
                    'cirrhose_foie' => $validated['cirrhose_foie'] ?? $donneesSante->cirrhose_foie,
                    'maladie_pulmonaire' => $validated['maladie_pulmonaire'] ?? $donneesSante->maladie_pulmonaire,
                    'cancer' => $validated['cancer'] ?? $donneesSante->cancer,
                    'anemie' => $validated['anemie'] ?? $donneesSante->anemie,
                    'insuffisance_renale' => $validated['insuffisance_renale'] ?? $donneesSante->insuffisance_renale,
                    'avc' => $validated['avc'] ?? $donneesSante->avc,
                    'update_by' => $validated['update_by'] ?? $donneesSante->update_by,
                    'updated_at' => now()
                ]
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Données de santé mises à jour avec succès.',
                'data' => $donneesSante->fresh()
            ], 200);

        } catch (\Throwable $e) {

            Log::error($e);

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la modification des données de santé.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroySante(Request $request, $uuid)
    {
        try {
            $donneesSante = DeclarationSante::find($uuid);

            if (!$donneesSante) {
                return response()->json([
                    'success' => false,
                    'message' => 'Données de santé introuvables.'
                ], 404);
            }

            if ($request->input('deleting') === 'full') {
                $donneesSante->delete();

                return response()->json([
                    'success' => true,
                    'message' => 'Données de santé supprimées avec succès.'
                ], 200);
            }
                
                
            $donneesSante->update(
                [
                    'deleted_at' => now(),
                    'deleted_by' => $request->input('deleted_by')
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Données de santé supprimées avec succès.'
            ], 200);

        } catch (\Throwable $e) {
            Log::error($e);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression des données de santé.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function restoreSante(Request $request, $uuid) {
        try {
            $donneesSante = DeclarationSante::withTrashed()->find($uuid);

            if (!$donneesSante) {
                return response()->json([
                    'success' => false,
                    'message' => 'Données de santé introuvables.'
                ], 404);
            }

            $donneesSante->restore();

            return response()->json([
                'success' => true,
                'message' => 'Données de santé restaurées avec succès.'
            ], 200);

        } catch (\Throwable $e) {
            Log::error($e);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la restauration des données de santé.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getTrashedSante() {
        try {
            $donneesSante = DeclarationSante::onlyTrashed()->get();

            if (!$donneesSante) {
                return response()->json([
                    'success' => false,
                    'message' => 'Données de santé introuvables.'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Données de santé restaurées avec succès.',
                'data' => $donneesSante
            ], 200);

        } catch (\Throwable $e) {
            Log::error($e);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la restauration des données de santé.',
                'error' => $e->getMessage()
            ], 500);
        }
    }






}




