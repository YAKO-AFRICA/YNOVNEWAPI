<?php

namespace App\Http\Controllers\Api\Ynov\Esouscription;

use App\Http\Controllers\Controller;
use App\Services\Api\Ynov\Documents\DocumentService;
use App\Services\Api\Ynov\Esouscription\ActeurService;
use App\Services\Api\Ynov\Esouscription\ClientNumberGenerator;
use App\Services\Api\Ynov\Esouscription\ContratActeurService;
use App\Services\Api\Ynov\Esouscription\ContratService;
use App\Services\Api\Ynov\Esouscription\DeclarationSanteService;
use DateTimeImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PropositionController extends Controller
{
    public function __construct(
        private ClientNumberGenerator $clientNumberGenerator,
        private ActeurService $acteurService,
        private ContratActeurService $contratActeurService,
        private DeclarationSanteService $declarationSanteService,
        private DocumentService $documentService,
        private ContratService $contratService
    ) {}

    public function storeSouscription(Request $request)
    {
        try {
            $result = DB::transaction(function () use ($request) {

                $contratUuid = Str::uuid()->toString();
                $keyIntegration = now()->format('Ymdh');

                $adherentData      = $request->adherentData ?? [];
                $assurerDatas      = $request->assurerDatas ?? [];
                $BeneficiaireDatas = $request->BeneficiaireDatas ?? [];
                $DocumentDatas     = $request->documentDatas ?? [];
                $contratData       = $request->contratData ?? [];

                $createdBy = $adherentData['created_by'] ?? null;

                // ==================== 1. Souscripteur (ADH) ====================
                $numeroClient = $this->clientNumberGenerator->generate(
                    (int) $adherentData['genre'],
                    new DateTimeImmutable($adherentData['date_naissance']),
                );

                Log::info("Données de l'adhérent", $adherentData);

                $adherentStore = $this->acteurService->create([
                    'uuid_acteur'            => Str::uuid(),
                    'idClient'               => $numeroClient,
                    'civilite'               => $adherentData['civilite'],
                    'genre'                  => $adherentData['genre'],
                    'nom'                    => $adherentData['nom'],
                    'prenoms'                => $adherentData['prenoms'],
                    'date_naissance'         => $adherentData['date_naissance'],
                    'lieunaissance_code'     => $adherentData['lieunaissance_code'],
                    'email'                  => $adherentData['email'],
                    'mobile'                 => $adherentData['mobile'],
                    'telephone'              => $adherentData['telephone'],
                    'numero_piece'           => $adherentData['numero_piece'],
                    'nni'                    => $adherentData['nni'],
                    'nature_piece'           => $adherentData['nature_piece'],
                    'situation_matrimoniale' => $adherentData['situation_matrimoniale'],
                    'profession_code'        => $adherentData['profession_code'],
                    'employeur'              => $adherentData['employeur'],
                    'lieuresidence_code'     => $adherentData['lieuresidence_code'],
                    'pays_code'              => $adherentData['pays_code'],
                    'integration_key'        => $keyIntegration,
                    'created_by'             => $createdBy,
                ]);

                if (!$adherentStore) {
                    throw new \Exception("Échec de la création du souscripteur.");
                }

                $contratAdherentStore = $this->contratActeurService->create([
                    'uuid_contrat_acteur' => Str::uuid(),
                    'contrat_uuid'        => $contratUuid,
                    'acteur_uuid'         => $adherentStore->uuid_acteur,
                    'type_acteur'         => 'ADH',
                    'integration_key'     => $keyIntegration,
                    'created_by'          => $createdBy,
                ]);

                if (!$contratAdherentStore) {
                    throw new \Exception("Échec de la liaison contrat-adhérent.");
                }

                Log::info("Souscripteur créé avec succès : {$adherentStore->uuid_acteur}");

                // ==================== 2. Assurés (ASS) ====================
                Log::info('Données des assurés', $assurerDatas);

                foreach ($assurerDatas as $assure) {

                    $assureInfo  = $assure['info']  ?? [];
                    $assureSante = $assure['sante'] ?? [];
                    $assureCreatedBy = $assureInfo['created_by'] ?? $createdBy;
                    $isAdherentAssure = data_get($assure, 'is_adherent') === 'oui';
                    $uuidAssure = $isAdherentAssure
                        ? $adherentStore->uuid_acteur
                        : Str::uuid();

                    if ($isAdherentAssure) {

                        $contratAssureAdherentStore = $this->contratActeurService->create([
                            'uuid_contrat_acteur' => Str::uuid(),
                            'contrat_uuid'        => $contratUuid,
                            'acteur_uuid'         => $adherentStore->uuid_acteur,
                            'type_acteur'         => 'ASS',
                            'integration_key'     => $keyIntegration,
                            'created_by'          => $assureCreatedBy,
                        ]);

                        if (!$contratAssureAdherentStore) {
                            throw new \Exception("Échec de la liaison contrat-assuré : {$assureInfo['nom']}");
                        }

                        $AssuAdhStoreSante = $this->declarationSanteService->create([
                            'uuid_declaration_santes'                  => Str::uuid(),
                            'contrat_uuid'                             => $contratUuid,
                            'assure_uuid'                              => $uuidAssure,
                            'taille'                                   => $assureSante['taille'],
                            'poids'                                    => $assureSante['poids'],
                            'tension_min'                              => $assureSante['tension_min'],
                            'tension_max'                              => $assureSante['tension_max'],
                            'tabagisme'                                => $assureSante['tabagisme'],
                            'alcool'                                   => $assureSante['alcool'],
                            'sport'                                    => $assureSante['sport'],
                            'accident'                                 => $assureSante['accident'],
                            'traitement'                               => $assureSante['traitement'],
                            'transfusion_sanguine'                     => $assureSante['transfusion_sanguine'],
                            'intervention_chirurgicale'                => $assureSante['intervention_chirurgicale'],
                            'prochaine_intervention_chirurgicale'      => $assureSante['prochaine_intervention_chirurgicale'],
                            'diabete'                                  => $assureSante['diabete'],
                            'hypertension'                             => $assureSante['hypertension'],
                            'drepanocytose'                            => $assureSante['drepanocytose'],
                            'cirrhose_foie'                            => $assureSante['cirrhose_foie'],
                            'maladie_pulmonaire'                       => $assureSante['maladie_pulmonaire'],
                            'cancer'                                   => $assureSante['cancer'],
                            'anemie'                                   => $assureSante['anemie'],
                            'insuffisance_renale'                      => $assureSante['insuffisance_renale'],
                            'avc'                                      => $assureSante['avc'],
                            'integration_key'                          => $keyIntegration,
                            'created_by'                               => $assureCreatedBy,
                        ]);

                        if (!$AssuAdhStoreSante) {
                            throw new \Exception("Échec de la déclaration santé de l'assuré : {$assureInfo['nom']}");
                        }

                        
                    } else {

                        $assurerStore = $this->acteurService->create([
                            'uuid_acteur'            => $uuidAssure,
                            'civilite'               => $assureInfo['civilite'],
                            'genre'                  => $assureInfo['genre'],
                            'nom'                    => $assureInfo['nom'],
                            'prenoms'                => $assureInfo['prenoms'],
                            'date_naissance'         => $assureInfo['date_naissance'],
                            'lieunaissance_code'     => $assureInfo['lieunaissance_code'],
                            'email'                  => $assureInfo['email'],
                            'mobile'                 => $assureInfo['mobile'],
                            'telephone'              => $assureInfo['telephone'],
                            'numero_piece'           => $assureInfo['numero_piece'],
                            'nni'                    => $assureInfo['nni'],
                            'nature_piece'           => $assureInfo['nature_piece'],
                            'situation_matrimoniale' => $assureInfo['situation_matrimoniale'],
                            'profession_code'        => $assureInfo['profession_code'],
                            'employeur'              => $assureInfo['employeur'],
                            'lieuresidence_code'     => $assureInfo['lieuresidence_code'],
                            'pays_code'              => $assureInfo['pays_code'],
                            'integration_key'        => $keyIntegration,
                            'created_by'             => $assureCreatedBy,
                        ]);

                        if (!$assurerStore) {
                            throw new \Exception("Échec de la création de l'assuré : {$assureInfo['nom']}");
                        }

                        $contratAssureStore = $this->contratActeurService->create([
                            'uuid_contrat_acteur' => Str::uuid(),
                            'contrat_uuid'        => $contratUuid,
                            'acteur_uuid'         => $uuidAssure,
                            'type_acteur'         => 'ASS',
                            'integration_key'     => $keyIntegration,
                            'created_by'          => $assureCreatedBy,
                        ]);

                        if (!$contratAssureStore) {
                            throw new \Exception("Échec de la liaison contrat-assuré : {$assureInfo['nom']}");
                        }

                        $assurSanteStore = $this->declarationSanteService->create([
                            'uuid_declaration_santes'                  => Str::uuid(),
                            'contrat_uuid'                             => $contratUuid,
                            'assure_uuid'                              => $uuidAssure,
                            'taille'                                   => $assureSante['taille'],
                            'poids'                                    => $assureSante['poids'],
                            'tension_min'                              => $assureSante['tension_min'],
                            'tension_max'                              => $assureSante['tension_max'],
                            'tabagisme'                                => $assureSante['tabagisme'],
                            'alcool'                                   => $assureSante['alcool'],
                            'sport'                                    => $assureSante['sport'],
                            'accident'                                 => $assureSante['accident'],
                            'traitement'                               => $assureSante['traitement'],
                            'transfusion_sanguine'                     => $assureSante['transfusion_sanguine'],
                            'intervention_chirurgicale'                => $assureSante['intervention_chirurgicale'],
                            'prochaine_intervention_chirurgicale'      => $assureSante['prochaine_intervention_chirurgicale'],
                            'diabete'                                  => $assureSante['diabete'],
                            'hypertension'                             => $assureSante['hypertension'],
                            'drepanocytose'                            => $assureSante['drepanocytose'],
                            'cirrhose_foie'                            => $assureSante['cirrhose_foie'],
                            'maladie_pulmonaire'                       => $assureSante['maladie_pulmonaire'],
                            'cancer'                                   => $assureSante['cancer'],
                            'anemie'                                   => $assureSante['anemie'],
                            'insuffisance_renale'                      => $assureSante['insuffisance_renale'],
                            'avc'                                      => $assureSante['avc'],
                            'integration_key'                          => $keyIntegration,
                            'created_by'                               => $assureCreatedBy,
                        ]);

                        if (!$assurSanteStore) {
                            throw new \Exception("Échec de la déclaration santé de l'assuré : {$assureInfo['nom']}");
                        }

                    }
                }

                // ==================== 3. Bénéficiaires (BEN) ====================
                Log::info('Données des bénéficiaires', $BeneficiaireDatas);

                foreach ($BeneficiaireDatas as $beneficiaire) {
                    $benefUuid = Str::uuid();
                    $benefCreatedBy = $beneficiaire['created_by'] ?? $createdBy;

                    if (data_get($beneficiaire, 'is_adherent') === 'oui') {
                         $contratBenefAdhStore = $this->contratActeurService->create([
                            'uuid_contrat_acteur' => Str::uuid(),
                            'contrat_uuid'        => $contratUuid,
                            'acteur_uuid'         => $adherentStore->uuid_acteur,
                            'type_acteur'         => 'BEN',
                            'integration_key'     => $keyIntegration,
                            'created_by'          => $benefCreatedBy,
                        ]);

                        if (!$contratBenefAdhStore) {
                            throw new \Exception("Échec de la liaison contrat-bénéficiaire : {$beneficiaire['nom']}");
                        }

                    } else {

                        $beneficiaireStore = $this->acteurService->create([
                            'uuid_acteur'            => $benefUuid,
                            'civilite'               => $beneficiaire['civilite'],
                            'genre'                  => $beneficiaire['genre'],
                            'nom'                    => $beneficiaire['nom'],
                            'prenoms'                => $beneficiaire['prenoms'],
                            'date_naissance'         => $beneficiaire['date_naissance'],
                            'lieunaissance_code'     => $beneficiaire['lieunaissance_code'],
                            'email'                  => $beneficiaire['email'],
                            'mobile'                 => $beneficiaire['mobile'],
                            'telephone'              => $beneficiaire['telephone'],
                            'numero_piece'           => $beneficiaire['numero_piece'],
                            'nni'                    => $beneficiaire['nni'],
                            'nature_piece'           => $beneficiaire['nature_piece'],
                            'situation_matrimoniale' => $beneficiaire['situation_matrimoniale'],
                            'profession_code'        => $beneficiaire['profession_code'],
                            'employeur'              => $beneficiaire['employeur'],
                            'lieuresidence_code'     => $beneficiaire['lieuresidence_code'],
                            'pays_code'              => $beneficiaire['pays_code'],
                            'integration_key'        => $keyIntegration,
                            'created_by'             => $benefCreatedBy,
                        ]);

                        if (!$beneficiaireStore) {
                            throw new \Exception("Échec de la création du bénéficiaire : {$beneficiaire['nom']}");
                        }

                        $contratBenefStore = $this->contratActeurService->create([
                            'uuid_contrat_acteur' => Str::uuid(),
                            'contrat_uuid'        => $contratUuid,
                            'acteur_uuid'         => $benefUuid,
                            'type_acteur'         => 'BEN',
                            'integration_key'     => $keyIntegration,
                            'created_by'          => $benefCreatedBy,
                        ]);

                        if (!$contratBenefStore) {
                            throw new \Exception("Échec de la liaison contrat-bénéficiaire : {$beneficiaire['nom']}");
                        }
                    }
                }

                // ==================== 4. Documents ====================
                Log::info('Données des documents', $DocumentDatas);

                $payload = [
                    'reference_uuid' => $contratUuid,
                    'source'         => 'E-SOUSCRIPTION',
                    'created_by'     => $createdBy,
                    'documents'      => $DocumentDatas,
                ];

                $documentStore = $this->documentService->createDocument($payload);

                if (!$documentStore) {
                    throw new \Exception("Échec de l'enregistrement des documents.");
                }

                // ==================== 5. Contrat ====================
                $contratPayload = [
                    'uuid_contrat'                  => $contratUuid,
                    'date_effet'                    => $contratData['date_effet'],
                    'mode_paiement'                 => $contratData['mode_paiement'],
                    'organisme'                     => $contratData['organisme'],
                    'duree'                         => $contratData['duree'],
                    'code_periodicite'              => $contratData['code_periodicite'],
                    'prime'                         => $contratData['prime'],
                    'prime_principale'              => $contratData['prime_principale'],
                    'sur_prime'                     => $contratData['sur_prime'],
                    'capital'                       => $contratData['capital'],
                    'frais_adhesion'                => $contratData['frais_adhesion'],
                    'montant_rente'                 => $contratData['montant_rente'],
                    'periodicite_rente'             => $contratData['periodicite_rente'],
                    'duree_rente'                   => $contratData['duree_rente'],
                    'code_banque'                   => $contratData['code_banque'],
                    'code_guichet'                  => $contratData['code_guichet'],
                    'rib'                           => $contratData['rib'],
                    'numero_compte'                 => $contratData['numero_compte'],
                    'numecompte_complet'            => $contratData['numecompte_complet'],
                    'agence_uuid'                   => $contratData['agence_uuid'],
                    'code_produit'                  => $contratData['code_produit'],
                    'libelle_produit'               => $contratData['libelle_produit'],
                    'formule_produit_code'          => $contratData['formule_produit_code'],
                    'contact_personne_nom'          => $contratData['contact_personne_nom'],
                    'contact_personne_mobile'       => $contratData['contact_personne_mobile'],
                    'contact_personne_nom_2'        => $contratData['contact_personne_nom_2'],
                    'contact_personne_mobile_2'     => $contratData['contact_personne_mobile_2'],
                    'branch_code'                   => $contratData['branch_code'],
                    'partner_uuid'                  => $contratData['partner_uuid'],
                    'conseiller_uuid'               => $contratData['conseiller_uuid'],
                    'is_paid'                       => $contratData['is_paid'],
                    'observation'                   => $contratData['observation'],
                    'bulletin_num'                  => $contratData['bulletin_num'],
                    'formule'                       => $contratData['formule'],
                    'integration_key'               => $keyIntegration,
                    'created_by'                    => $contratData['created_by'] ?? $createdBy,
                ];

                $contratStore = $this->contratService->create($contratPayload);

                if (!$contratStore) {
                    throw new \Exception("Échec de la création du contrat.");
                }

                return [
                    'key_integration' => $keyIntegration,
                    'contrat_uuid'    => $contratUuid,
                    'documentStore'   => $documentStore,
                ];
            });

            return response()->json([
                'success'         => true,
                'message'         => 'Souscription créée avec succès',
                'code'            => 200,
                'key_integration' => $result['key_integration'],
                'contrat_uuid'    => $result['contrat_uuid'],
                'data'            => $result['documentStore'],
            ]);

        } catch (\Throwable $th) {
            Log::error('Erreur lors de la souscription : ' . $th->getMessage(), [
                'trace' => $th->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la souscription : ' . $th->getMessage(),
                'code'    => 500,
            ], 500);
        }
    }
}
