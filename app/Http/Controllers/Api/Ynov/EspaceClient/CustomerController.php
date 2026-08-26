<?php

namespace App\Http\Controllers\Api\Ynov\EspaceClient;

use App\Http\Controllers\Controller;
use App\Models\Api\Ynov\parameter\User;
use App\Services\EncaissementBisService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CustomerController extends Controller
{
    public function __construct(
        private EncaissementBisService $encaissementBisService
    ) {}

    public function index(Request $request)
    {
        return response()->json(['type' => 'success']);
    }

    /**
     * Récupérer tous les contrats du client
     * 
     */
    public function getAllContrat(Request $request): JsonResponse
    {
        try {
            $user = $request->user()->load('userContrats');

            if ($user->userContrats->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'code' => 'NO_CONTRAT_FOUND',
                    'message' => 'Aucun contrat trouvé pour ce client.',
                    'data' => [],
                    'meta' => [
                        'total' => 0,
                        'per_page' => (int) $request->get('per_page', 10),
                        'current_page' => 1,
                        'last_page' => 1,
                    ],
                ], 200);
            }

            $errors = [];
            $allContrats = [];

            foreach ($user->userContrats as $userContrat) {
                try {
                    // Récupérer les données du contrat
                    $result = $this->encaissementBisService->getContrat($userContrat->contrat_id);

                    if (!$result['success']) {
                        $errors[] = [
                            'contrat_id' => $userContrat->contrat_id,
                            'message' => $result['message'] ?? 'Erreur de récupération'
                        ];
                        continue;
                    }

                    $data = $result['data'];

                    // Vérifier si le contrat existe et a des détails
                    if (!isset($data['details'][0])) {
                        $errors[] = [
                            'contrat_id' => $userContrat->contrat_id,
                            'message' => 'Contrat sans détails'
                        ];
                        continue;
                    }

                    $detail = $data['details'][0];

                    // Filtrer les contrats arrêtés (OnStdbyOff = 3)
                    if (isset($detail['OnStdbyOff']) && $detail['OnStdbyOff'] == "3") {
                        continue;
                    }

                    // Construire les données du contrat
                    $contratData = [
                        'IdProposition' => $detail['IdProposition'] ?? null,
                        'CapitalSouscrit' => $detail['CapitalSouscrit'] ?? 0,
                        'TotalPrime' => $detail['TotalPrime'] ?? 0,
                        'NbreImpayes' => $detail['NbreImpayes'] ?? 0,
                        'produit' => $detail['produit'] ?? $userContrat->libelle_produit ?? 'Non défini',
                        'EtatAvancementCotisation' => $detail['EtatAvancementCotisation'] ?? null,
                    ];

                    $allContrats[] = $contratData;
                } catch (\Exception $e) {
                    Log::error('Erreur récupération contrat', [
                        'contrat_id' => $userContrat->contrat_id,
                        'error' => $e->getMessage()
                    ]);

                    $errors[] = [
                        'contrat_id' => $userContrat->contrat_id,
                        'message' => 'Erreur technique lors de la récupération'
                    ];
                }
            }

            // ============================================================
            // PAGINATION
            // ============================================================
            $perPage = (int) $request->get('per_page', 10);
            $page = (int) $request->get('page', 1);

            // Valider les paramètres
            $perPage = max(1, min(100, $perPage)); // Entre 1 et 100
            $page = max(1, $page);

            // Trier les contrats par date de création (plus récent d'abord)
            $sortedContrats = collect($allContrats)->sortByDesc('created_at')->values()->toArray();

            // Paginer manuellement
            $total = count($sortedContrats);
            $lastPage = ceil($total / $perPage);
            $offset = ($page - 1) * $perPage;
            $paginatedData = array_slice($sortedContrats, $offset, $perPage);

            return response()->json([
                'success' => true,
                'code' => !empty($paginatedData) ? 'GET_ALL_CONTRAT_SUCCESS' : 'NO_CONTRAT_FOUND',
                'message' => !empty($paginatedData)
                    ? 'Contrats récupérés avec succès.'
                    : 'Aucun contrat actif trouvé.',
                'data' => $paginatedData,
                'meta' => [
                    'total' => $total,
                    'per_page' => $perPage,
                    'current_page' => $page,
                    'last_page' => $lastPage,
                    'has_errors' => !empty($errors),
                    'errors' => $errors,
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Erreur getAllContrat', [
                'user_uuid' => $request->user()->uuid_user ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'code' => 'GET_CONTRAT_ERROR',
                'message' => 'Une erreur est survenue lors de la récupération des contrats.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 422);
        }
    }


    public function getContratDetails($contrat_id)
    {
        try {
                $result = $this->encaissementBisService->getContrat($contrat_id);
                // filtrer uniquement les contrats actifs ou suspendus (OnStdbyOff != 3)
                $data = $result['data'];
                $detail = $data['details'][0];
                $assures = collect($data['allActeur'] ?? [])
                ->where('CodeRole', 'ASS')
                ->values()
                ->map(function ($assure) {
                    return [
                        'CodePersonne' => $assure['CodePersonne'] ?? null,
                        'Nom' => $assure['nomAssu'] ?? null,
                        'Prenoms' => $assure['PrenomAssu'] ?? null,
                        'NomComplet' => trim(
                            ($assure['nomAssu'] ?? '') . ' ' .
                            ($assure['PrenomAssu'] ?? '')
                        ),
                        'DateNaissance' => !empty($assure['DateNaissanceAssu'])
                            ? $assure['DateNaissanceAssu']
                            : null,
                        'LieuNaissance' => $assure['LieuNaissanceAssu'] ?? null,
                        'Profession' => $assure['ProfessionAssu'] ?? null,
                        'CodeFiliation' => $assure['CodeFiliation'] ?? null,
                        'Filiation' => $assure['MonLibelle'] ?? null,
                    ];
                })
                ->toArray();

                $beneficiaires = collect($data['allActeur'] ?? [])
                ->where('CodeRole', 'BEN')
                ->values()
                ->map(function ($beneficiaire) {
                    return [
                        'CodePersonne' => $beneficiaire['CodePersonne'] ?? null,
                        'Nom' => $beneficiaire['nomAssu'] ?? null,
                        'Prenoms' => $beneficiaire['PrenomAssu'] ?? null,
                        'NomComplet' => trim(
                            ($beneficiaire['nomAssu'] ?? '') . ' ' .
                            ($beneficiaire['PrenomAssu'] ?? '')
                        ),
                        'DateNaissance' => !empty($beneficiaire['DateNaissanceAssu'])
                            ? $beneficiaire['DateNaissanceAssu']
                            : null,
                        'LieuNaissance' => $beneficiaire['LieuNaissanceAssu'] ?? null,
                        'Profession' => $beneficiaire['ProfessionAssu'] ?? null,
                        'CodeFiliation' => $beneficiaire['CodeFiliation'] ?? null,
                        'Filiation' => $beneficiaire['MonLibelle'] ?? null,
                    ];
                })
                ->toArray();

                $contratData = [
                    'details' => [
                        'IdProposition' => $detail['IdProposition'] ?? null,
                        'NumBulletin' => $detail['CodepropositionForm'] ?? null,
                        'CodeProposition' => $detail['CodeProposition'] ?? null,
                        'CapitalSouscrit' => $detail['CapitalSouscrit'] ?? 0,
                        'TotalPrime' => $detail['TotalPrime'] ?? 0,
                        'NbreImpayes' => $detail['NbreImpayes'] ?? 0,
                        'produit' => $detail['produit'] ?? $userContrat->libelle_produit ?? 'Non défini',
                        'EtatAvancementCotisation' => $detail['EtatAvancementCotisation'] ?? null,
                        'Periodicite' => $detail['LibellePeriodicite'] ?? $detail['periodicite'] ?? null,
                        'ModePaiement' => $detail['LibelleModePaiement'] ?? $detail['CodeModePaiement'] ?? null,
                        'DateFinAdhesion' => (isset($detail['FinAdhesion']) && $detail['FinAdhesion'] != null) ? Carbon::parse($detail['FinAdhesion'])->format('d/m/Y') : $detail['FinAdhesion'] ?? null,
                        'DateEffetAdhesion' => !empty($detail['DateEffetReel']) ? Carbon::parse($detail['DateEffetReel'])->format('d/m/Y') : (!empty($detail['DateEffetSouhaite']) ? Carbon::parse($detail['DateEffetSouhaite'])->format('d/m/Y') : null ),
                        'Conseiller' => $detail['CodeConseiller'] . '-'. $detail['NomAgent']  ?? null,
                        'Adherent' => $detail['nomSous'] . ' '. $detail['prenomSous']  ?? null,
                        'Status' => $this->getContractStatus($detail)
                    ],
                    'Assures' => $assures,
                    'Beneficiaires' => $beneficiaires,
                    'Garanties' => $data['garanties'] ,
                    'Documents' => $data['documents'],
                    'anciennete' => $detail['anciennete']
                ];

                    
               
                if (!$result['success']) {
                    return response()->json([
                        'success' => false,
                        'code' => $result['code'],
                        'message' => $result['message'],
                    ], 422);
                }

            return response()->json([
                'success' => true,
                'code' => count($contratData) > 0 ? 'GET_ALL_CONTRAT_SUCCESS' : 'NO_CONTRAT_FOUND',
                'message' => count($contratData) > 0 ? 'Contrats obtenus avec successe.' : 'Aucun contrat trouvé.',
                'data' => $contratData,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }




    /**
     * Calculer le taux de paiement
     */
    /**
     * Obtenir le statut du contrat
     */
    private function getContractStatus(array $detail): string
    {
        $status = $detail['OnStdbyOff'] ?? null;

        $statusMap = [
            '1' => 'En cours',
            '2' => 'Suspendu',
            '3' => 'arrete',
        ];

        return $statusMap[$status] ?? 'inconnu';
    }
}
