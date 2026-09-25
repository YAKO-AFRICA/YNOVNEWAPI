<?php

namespace App\Http\Controllers\Api\Ynov\Esouscription;

use App\Http\Controllers\Controller;
use App\Services\Api\Ynov\Documents\DocumentService;
use App\Services\Api\Ynov\Esouscription\ActeurService;
use App\Services\Api\Ynov\Esouscription\ClientNumberGenerator;
use App\Services\Api\Ynov\Esouscription\ContratActeurService;
use App\Services\Api\Ynov\Esouscription\DeclarationSanteService;
use DateTimeImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PropositionController extends Controller
{

    public function __construct(
        private ClientNumberGenerator $clientNumberGenerator,
        private ActeurService $acteurService,
        private ContratActeurService $contratActeurService,
        private DeclarationSanteService $declarationSanteService,
        private DocumentService $documentService
    ) {}
    public function storeSouscription(Request $request)
    {

        // Log::info('données de souscription', $request->all());

        try {

            $uuids = [
                'contrat_uuid' => Str::uuid(),
            ];

            $keyIntegration = now()->format('Ymdh');

            $adherentData = $request->adherentData ?? [];
            $assurerDatas = $request->assurerDatas ?? [];
            $BeneficiaireDatas = $request->BeneficiaireDatas ?? [];
            $DocumentDatas = $request->documentDatas ?? [];

            try{
                foreach($DocumentDatas as $index => $document) {
                    Log::info('données de Document fichier', );
                    Log::info($document['fichier']);
                    // Log::info($document['fichier']);
                    $payload = [
                        'reference_uuid' => $uuids['contrat_uuid'],
                        'libelle' => $document['libelle'] ?? null,
                        'documents' => $document['fichier'],
                        'created_by' => $document['created_by'] ?? null,
                    ];

                    Log::info('données de Document======= {$payload}', $payload);

                    $documentStore = $this->documentService->createDocument([
                        'reference_uuid' => $uuids['contrat_uuid'],
                        'libelle' => $document['libelle'] ?? null,
                        'documents' => $document['fichier'],
                        'created_by' => $document['created_by'] ?? null,
                        'source' => 'E-SOUSCRIPTION',
                    ]);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Document ajouté avec succès.',
                    'data' => $documentStore
                ]);
            } catch (\Throwable $th) {
                Log::info('données de Document', $th->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => 'Document non ajouté.',
                    'error' => $th->getMessage(),
                ]);
                
            }

             

            return $documentStore;


            $numeroClient = $this->clientNumberGenerator->generate(
                (int) $adherentData['genre'],
                new DateTimeImmutable($adherentData['date_naissance']),
            );

            // insertion de l'adherent souscripteur
            Log::info('donnée de l\'adherent', $adherentData);

            $adherentStore = $this->acteurService->create([
                'uuid_acteur' => Str::uuid(),
                'idClient' => $numeroClient,
                'civilite' => $adherentData['civilite'],
                'genre' => $adherentData['genre'],
                'nom' => $adherentData['nom'],
                'prenoms' => $adherentData['prenoms'],
                'date_naissance' => $adherentData['date_naissance'],
                'lieunaissance_code' => $adherentData['lieunaissance_code'],
                'email' => $adherentData['email'],
                'mobile' => $adherentData['mobile'],
                'telephone' => $adherentData['telephone'],
                'numero_piece' => $adherentData['numero_piece'],
                'nni' => $adherentData['nni'],
                'nature_piece' => $adherentData['nature_piece'],
                'situation_matrimoniale' => $adherentData['situation_matrimoniale'],
                'profession_code' => $adherentData['profession_code'],
                'employeur' => $adherentData['employeur'],
                'lieuresidence_code' => $adherentData['lieuresidence_code'],
                'pays_code' => $adherentData['pays_code'],
                'integration_key' => $keyIntegration,
                'created_by' => $adherentData['created_by'] ?? null,
            ]);

            if ($adherentStore) {
                $contratAdherentStore = $this->contratActeurService->create([
                    'uuid_contrat_acteur' => Str::uuid(),
                    'contrat_uuid' => $uuids['contrat_uuid'],
                    'acteur_uuid' => $adherentStore->uuid_acteur,
                    'type_acteur' => 'ADH',
                    'integration_key' => $keyIntegration,
                    'created_by' => $adherentData['created_by'] ?? null,
                ]);
            }

            Log::info("Finalisation de la creation du souscripteur $adherentStore");

            // insertion des assures
            Log::info('donnée des assures', $assurerDatas);

            foreach ($assurerDatas as $index => $assure) {

                $uuidAssure = Str::uuid();

                $assureInfo = $assure['info'] ?? [];
                $assureSante = $assure['sante'] ?? [];

                $assurerStore = $this->acteurService->create([
                    'uuid_acteur' => $uuidAssure,
                    'civilite' => $assureInfo['civilite'],
                    'genre' => $assureInfo['genre'],
                    'nom' => $assureInfo['nom'],
                    'prenoms' => $assureInfo['prenoms'],
                    'date_naissance' => $assureInfo['date_naissance'],
                    'lieunaissance_code' => $assureInfo['lieunaissance_code'],
                    'email' => $assureInfo['email'],
                    'mobile' => $assureInfo['mobile'],
                    'telephone' => $assureInfo['telephone'],
                    'numero_piece' => $assureInfo['numero_piece'],
                    'nni' => $assureInfo['nni'],
                    'nature_piece' => $assureInfo['nature_piece'],
                    'situation_matrimoniale' => $assureInfo['situation_matrimoniale'],
                    'profession_code' => $assureInfo['profession_code'],
                    'employeur' => $assureInfo['employeur'],
                    'lieuresidence_code' => $assureInfo['lieuresidence_code'],
                    'pays_code' => $assureInfo['pays_code'],
                    'integration_key' => $keyIntegration,
                    'created_by' => $assureInfo['created_by'] ?? null,
                ]);

                if ($assurerStore) {
                    $contratAssureStore = $this->contratActeurService->create([
                        'uuid_contrat_acteur' => Str::uuid(),
                        'contrat_uuid' => $uuids['contrat_uuid'],
                        'acteur_uuid' => $uuidAssure,
                        'type_acteur' => 'ASS',
                        'integration_key' => $keyIntegration,
                        'created_by' => $assureInfo['created_by'] ?? null,
                    ]);
                }

                $assurSanteStore = $this->declarationSanteService->create([
                    'uuid_declaration_santes' => Str::uuid(),
                    'contrat_uuid' => $uuids['contrat_uuid'],
                    'assure_uuid' => $uuidAssure,
                    'taille' => $assureSante['taille'],
                    'poids' => $assureSante['poids'],
                    'tension_min' => $assureSante['tension_min'],
                    'tension_max' => $assureSante['tension_max'],
                    'tabagisme' => $assureSante['tabagisme'],
                    'alcool' => $assureSante['alcool'],
                    'sport' => $assureSante['sport'],
                    'accident' => $assureSante['accident'],
                    'traitement' => $assureSante['traitement'],
                    'transfusion_sanguine' => $assureSante['transfusion_sanguine'],
                    'intervention_chirurgicale' => $assureSante['intervention_chirurgicale'],
                    'prochaine_intervention_chirurgicale' => $assureSante['prochaine_intervention_chirurgicale'],
                    'diabete' => $assureSante['diabete'],
                    'hypertension' => $assureSante['hypertension'],
                    'drepanocytose' => $assureSante['drepanocytose'],
                    'cirrhose_foie' => $assureSante['cirrhose_foie'],
                    'maladie_pulmonaire' => $assureSante['maladie_pulmonaire'],
                    'cancer' => $assureSante['cancer'],
                    'anemie' => $assureSante['anemie'],
                    'insuffisance_renale' => $assureSante['insuffisance_renale'],
                    'avc' => $assureSante['avc'],
                    'integration_key' => $keyIntegration,
                    'created_by' => $assureInfo['created_by'] ?? null,
                ]);
                
            }

            // insertion des beneficiaires

            Log::info('données de Beneficiaire', $BeneficiaireDatas);

            foreach ($BeneficiaireDatas as $index => $beneficiaire) {
                $benefUuid = Str::uuid();
                $beneficiaireStore = $this->acteurService->create([
                    'uuid_acteur' => $benefUuid,
                    'civilite' => $beneficiaire['civilite'],
                    'genre' => $beneficiaire['genre'],
                    'nom' => $beneficiaire['nom'],
                    'prenoms' => $beneficiaire['prenoms'],
                    'date_naissance' => $beneficiaire['date_naissance'],
                    'lieunaissance_code' => $beneficiaire['lieunaissance_code'],
                    'email' => $beneficiaire['email'],
                    'mobile' => $beneficiaire['mobile'],
                    'telephone' => $beneficiaire['telephone'],
                    'numero_piece' => $beneficiaire['numero_piece'],
                    'nni' => $beneficiaire['nni'],
                    'nature_piece' => $beneficiaire['nature_piece'],
                    'situation_matrimoniale' => $beneficiaire['situation_matrimoniale'],
                    'profession_code' => $beneficiaire['profession_code'],
                    'employeur' => $beneficiaire['employeur'],
                    'lieuresidence_code' => $beneficiaire['lieuresidence_code'],
                    'pays_code' => $beneficiaire['pays_code'],
                    'integration_key' => $keyIntegration,
                    'created_by' => $beneficiaire['created_by'] ?? null,
                ]);


                if ($beneficiaireStore) {
                    $contratBenefStore = $this->contratActeurService->create([
                        'uuid_contrat_acteur' => Str::uuid(),
                        'contrat_uuid' => $uuids['contrat_uuid'],
                        'acteur_uuid' => $benefUuid,
                        'type_acteur' => 'BEN',
                        'integration_key' => $keyIntegration,
                        'created_by' => $beneficiaire['created_by'] ?? null,
                    ]);
                }

            }

            // insertion des documents

            Log::info('données de Document', $DocumentDatas);
            

            foreach($DocumentDatas as $index => $document) {
                Log::info('données de Document libellleeeeeeeeeeeeeeeeee', $document['libelle']);
                $payload = [
                    'reference_uuid' => $uuids['contrat_uuid'],
                    'libelle' => $document['libelle'] ?? null,
                    'documents' => $document['fichier'],
                    'created_by' => $document['created_by'] ?? null,
                    'source' => 'E-SOUSCRIPTION',
                ];

                $documentStore = $this->documentService->createDocument($payload);
            }



            

            return response()->json([
                'success' => true,
                'message' => 'Souscription crée avec succès',
                'code' => 200,
                'key_integration' => $keyIntegration,
                'data' => $documentStore
            ]);

            
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
