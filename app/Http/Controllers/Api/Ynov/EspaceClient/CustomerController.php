<?php

namespace App\Http\Controllers\Api\Ynov\EspaceClient;

use App\Http\Controllers\Controller;
use App\Models\Api\Ynov\parameter\GroupNotif;
use App\Models\Api\Ynov\parameter\Notification;
use App\Models\Api\Ynov\parameter\UserDetails;
use App\Models\Api\Ynov\UserContrat;
use App\Services\Api\Ynov\NotificationService;
use App\Services\EncaissementBisService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CustomerController extends Controller
{
    public function __construct(
        private EncaissementBisService $encaissementBisService,
        private NotificationService $notificationService,
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
                        'codeProduit' => $detail['codeProduit'] ?? $userContrat->code_produit ?? 'Non défini',
                        'EtatAvancementCotisation' => $detail['EtatAvancementCotisation'] ?? null,
                    ];

                    $allContrats[] = $contratData;
                } catch (\Exception $e) {
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
                    'codeProduit' => $detail['codeProduit'] ?? $userContrat->code_produit ?? null,
                    'EtatAvancementCotisation' => $detail['EtatAvancementCotisation'] ?? null,
                    'Periodicite' => $detail['LibellePeriodicite'] ?? $detail['periodicite'] ?? null,
                    'ModePaiement' => $detail['LibelleModePaiement'] ?? $detail['CodeModePaiement'] ?? null,
                    'DateFinAdhesion' => (isset($detail['FinAdhesion']) && $detail['FinAdhesion'] != null) ? Carbon::parse($detail['FinAdhesion'])->format('d/m/Y') : $detail['FinAdhesion'] ?? null,
                    'DateEffetAdhesion' => !empty($detail['DateEffetReel']) ? Carbon::parse($detail['DateEffetReel'])->format('d/m/Y') : (!empty($detail['DateEffetSouhaite']) ? Carbon::parse($detail['DateEffetSouhaite'])->format('d/m/Y') : null),
                    'Conseiller' => $detail['CodeConseiller'] . '-' . $detail['NomAgent']  ?? null,
                    'Adherent' => $detail['nomSous'] . ' ' . $detail['PrenomSous']  ?? null,
                    'Status' => $this->getContractStatus($detail)
                ],
                'Assures' => $assures,
                'Beneficiaires' => $beneficiaires,
                'Garanties' => $data['garanties'],
                'Documents' => $data['documents'],
                'anciennete' => $data['anciennete']
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

    public function getContratEtatCotisation($contrat_id)
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
                })->toArray();

            $payeur = collect($data['payeur'] ?? [])
                ->where('CodeRole', 'PAY')
                ->values()
                ->map(function ($payeur) {
                    return [
                        'CodePersonne' => $payeur['CodePersonne'] ?? null,
                        'NomPrenom' => trim($payeur['NomPrenom'] ?? ''),
                        'ModePaiement' =>  $payeur['CodeModePaiement'] ?? null,
                        'Organisme' => $payeur['Societe'] ?? null,
                        'NumCompte' => $payeur['NumCompte']  ?? null,
                    ];
                })
                ->toArray();

            $AssuresGaranties = collect($data['assures'] ?? [])
                ->where('CodeRole', 'ASS')
                ->values()
                ->map(function ($assureGarantie) {
                    return [
                        'NomPrenom' => $assureGarantie['NomPrenom'] ?? null,
                        'CodeGarantie' => $assureGarantie['CodeGarantie'] ?? null,
                        'Libelle' => $assureGarantie['MonLibelle'] ?? null,
                        'Capital' => (float) ($assureGarantie['Capital'] ?? 0),
                        'Prime' => (float) ($assureGarantie['Prime'] ?? 0),
                        'PrimePrincipale' => $assureGarantie['PrimePrincipale'] ?? 0,
                        'FraisAccessoires' => (float) ($assureGarantie['FraisAcces'] ?? 0),
                        'DateEffet' => $assureGarantie['DateEffet'] ?? null,
                        'DateEcheance' => $assureGarantie['DateEcheance'] ?? null,
                        'DureeCouvAns' => (int) ($assureGarantie['DureeCouvAns'] ?? 0),
                        'DureePrimeAns' => (float) ($assureGarantie['DureePrimeAns'] ?? 0),
                        'Periodicite' => $assureGarantie['CodePerodicite'] ?? null,
                    ];
                })
                ->toArray();

            $contratData = [
                'details' => [
                    'IdProposition' => $detail['IdProposition'] ?? null,
                    'NumBulletin' => $detail['CodepropositionForm'] ?? null,
                    'NumPolice' => $detail['CodePolice'] ?? null,
                    'CodeProposition' => $detail['CodeProposition'] ?? null,
                    'CapitalSouscrit' => $detail['CapitalSouscrit'] ?? 0,
                    'TotalPrime' => $detail['TotalPrime'] ?? 0,
                    'NbreImpayes' => $detail['NbreImpayes'] ?? 0,
                    'NbreEmission' => $detail['NbreEmission'] ?? 0,
                    'NbreEncaissment' => $detail['NbreEncaissment'] ?? 0,
                    'NbrencPartielle' => $detail['NbrencPartielle'] ?? 0,
                    'TotalEncaissement' => $detail['TotalEncaissement'] ?? 0,
                    'TotalEncaissementPartielle' => $detail['TotalEncaissementPartielle'] ?? 0,
                    'TotalImpayes' => $detail['TotalImpayes'] ?? 0,

                    'produit' => $detail['produit'] ?? $userContrat->libelle_produit ?? 'Non défini',
                    'EtatAvancementCotisation' => $detail['EtatAvancementCotisation'] ?? null,
                    'DureeCotisationAns' => (float) $detail['DureeCotisationAns'] ?? null,
                    'Periodicite' => $detail['LibellePeriodicite'] ?? $detail['periodicite'] ?? null,
                    'ModePaiement' => $detail['LibelleModePaiement'] ?? $detail['CodeModePaiement'] ?? null,
                    'DateFinAdhesion' => (isset($detail['FinAdhesion']) && $detail['FinAdhesion'] != null) ? Carbon::parse($detail['FinAdhesion'])->format('d/m/Y') : $detail['FinAdhesion'] ?? null,
                    'DateEffetAdhesion' => !empty($detail['DateEffetReel']) ? Carbon::parse($detail['DateEffetReel'])->format('d/m/Y') : (!empty($detail['DateEffetSouhaite']) ? Carbon::parse($detail['DateEffetSouhaite'])->format('d/m/Y') : null),
                    'Conseiller' => $detail['CodeConseiller'] . '-' . $detail['NomAgent']  ?? null,
                    'Adherent' => $detail['nomSous'] . ' ' . $detail['PrenomSous']  ?? null,

                    'Status' => $this->getContractStatus($detail)
                ],
                'Assures' => $assures,
                'AssuresGaranties' => $AssuresGaranties,
                'Beneficiaires' => $beneficiaires,
                'PayeurPrime' => $payeur,
                'PrimeNonRegles' => $data['enc']['nonRegle'],
                'PrimeRegles' => $data['enc']['confirmer'],
                'PrimeReglesPartielle' => $data['enc']['partielle'],

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
                'message' => count($contratData) > 0 ? 'Etats de contisation du contrats recuperer avec successe.' : 'Aucun contrat trouvé.',
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
     * Ajouter un nouveau contrat au compte client
     */
    public function addNewContrat(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            // Vérifier si le contrat est déjà associé
            $existingContract = UserContrat::where('contrat_id', $request->idcontrat)
                ->where('user_uuid', $user->uuid_user)
                ->first();

            if ($existingContract) {
                $message = 'Le contrat ' . $request->idcontrat . ' est déjà associé à votre compte.';

                return response()->json([
                    'success' => false,
                    'code' => 'CONTRAT_ALREADY_EXISTS',
                    'message' => $message,
                ], 422);
            }

            // Récupérer les détails de l'utilisateur
            $userDetail = UserDetails::where('user_uuid', $user->uuid_user)->first();
            
            // Récupérer les informations du contrat via l'API
            $result = $this->encaissementBisService->getContrat($request->idcontrat);
            
            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                ], 422);
            }

            $data = $result['data'];
            $details = $data['details'][0];

            // Vérifier la date de naissance
            if ($data['details'][0]['DateNaissance'] != $userDetail->date_naissance) {
                return response()->json([
                    'success' => false,
                    'code' => 'DATE_OF_BIRTH_MISMATCH',
                    'message' => 'Votre date de naissance ne correspond pas à celle enregistrée dans le contrat. Veuillez faire une demande de modification.',
                ], 422);
            }

            // Vérifier si le contrat est arrêté
            if ($data['details'][0]['OnStdbyOff'] == "3") {
                return response()->json([
                    'success' => false,
                    'code' => 'CONTRACT_FROZEN',
                    'message' => 'Ce contrat est arrêté.',
                ], 422);
            }

            // Créer l'association du contrat
            $contratAdded = UserContrat::create([
                'uuid_user_contrat' => (string) Str::uuid(),
                'user_uuid' => $user->uuid_user,
                'contrat_id' => $details['IdProposition'] ?? null,
                'client_number' => $user->client_number ?? null,
                'code_produit' => $details['codeProduit'] ?? null,
                'libelle_produit' => $details['produit'] ?? null,
                'code_produit_formule' => $details['CodeProduitFormule'] ?? null,
                'libelle_produit_formule' => $details['ProduitFormule'] ?? null,
            ]);

            // ============================================================
            // CRÉER LES NOTIFICATIONS
            // ============================================================

            // 1) Notification pour l'utilisateur - Contrat ajouté
            $this->notificationService->create([
                'user_uuid' => $user->uuid_user,
                'group_notif_uuid' => $this->getContratsGroupUuid(),
                'title' => '📄 Nouveau contrat ajouté',
                'body' => "Le contrat n° {$details['IdProposition']} ({$details['produit']}) a été ajouté à votre compte avec succès.",
                'type' => 'contract',
                'metadata' => [
                    'contrat_id' => $details['IdProposition'],
                    'produit' => $details['produit'],
                    'code_produit' => $details['codeProduit'] ?? null,
                    'added_at' => now()->toISOString(),
                ],
                'channel' => 'database',
                'created_by' => $user->uuid_user,
            ]);

            // 2) Notification pour l'utilisateur - Bienvenue si premier contrat
            $contratsCount = UserContrat::where('user_uuid', $user->uuid_user)->count();
            
            if ($contratsCount === 1) {
                $this->notificationService->create([
                    'user_uuid' => $user->uuid_user,
                    'group_notif_uuid' => $this->getWelcomeGroupUuid(),
                    'title' => '🎉 Bienvenue ! Premier contrat ajouté',
                    'body' => "Félicitations ! Votre premier contrat a été ajouté à votre espace client. Vous pouvez maintenant suivre vos cotisations et paiements.",
                    'type' => 'welcome',
                    'metadata' => [
                        'contrat_id' => $details['IdProposition'],
                        'contrat_count' => $contratsCount,
                    ],
                    'channel' => 'database',
                    'created_by' => $user->uuid_user,
                ]);
            }

            // 3) Notification de sécurité - Nouveau contrat ajouté (alerte)
            $this->notificationService->create([
                'user_uuid' => $user->uuid_user,
                'group_notif_uuid' => $this->getSecurityGroupUuid(),
                'title' => '🔒 Nouveau contrat associé',
                'body' => "Un nouveau contrat a été associé à votre compte depuis l'adresse IP {$request->ip()}.",
                'type' => 'security',
                'metadata' => [
                    'contrat_id' => $details['IdProposition'],
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'associated_at' => now()->toISOString(),
                ],
                'channel' => 'database',
                'created_by' => $user->uuid_user,
            ]);

            Log::info('Nouveau contrat ajouté', [
                'user_uuid' => $user->uuid_user,
                'contrat_id' => $details['IdProposition'],
                'contrat_count' => $contratsCount,
            ]);

            return response()->json([
                'success' => true,
                'code' => 'CONTRACT_ADDED',
                'message' => 'Contrat ajouté avec succès.',
                'data' => $contratAdded,
            ], 200);

        } catch (\Throwable $e) {
            Log::error('Erreur lors de l\'ajout du contrat', [
                'user_uuid' => $request->user()?->uuid_user,
                'contrat_id' => $request->idcontrat,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de l\'ajout du contrat.',
                'code' => 'CONTRACT_ADD_ERROR',
            ], 500);
        }
    }

    /**
     * Récupérer les contrats avec factures impayées
     */
    public function getContratsFactures(Request $request): JsonResponse
    {
        try {
            $user = $request->user()->load('userContrats');

            if ($user->userContrats->isEmpty()) {
                $this->clearUnpaidNotifications($user);
                
                return response()->json([
                    'success' => true,
                    'code' => 'NO_FACTURE_FOUND',
                    'message' => 'Aucun contrat trouvé.',
                    'data' => [],
                    'meta' => [
                        'total' => 0,
                        'per_page' => (int) $request->get('per_page', 10),
                        'current_page' => 1,
                        'last_page' => 1,
                    ],
                ], 200);
            }

            $period = $request->get('period', 'all');
            $dateFrom = $request->get('date_from');
            $dateTo = $request->get('date_to');
            $search = $request->get('search');

            $errors = [];
            $allContrats = [];
            $hasUnpaidInvoices = false;
            $totalImpayes = 0;
            $totalFactures = 0;
            $totalContrats = 0;

            foreach ($user->userContrats as $userContrat) {
                try {
                    $result = $this->encaissementBisService->getContrat($userContrat->contrat_id);

                    if (!$result['success']) {
                        $errors[] = [
                            'contrat_id' => $userContrat->contrat_id,
                            'message' => $result['message'] ?? 'Erreur de récupération'
                        ];
                        continue;
                    }

                    $data = $result['data'];

                    if (!isset($data['details'][0])) {
                        $errors[] = [
                            'contrat_id' => $userContrat->contrat_id,
                            'message' => 'Contrat sans détails'
                        ];
                        continue;
                    }

                    $detail = $data['details'][0];

                    if (isset($detail['OnStdbyOff']) && $detail['OnStdbyOff'] == "3") {
                        continue;
                    }

                    // Filtrer par recherche
                    if ($search) {
                        $searchLower = strtolower($search);
                        $produit = strtolower($detail['produit'] ?? $userContrat->libelle_produit ?? '');
                        $idProposition = strtolower($detail['IdProposition'] ?? '');

                        if (
                            strpos($produit, $searchLower) === false &&
                            strpos($idProposition, $searchLower) === false
                        ) {
                            continue;
                        }
                    }

                    // Filtrer les factures par période
                    $PrimeNonRegles = collect($data['enc']['nonRegle'] ?? [])
                        ->values()
                        ->map(function ($PrimeNonRegle) {
                            $typeFacture = $PrimeNonRegle['TypePresentation'] ?? null;
                            $dateCreation = $PrimeNonRegle['MaDate'] ?? null;
                            $dateFormatted = $this->parseDate($dateCreation);

                            return [
                                'IdFacture' => $PrimeNonRegle['IdPresentation'] ?? null,
                                'DateCreation' => $dateCreation,
                                'DateCreationFormatted' => $dateFormatted,
                                'MontantARegler' => (float) ($PrimeNonRegle['MontantNet'] ?? 0),
                                'TypeFacture' => $typeFacture,
                                'TypeFactureLibelle' => $this->getTypeFactureLibelle($typeFacture),
                            ];
                        })
                        ->filter(function ($facture) use ($period, $dateFrom, $dateTo) {
                            if (!$facture['DateCreationFormatted']) {
                                return true;
                            }

                            $factureDate = $facture['DateCreationFormatted'];
                            $today = now()->startOfDay();

                            switch ($period) {
                                case 'today':
                                    return $factureDate->isToday();
                                case 'week':
                                    return $factureDate->isCurrentWeek();
                                case 'month':
                                    return $factureDate->isCurrentMonth();
                                case 'year':
                                    return $factureDate->isCurrentYear();
                                case 'custom':
                                    if ($dateFrom && $factureDate->lt(Carbon::parse($dateFrom)->startOfDay())) {
                                        return false;
                                    }
                                    if ($dateTo && $factureDate->gt(Carbon::parse($dateTo)->endOfDay())) {
                                        return false;
                                    }
                                    return true;
                                case 'all':
                                default:
                                    return true;
                            }
                        })
                        ->values()
                        ->toArray();

                    // Si après filtrage il n'y a plus de factures, on passe au contrat suivant
                    if (empty($PrimeNonRegles)) {
                        continue;
                    }

                    // ✅ Mettre à jour les compteurs globaux
                    $hasUnpaidInvoices = true;
                    $totalImpayes += array_sum(array_column($PrimeNonRegles, 'MontantARegler'));
                    $totalFactures += count($PrimeNonRegles);
                    $totalContrats++;

                    $contratData = [
                        'details' => [
                            'IdProposition' => $detail['IdProposition'] ?? null,
                            'CapitalSouscrit' => $detail['CapitalSouscrit'] ?? 0,
                            'TotalPrime' => $detail['TotalPrime'] ?? 0,
                            'NbreImpayes' => count($PrimeNonRegles),
                            'TotalImpayes' => array_sum(array_column($PrimeNonRegles, 'MontantARegler')),
                            'produit' => $detail['produit'] ?? $userContrat->libelle_produit ?? 'Non défini',
                            'codeProduit' => $detail['codeProduit'] ?? $userContrat->code_produit ?? 'Non défini',
                            'Status' => $this->getContractStatus($detail)
                        ],
                        'PrimeNonRegles' => $PrimeNonRegles,
                    ];

                    $allContrats[] = $contratData;
                } catch (\Exception $e) {
                    $errors[] = [
                        'contrat_id' => $userContrat->contrat_id,
                        'message' => 'Erreur technique lors de la récupération'
                    ];
                }
            }

            // ============================================================
            // GESTION DES NOTIFICATIONS D'IMPAYÉS
            // ============================================================
            
            if ($hasUnpaidInvoices) {
                // ✅ Passer les données calculées à la notification
                $this->createOrUpdateUnpaidNotification($user, $allContrats, $totalImpayes, $totalFactures, $totalContrats);
            } else {
                $this->clearUnpaidNotifications($user);
            }

            // ============================================================
            // PAGINATION
            // ============================================================
            $perPage = (int) $request->get('per_page', 10);
            $page = (int) $request->get('page', 1);

            $perPage = max(1, min(100, $perPage));
            $page = max(1, $page);

            // Trier les contrats par date de la plus récente facture
            usort($allContrats, function ($a, $b) {
                $dateA = $this->getLatestFactureDate($a);
                $dateB = $this->getLatestFactureDate($b);
                return strtotime($dateB) - strtotime($dateA);
            });

            $total = count($allContrats);
            $lastPage = ceil($total / $perPage);
            $offset = ($page - 1) * $perPage;
            $paginatedData = array_slice($allContrats, $offset, $perPage);

            return response()->json([
                'success' => true,
                'code' => !empty($paginatedData) ? 'FACTURE_FOUND' : 'NO_FACTURE_FOUND',
                'message' => !empty($paginatedData)
                    ? 'Contrats avec factures impayés récupérés'
                    : 'Aucun contrat trouvé avec facture impayé.',
                'data' => $paginatedData,
                'meta' => [
                    'total' => $total,
                    'per_page' => $perPage,
                    'current_page' => $page,
                    'last_page' => $lastPage,
                    'has_errors' => !empty($errors),
                    'errors' => $errors,
                    'filters' => [
                        'period' => $period,
                        'date_from' => $dateFrom,
                        'date_to' => $dateTo,
                        'search' => $search,
                    ],
                    'has_unpaid_invoices' => $hasUnpaidInvoices,
                    'summary' => [
                        'total_impayes' => $totalImpayes,
                        'total_factures' => $totalFactures,
                        'total_contrats' => $totalContrats,
                    ],
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Erreur getContratsFactures', [
                'user_uuid' => $request->user()?->uuid_user,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'code' => 'GET_CONTRAT_ERROR',
                'message' => 'Une erreur est survenue lors de la récupération des contrats.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 422);
        }
    }

    /**
     * Créer ou mettre à jour la notification d'impayés
     */
    private function createOrUpdateUnpaidNotification($user, array $contrats, float $totalImpayes, int $totalFactures, int $totalContrats): void
    {
        $paiementsGroup = GroupNotif::where('code', 'paiements')->first();

        $existingNotification = Notification::where('user_uuid', $user->uuid_user)
            ->where('type', 'impayee')
            ->whereNull('read_at')
            ->first();

        $title = '⚠️ ' . $totalFactures . ' facture' . ($totalFactures > 1 ? 's' : '') . ' impayée' . ($totalFactures > 1 ? 's' : '');
        $body = 'Vous avez ' . $totalFactures . ' facture' . ($totalFactures > 1 ? 's' : '') . ' impayée' . ($totalFactures > 1 ? 's' : '') . ' sur ' . $totalContrats . ' contrat' . ($totalContrats > 1 ? 's' : '') . '. Montant total : ' . number_format($totalImpayes, 0, ',', ' ') . ' F CFA.';

        $metadata = [
            'total_impayes' => $totalImpayes,
            'total_contrats' => $totalContrats,
            'total_factures' => $totalFactures,
            'contrats' => array_map(function ($contrat) {
                return [
                    'id' => $contrat['details']['IdProposition'] ?? null,
                    'produit' => $contrat['details']['produit'] ?? null,
                    'nbre_impayes' => $contrat['details']['NbreImpayes'] ?? 0,
                    'total_impayes' => $contrat['details']['TotalImpayes'] ?? 0,
                ];
            }, $contrats),
            'updated_at' => now()->toISOString(),
        ];

        if ($existingNotification) {
            $existingNotification->update([
                'title' => $title,
                'body' => $body,
                'metadata' => $metadata,
                'updated_at' => now(),
            ]);
        } else {
            $this->notificationService->create([
                'user_uuid' => $user->uuid_user,
                'group_notif_uuid' => $paiementsGroup?->uuid_group_notif,
                'title' => $title,
                'body' => $body,
                'type' => 'impayee',
                'metadata' => $metadata,
                'channel' => 'database',
                'created_by' => null,
            ]);
        }
    }

    /**
     * Marquer comme lues les notifications d'impayés
     */
    private function clearUnpaidNotifications($user): void
    {
        Notification::where('user_uuid', $user->uuid_user)
            ->where('type', 'impayees')
            ->whereNull('read_at')
            ->update([
                'read_at' => now(),
            ]);
    }

    /**
     * Parser une date au format d/m/Y
     */
    private function parseDate(?string $date): ?Carbon
    {
        if (!$date) {
            return null;
        }

        try {
            return Carbon::createFromFormat('d/m/Y', $date);
        } catch (\Exception $e) {
            try {
                return Carbon::parse($date);
            } catch (\Exception $e) {
                return null;
            }
        }
    }

    /**
     * Récupérer la date de la plus récente facture
     */
    private function getLatestFactureDate(array $contrat): string
    {
        $dates = array_column($contrat['PrimeNonRegles'] ?? [], 'DateCreation');
        if (empty($dates)) {
            return '1970-01-01';
        }

        usort($dates, function ($a, $b) {
            return strtotime($b) - strtotime($a);
        });

        return $dates[0] ?? '1970-01-01';
    }

    /**
     * Obtenir le libellé du type de facture
     */
    private function getTypeFactureLibelle(?string $type): string
    {
        $typeMap = [
            'N' => 'Prime normal',
            'F' => 'Frais d\'adhésion',
            'P' => 'Partielle (Reste à payer)',
            'U' => 'Unique',
            'B' => 'Participation aux Bénéfices',
            'E' => 'Exceptionnelle',
            'A' => 'Avance (Remboursement de prêts)',
        ];

        return $typeMap[$type] ?? 'Inconnu';
    }

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

    /**
     * Récupérer l'UUID du groupe des contrats
     */
    private function getContratsGroupUuid(): ?string
    {
        $group = GroupNotif::where('code', 'contrats')->first();
        return $group?->uuid_group_notif;
    }

    /**
     * Récupérer l'UUID du groupe de sécurité
     */
    private function getSecurityGroupUuid(): ?string
    {
        $group = GroupNotif::where('code', 'securite')->first();
        return $group?->uuid_group_notif;
    }

    /**
     * Récupérer l'UUID du groupe de bienvenue
     */
    private function getWelcomeGroupUuid(): ?string
    {
        $group = GroupNotif::where('code', 'welcome')->first();
        return $group?->uuid_group_notif;
    }
}
