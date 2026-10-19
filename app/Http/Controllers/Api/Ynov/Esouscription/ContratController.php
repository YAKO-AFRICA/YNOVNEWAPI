<?php

namespace App\Http\Controllers\Api\Ynov\Esouscription;

use App\Http\Controllers\Controller;
use App\Services\Api\Ynov\ESouscription\ContratService;
use Illuminate\Http\Request;
use Throwable;

class ContratController extends Controller
{
    public function __construct(private ContratService $contratService)
    {
    }

    public function index(Request $request)
    {
        try {
            $contrats = $this->contratService->getAll(
                $request->only([
                    'uuid_contrat',
                    'agence_uuid',
                    'code_produit',
                    'libelle_produit',
                    'code_proposition',
                    'numero_police',
                    'partner_uuid',
                    'conseiller_uuid',
                    'mode_paiement',
                    'etape',
                    'search',
                    'etat',
                ]),
                (int) $request->input('per_page', 15)
                );



            return response()->json([
                'success' => true,
                'message' => 'Contrats récupérés avec succès',
                'code' => 200,
                'total' => $contrats->total(),
                'data' => $contrats,
            ]);
        } catch (Throwable $e) {
            return $this->errorResponse('Erreur lors de la récupération des contrats', $e);
        }
    }

    public function dashboard(Request $request)
    {
        $dateFinRules = ['sometimes', 'nullable', 'date'];
        if ($request->filled('date_debut')) {
            $dateFinRules[] = 'after_or_equal:date_debut';
        }

        $filters = $request->validate([
            'date_debut' => ['sometimes', 'nullable', 'date'],
            'date_fin' => $dateFinRules,
            'agence_uuid' => ['sometimes', 'nullable', 'string'],
            'reseau_uuid' => ['sometimes', 'nullable', 'string'],
            'partner_uuid' => ['sometimes', 'nullable', 'string'],
            'code_produit' => ['sometimes', 'nullable', 'string'],
            'granularite' => ['sometimes', 'string', 'in:mois,semaine,jour'],
        ]);

        try {
            return response()->json([
                'success' => true,
                'message' => 'Tableau de bord des contrats récupéré avec succès',
                'code' => 200,
                'data' => $this->contratService->getDashboard($filters),
            ]);
        } catch (Throwable $e) {
            return $this->errorResponse('Erreur lors de la récupération du tableau de bord des contrats', $e);
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules());

        try {
            $contrat = $this->contratService->create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Contrat créé avec succès',
                'code' => 201,
                'data' => $contrat,
            ], 201);
        } catch (Throwable $e) {
            return $this->errorResponse('Erreur lors de la création du contrat', $e);
        }
    }

    public function showContrat(string $uuid)
    {
        try {
            $contrat = $this->contratService->findByUuid($uuid);

            if (!$contrat) {
                return $this->notFoundResponse();
            }

            // S'assurer que la relation est chargée
            $contrat->loadMissing('contratActeurs');

            // Collection d'Eloquent
            $contratActeurs = $contrat->contratActeurs;

            // Groupement par type d'acteur
            $adherent      = $contratActeurs->firstWhere('type_acteur', 'ADH');
            $beneficiaires = $contratActeurs->where('type_acteur', 'BEN')->values();
            $assures       = $contratActeurs->where('type_acteur', 'ASS')->values();

            // On garde $contratActeurs groupé dans une variable dédiée
            $acteursGroupes = [
                'adherent'      => $adherent,
                'beneficiaires' => $beneficiaires,
                'assures'       => $assures,
            ];

            // Récupération des documents associés au contrat

            $documents = $contrat->documents()->get();

            return response()->json([
                'success' => true,
                'message' => 'Contrat récupéré avec succès',
                'code'    => 200,
                'data'    => [
                    'contrat'        => $contrat,
                    'contratActeurs' => $acteursGroupes,
                    'documents'      => $documents,
                ],
            ]);
        } catch (Throwable $e) {
            return $this->errorResponse('Erreur lors de la récupération du contrat', $e);
        }
    }

    public function update(Request $request, string $uuid)
    {
        $contrat = $this->contratService->findByUuid($uuid);

        if (!$contrat) {
            return $this->notFoundResponse();
        }

        $validated = $request->validate($this->rules(true));

        try {
            $contrat = $this->contratService->update($contrat, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Contrat mis à jour avec succès',
                'code' => 200,
                'data' => $contrat,
            ]);
        } catch (Throwable $e) {
            return $this->errorResponse('Erreur lors de la mise à jour du contrat', $e);
        }
    }

    public function transmit(Request $request, string $uuid)
    {
        $contrat = $this->contratService->findByUuid($uuid);

        if (!$contrat) {
            return $this->notFoundResponse();
        }

        try {
            $contrat = $this->contratService->transmit(
                $contrat,
                (string) $request->user()->uuid_user
            );

            return response()->json([
                'success' => true,
                'message' => 'Contrat transmis avec succès',
                'code' => 200,
                'data' => $contrat,
            ]);
        } catch (Throwable $e) {
            return $this->errorResponse('Erreur lors de la transmission du contrat', $e);
        }
    }

    public function cancel(Request $request, string $uuid)
    {
        $contrat = $this->contratService->findByUuid($uuid);

        if (!$contrat) {
            return $this->notFoundResponse();
        }

        try {
            $contrat = $this->contratService->cancel(
                $contrat,
                (string) $request->user()->uuid_user
            );

            return response()->json([
                'success' => true,
                'message' => 'Contrat annulé avec succès',
                'code' => 200,
                'data' => $contrat,
            ]);
        } catch (Throwable $e) {
            return $this->errorResponse('Erreur lors de l’annulation du contrat', $e);
        }
    }

    public function destroy(Request $request, string $uuid)
    {
        $contrat = $this->contratService->findWithTrashedByUuid($uuid);

        if (!$contrat) {
            return $this->notFoundResponse();
        }

        try {
            $force = $request->input('deleting') === 'full';
            $this->contratService->delete($contrat, $force, $request->input('deleted_by'));

            return response()->json([
                'success' => true,
                'message' => $force
                    ? 'Contrat supprimé définitivement'
                    : 'Contrat supprimé avec succès',
                'code' => 200,
                'data' => null,
            ]);
        } catch (Throwable $e) {
            return $this->errorResponse('Erreur lors de la suppression du contrat', $e);
        }
    }

    public function restore(string $uuid)
    {
        $contrat = $this->contratService->findTrashedByUuid($uuid);

        if (!$contrat) {
            return $this->notFoundResponse('Contrat supprimé introuvable');
        }

        try {
            $contrat = $this->contratService->restore($contrat);

            return response()->json([
                'success' => true,
                'message' => 'Contrat restauré avec succès',
                'code' => 200,
                'data' => $contrat,
            ]);
        } catch (Throwable $e) {
            return $this->errorResponse('Erreur lors de la restauration du contrat', $e);
        }
    }

    private function rules(bool $partial = false): array
    {
        $required = $partial ? 'sometimes|nullable' : 'required';

        return [
            'uuid_contrat' => 'sometimes|nullable|string|max:255',
            'date_effet' => $required . '|date',
            'mode_paiement' => $required . '|string|max:100',
            'organisme' => 'sometimes|nullable|string|max:255',
            'duree' => 'sometimes|nullable|integer',
            'code_periodicite' => 'sometimes|nullable|string|max:100',
            'prime' => 'sometimes|nullable|numeric',
            'prime_principale' => 'sometimes|nullable|numeric',
            'sur_prime' => 'sometimes|nullable|numeric',
            'capital' => 'sometimes|nullable|numeric',
            'frais_adhesion' => 'sometimes|nullable|numeric',
            'montant_rente' => 'sometimes|nullable|numeric',
            'periodicite_rente' => 'sometimes|nullable|string|max:100',
            'duree_rente' => 'sometimes|nullable|integer',
            'code_banque' => 'sometimes|nullable|string|max:100',
            'code_guichet' => 'sometimes|nullable|string|max:100',
            'rib' => 'sometimes|nullable|string|max:100',
            'numero_compte' => 'sometimes|nullable|string|max:100',
            'numecompte_complet' => 'sometimes|nullable|string|max:255',
            'agence_uuid' => 'sometimes|nullable|string|max:255',
            'code_produit' => 'sometimes|nullable|string|max:100',
            'libelle_produit' => 'sometimes|nullable|string|max:255',
            'formule_produit_code' => 'sometimes|nullable|string|max:100',
            'etape' => 'sometimes|nullable|integer',
            'is_migrated' => 'sometimes|nullable|boolean',
            'migration_date' => 'sometimes|nullable|date',
            'contact_personne_nom' => 'sometimes|nullable|string|max:255',
            'contact_personne_mobile' => 'sometimes|nullable|string|max:50',
            'contact_personne_nom_2' => 'sometimes|nullable|string|max:255',
            'contact_personne_mobile_2' => 'sometimes|nullable|string|max:50',
            'id_proposition' => 'sometimes|nullable|integer',
            'code_proposition' => 'sometimes|nullable|string|max:255',
            'numero_police' => 'sometimes|nullable|string|max:255',
            'branch_code' => 'sometimes|nullable|string|max:100',
            'partner_uuid' => 'sometimes|nullable|string|max:255',
            'conseiller_uuid' => 'sometimes|nullable|string|max:255',
            'transmis_par' => 'sometimes|nullable|string|max:255',
            'date_transmission' => 'sometimes|nullable|date',
            'annuler_par' => 'sometimes|nullable|string|max:255',
            'date_annulation' => 'sometimes|nullable|date',
            'rejeter_par' => 'sometimes|nullable|string|max:255',
            'date_rejet' => 'sometimes|nullable|date',
            'motif_rejet' => 'sometimes|nullable|array',
            'motif_rejet.*' => 'required|string',
            'acceptance_date' => 'sometimes|nullable|date',
            'accepted_by' => 'sometimes|nullable|string|max:255',
            'is_paid' => 'sometimes|nullable|boolean',
            'observation' => 'sometimes|nullable|string',
            'bulletin_num' => 'sometimes|nullable|string|max:255',
            'formule' => 'sometimes|nullable|string|max:255',
            'integration_key' => 'sometimes|nullable|string|max:255',
            'created_by' => 'sometimes|nullable|string|max:255',
            'updated_by' => 'sometimes|nullable|string|max:255',
            'deleted_by' => 'sometimes|nullable|string|max:255',
        ];
    }

    private function notFoundResponse(string $message = 'Contrat introuvable')
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'code' => 404,
        ], 404);
    }

    private function errorResponse(string $message, Throwable $exception)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'code' => 500,
            'error' => $exception->getMessage(),
        ], 500);
    }
}
