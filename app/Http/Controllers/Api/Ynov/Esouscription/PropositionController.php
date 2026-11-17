<?php

namespace App\Http\Controllers\Api\Ynov\Esouscription;

use App\Http\Controllers\Controller;
use App\Models\Api\Ynov\Esouscription\Acteur;
use App\Models\Api\Ynov\Esouscription\AssurerGarantie;
use App\Models\Api\Ynov\Esouscription\Contrat;
use App\Models\Api\Ynov\Esouscription\ContratActeur;
use App\Models\Api\Ynov\Esouscription\DeclarationSante;
use App\Mail\CustomerMail;
use App\Models\Api\Ynov\Esouscription\Document;
use App\Services\Api\Ynov\Documents\DocumentService;
use App\Services\Api\Ynov\ESouscription\ActeurService;
use App\Services\Api\Ynov\ESouscription\AssurerGarantieService;
use App\Services\Api\Ynov\ESouscription\ClientNumberGenerator;
use App\Services\Api\Ynov\ESouscription\ContratActeurService;
use App\Services\Api\Ynov\ESouscription\ContratService;
use App\Services\Api\Ynov\ESouscription\DeclarationSanteService;
use App\Services\Api\Ynov\ESouscription\PropositionService;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PropositionController extends Controller
{
    public function __construct(
        private ClientNumberGenerator $clientNumberGenerator,
        private ActeurService $acteurService,
        private ContratActeurService $contratActeurService,
        private DeclarationSanteService $declarationSanteService,
        private DocumentService $documentService,
        private ContratService $contratService,
        private AssurerGarantieService $assurerGarantieService,
        private PropositionService $propositionService
    ) {}

    public function getPropositions(Request $request, string $uuid_user)
    {
        if ($request->user()?->uuid_user !== $uuid_user) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez consulter que les propositions de votre compte.',
                'code' => 403,
            ], 403);
        }

        $dateFinRules = ['sometimes', 'nullable', 'date'];
        if ($request->filled('date_debut')) {
            $dateFinRules[] = 'after_or_equal:date_debut';
        }

        $validated = $request->validate([
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'uuid_contrat' => ['sometimes', 'nullable', 'string', 'max:255'],
            'id_contrat' => ['sometimes', 'nullable', 'integer'],
            'id_proposition' => ['sometimes', 'nullable', 'integer'],
            'code_proposition' => ['sometimes', 'nullable', 'string', 'max:255'],
            'numero_police' => ['sometimes', 'nullable', 'string', 'max:255'],
            'numero_compte' => ['sometimes', 'nullable', 'string', 'max:255'],
            'date_debut' => ['sometimes', 'nullable', 'date'],
            'date_fin' => $dateFinRules,
            'idClient' => ['sometimes', 'nullable', 'string', 'max:255'],
            'numero_piece' => ['sometimes', 'nullable', 'string', 'max:255'],
            'nom' => ['sometimes', 'nullable', 'string', 'max:255'],
            'prenoms' => ['sometimes', 'nullable', 'string', 'max:255'],
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        try {
            $propositions = $this->propositionService->getForUser(
                $uuid_user,
                (int) ($validated['per_page'] ?? 15),
                $validated
            );
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur introuvable.',
                'code' => 404,
            ], 404);
        } catch (\Illuminate\Auth\Access\AuthorizationException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
                'code' => 403,
            ], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Propositions récupérées avec succès',
            'code' => 200,
            'total' => $propositions->total(),
            'data' => $propositions,
        ]);
    }

    public function updateProposition(Request $request, string $uuid)
    {
        $request->validate([
            'contratData' => ['sometimes', 'array'],
            'contratData.date_effet' => ['sometimes', 'nullable', 'date'],
            'contratData.mode_paiement' => ['sometimes', 'nullable', 'string', 'max:100'],
            'contratData.organisme' => ['sometimes', 'nullable', 'string', 'max:255'],
            'contratData.duree' => ['sometimes', 'nullable', 'integer'],
            'contratData.code_periodicite' => ['sometimes', 'nullable', 'string', 'max:100'],
            'contratData.prime' => ['sometimes', 'nullable', 'numeric'],
            'contratData.prime_principale' => ['sometimes', 'nullable', 'numeric'],
            'contratData.sur_prime' => ['sometimes', 'nullable', 'numeric'],
            'contratData.capital' => ['sometimes', 'nullable', 'numeric'],
            'contratData.frais_adhesion' => ['sometimes', 'nullable', 'numeric'],
            'contratData.montant_rente' => ['sometimes', 'nullable', 'numeric'],
            'contratData.periodicite_rente' => ['sometimes', 'nullable', 'string', 'max:100'],
            'contratData.duree_rente' => ['sometimes', 'nullable', 'integer'],
            'contratData.code_banque' => ['sometimes', 'nullable', 'string', 'max:100'],
            'contratData.code_guichet' => ['sometimes', 'nullable', 'string', 'max:100'],
            'contratData.rib' => ['sometimes', 'nullable', 'string', 'max:100'],
            'contratData.numero_compte' => ['sometimes', 'nullable', 'string', 'max:100'],
            'contratData.numecompte_complet' => ['sometimes', 'nullable', 'string', 'max:255'],
            'contratData.autre_ref_prelev' => ['sometimes', 'nullable', 'string', 'max:255'],
            'contratData.agence_uuid' => ['sometimes', 'nullable', 'string', 'max:255'],
            'contratData.code_produit' => ['sometimes', 'nullable', 'string', 'max:100'],
            'contratData.libelle_produit' => ['sometimes', 'nullable', 'string', 'max:255'],
            'contratData.formule_produit_code' => ['sometimes', 'nullable', 'string', 'max:100'],
            'contratData.contact_personne_nom' => ['sometimes', 'nullable', 'string', 'max:255'],
            'contratData.contact_personne_mobile' => ['sometimes', 'nullable', 'string', 'max:50'],
            'contratData.contact_personne_nom_2' => ['sometimes', 'nullable', 'string', 'max:255'],
            'contratData.contact_personne_mobile_2' => ['sometimes', 'nullable', 'string', 'max:50'],
            'contratData.branch_code' => ['sometimes', 'nullable', 'string', 'max:100'],
            'contratData.partner_uuid' => ['sometimes', 'nullable', 'string', 'max:255'],
            'contratData.conseiller_uuid' => ['sometimes', 'nullable', 'string', 'max:255'],
            'contratData.observation' => ['sometimes', 'nullable', 'string'],
            'contratData.bulletin_num' => ['sometimes', 'nullable', 'string', 'max:255'],
            'contratData.formule' => ['sometimes', 'nullable', 'string', 'max:255'],
            'contratData.source_data' => ['sometimes', 'nullable', 'in:user,ocr,oneci'],
            'adherentData' => ['sometimes', 'array'],
            'assurerDatas' => ['sometimes', 'array'],
            'assurerDatas.*.uuid_contrat_acteur' => ['sometimes', 'uuid', 'distinct'],
            'assurerDatas.*.is_adherent' => ['sometimes', 'in:oui,non'],
            'assurerDatas.*.info' => ['required', 'array'],
            'assurerDatas.*.sante' => ['sometimes', 'array'],
            'assurerDatas.*.garantie' => ['sometimes', 'array'],
            'assurerDatas.*.garanties' => ['sometimes', 'array'],
            'BeneficiaireDatas' => ['sometimes', 'array'],
            'BeneficiaireDatas.*.uuid_contrat_acteur' => ['sometimes', 'uuid', 'distinct'],
            'BeneficiaireDatas.*.is_adherent' => ['sometimes', 'in:oui,non'],
            'BeneficiaireDatas.*.info' => ['required', 'array'],
            'documentDatas' => ['sometimes', 'array'],
            'documentDatas.*.uuid_document' => ['sometimes', 'uuid', 'distinct'],
            'documentDatas.*.file' => ['sometimes', 'file', 'max:51200'],
            'documentDatas.*.libelle' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        $this->validateNewActorEntries($request->all());

        $updateSections = $request->only([
            'contratData',
            'adherentData',
            'assurerDatas',
            'BeneficiaireDatas',
            'documentDatas',
        ]);
        if (array_filter($updateSections, fn ($section) => is_array($section) && $section !== []) === []) {
            throw ValidationException::withMessages([
                'proposition' => ['Au moins une section à mettre à jour doit être renseignée.'],
            ]);
        }

        $userUuid = $request->user()?->uuid_user;

        if (! $userUuid) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non authentifié.',
                'code' => 401,
            ], 401);
        }

        try {
            $contrat = DB::transaction(function () use ($request, $uuid, $userUuid): Contrat {
                $contrat = $this->propositionService->findAccessibleByUuid($userUuid, $uuid);
                $data = $request->all();

                if (isset($data['contratData'])) {
                    $contractFields = [
                        'date_effet', 'mode_paiement', 'organisme', 'duree', 'code_periodicite',
                        'prime', 'prime_principale', 'sur_prime', 'capital', 'frais_adhesion',
                        'montant_rente', 'periodicite_rente', 'duree_rente', 'code_banque',
                        'code_guichet', 'rib', 'numero_compte', 'numecompte_complet',
                        'autre_ref_prelev', 'agence_uuid', 'code_produit', 'libelle_produit',
                        'formule_produit_code', 'contact_personne_nom', 'contact_personne_mobile',
                        'contact_personne_nom_2', 'contact_personne_mobile_2', 'branch_code',
                        'partner_uuid', 'conseiller_uuid', 'observation', 'bulletin_num', 'formule',
                        'source_data',
                    ];
                    $contractData = array_intersect_key($data['contratData'], array_flip($contractFields));
                    $contractData['updated_by'] = $userUuid;
                    $this->contratService->update($contrat, $contractData);
                }

                if (isset($data['adherentData'])) {
                    $adherentRelation = $contrat->contratActeurs()
                        ->where('type_acteur', 'ADH')
                        ->firstOrFail();
                    $this->updateActor($adherentRelation->acteur, $data['adherentData'], $userUuid);
                    if (array_key_exists('filiation_uuid', $data['adherentData'])) {
                        $adherentRelation->update([
                            'filiation_uuid' => $data['adherentData']['filiation_uuid'],
                            'updated_by' => $userUuid,
                        ]);
                    }
                }

                foreach ($data['assurerDatas'] ?? [] as $assure) {
                    $relation = $this->updateOrCreateActorRelation(
                        $contrat,
                        $assure,
                        'ASS',
                        $userUuid
                    );

                    if (isset($assure['sante'])) {
                        $this->updateHealthDeclaration($contrat, $relation->acteur_uuid, $assure['sante'], $userUuid);
                    }

                    $guarantees = $this->extractGuarantees($assure);
                    if ($guarantees !== null) {
                        AssurerGarantie::query()
                            ->where('uuid_contrat', $contrat->uuid_contrat)
                            ->where('uuid_assurer', $relation->acteur_uuid)
                            ->delete();

                        foreach ($guarantees as $guarantee) {
                            foreach (['uuid_garantie', 'codeGarantie', 'libelle', 'capital', 'prime'] as $requiredField) {
                                if (! array_key_exists($requiredField, $guarantee)) {
                                    throw ValidationException::withMessages([
                                        'assurerDatas' => ["La garantie doit contenir le champ {$requiredField}."],
                                    ]);
                                }
                            }
                            if (! is_numeric($guarantee['capital']) || ! is_numeric($guarantee['prime'])) {
                                throw ValidationException::withMessages([
                                    'assurerDatas' => ['Le capital et la prime de chaque garantie doivent être numériques.'],
                                ]);
                            }
                            if (
                                ! is_string($guarantee['uuid_garantie'])
                                || ! is_string($guarantee['codeGarantie'])
                                || ! is_string($guarantee['libelle'])
                            ) {
                                throw ValidationException::withMessages([
                                    'assurerDatas' => ['Les identifiants, codes et libellés de garantie doivent être textuels.'],
                                ]);
                            }

                            $this->assurerGarantieService->create([
                                'code' => $guarantee['codeGarantie'],
                                'uuid_garantie' => $guarantee['uuid_garantie'],
                                'uuid_assurer' => $relation->acteur_uuid,
                                'uuid_contrat' => $contrat->uuid_contrat,
                                'libelle' => $guarantee['libelle'],
                                'capital' => $guarantee['capital'],
                                'prime' => $guarantee['prime'],
                                'type' => 'ASS',
                            ]);
                        }
                    }
                }

                foreach ($data['BeneficiaireDatas'] ?? [] as $beneficiaire) {
                    $this->updateOrCreateActorRelation(
                        $contrat,
                        $beneficiaire,
                        'BEN',
                        $userUuid
                    );
                }

                foreach ($data['documentDatas'] ?? [] as $documentData) {
                    $file = $documentData['file'] ?? null;
                    $documentUuid = $documentData['uuid_document'] ?? null;

                    if ($documentUuid) {
                        $document = Document::query()
                            ->where('reference_uuid', $contrat->uuid_contrat)
                            ->where('uuid_document', $documentUuid)
                            ->firstOrFail();

                        if ($file instanceof UploadedFile) {
                            $this->documentService->updateDocument($documentUuid, $file, [
                                'libelle' => $documentData['libelle'] ?? null,
                                'update_by' => $userUuid,
                            ]);
                        } elseif (array_key_exists('libelle', $documentData)) {
                            $document->update([
                                'libelle' => $documentData['libelle'],
                                'update_by' => $userUuid,
                            ]);
                        }
                    } elseif ($file instanceof UploadedFile) {
                        $this->documentService->createDocument([
                            'reference_uuid' => $contrat->uuid_contrat,
                            'source' => 'E-SOUSCRIPTION',
                            'created_by' => $userUuid,
                            'documents' => [[
                                'file' => $file,
                                'libelle' => $documentData['libelle'] ?? null,
                            ]],
                        ]);
                    } else {
                        throw ValidationException::withMessages([
                            'documentDatas' => ['Chaque document doit cibler un document existant ou inclure un fichier.'],
                        ]);
                    }
                }

                return $contrat->fresh()->load([
                    'agence',
                    'partner',
                    'contratActeurs.acteur',
                    'contratActeurs.filiation',
                    'documents',
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Proposition mise à jour avec succès.',
                'code' => 200,
                'data' => $contrat,
            ]);
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Proposition introuvable ou inaccessible.',
                'code' => 404,
            ], 404);
        } catch (\Illuminate\Auth\Access\AuthorizationException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
                'code' => 403,
            ], 403);
        } catch (\Throwable $exception) {
            Log::error('Erreur lors de la mise à jour de la proposition : '.$exception->getMessage(), [
                'uuid_contrat' => $uuid,
                'trace' => $exception->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de la proposition.',
                'code' => 500,
            ], 500);
        }
    }

    private function updateActor(Acteur $acteur, array $data, string $userUuid): Acteur
    {
        $fields = [
            'civilite', 'genre', 'nom', 'prenoms', 'date_naissance', 'lieunaissance_code',
            'email', 'mobile', 'telephone', 'numero_piece', 'nni', 'nature_piece',
            'situation_matrimoniale', 'profession_code', 'employeur', 'lieuresidence_code',
            'pays_code',
        ];
        $actorData = array_intersect_key($data, array_flip($fields));
        $actorData['updated_by'] = $userUuid;

        if ($actorData !== ['updated_by' => $userUuid]) {
            $this->acteurService->update($acteur, $actorData);
        }

        return $acteur;
    }

    private function updateOrCreateActorRelation(
        Contrat $contrat,
        array $data,
        string $type,
        string $userUuid
    ): ContratActeur {
        $relationUuid = $data['uuid_contrat_acteur'] ?? null;
        $relation = $relationUuid
            ? $contrat->contratActeurs()
                ->where('uuid_contrat_acteur', $relationUuid)
                ->where('type_acteur', $type)
                ->firstOrFail()
            : null;

        if (($data['is_adherent'] ?? null) === 'oui') {
            $adherentRelation = $contrat->contratActeurs()
                ->where('type_acteur', 'ADH')
                ->firstOrFail();
            $acteur = $adherentRelation->acteur;
        } elseif ($relation) {
            $acteur = $relation->acteur;
        } else {
            $actorData = $data['info'] ?? [];
            $acteur = $this->acteurService->create(array_merge(
                array_intersect_key($actorData, array_flip([
                    'civilite', 'genre', 'nom', 'prenoms', 'date_naissance', 'lieunaissance_code',
                    'email', 'mobile', 'telephone', 'numero_piece', 'nni', 'nature_piece',
                    'situation_matrimoniale', 'profession_code', 'employeur', 'lieuresidence_code',
                    'pays_code',
                ])),
                [
                    'uuid_acteur' => (string) Str::uuid(),
                    'created_by' => $userUuid,
                ]
            ));
        }

        if (isset($data['info'])) {
            $this->updateActor($acteur, $data['info'], $userUuid);
        }

        $filiationUuid = $data['filiation_uuid'] ?? data_get($data, 'info.filiation_uuid');
        if ($relation) {
            $relation->update([
                'acteur_uuid' => $acteur->uuid_acteur,
                'filiation_uuid' => $filiationUuid ?? $relation->filiation_uuid,
                'updated_by' => $userUuid,
            ]);
        } else {
            $relation = $this->contratActeurService->create([
                'uuid_contrat_acteur' => (string) Str::uuid(),
                'contrat_uuid' => $contrat->uuid_contrat,
                'acteur_uuid' => $acteur->uuid_acteur,
                'filiation_uuid' => $filiationUuid,
                'type_acteur' => $type,
                'integration_key' => $contrat->integration_key,
                'created_by' => $userUuid,
            ]);
        }

        return $relation->load('acteur');
    }

    private function updateHealthDeclaration(
        Contrat $contrat,
        string $actorUuid,
        array $data,
        string $userUuid
    ): void {
        $fields = [
            'taille', 'poids', 'tension_min', 'tension_max', 'tabagisme', 'alcool', 'sport',
            'accident', 'traitement', 'transfusion_sanguine', 'intervention_chirurgicale',
            'prochaine_intervention_chirurgicale', 'diabete', 'hypertension', 'drepanocytose',
            'cirrhose_foie', 'maladie_pulmonaire', 'cancer', 'anemie', 'insuffisance_renale', 'avc',
        ];
        $healthData = array_intersect_key($data, array_flip($fields));
        if ($healthData === []) {
            return;
        }

        $declaration = DeclarationSante::query()
            ->where('contrat_uuid', $contrat->uuid_contrat)
            ->where('assure_uuid', $actorUuid)
            ->first();

        if ($declaration) {
            if ($healthData !== []) {
                $healthData['update_by'] = $userUuid;
                $this->declarationSanteService->update($declaration, $healthData);
            }

            return;
        }

        $this->declarationSanteService->create(array_merge($healthData, [
            'contrat_uuid' => $contrat->uuid_contrat,
            'assure_uuid' => $actorUuid,
            'created_by' => $userUuid,
        ]));
    }

    private function extractGuarantees(array $assure): ?array
    {
        $guarantees = data_get($assure, 'garanties.garantieData')
            ?? data_get($assure, 'garantie.garantieData')
            ?? ($assure['garanties'] ?? $assure['garantie'] ?? null);

        if ($guarantees === null) {
            return null;
        }

        if (! is_array($guarantees)) {
            throw ValidationException::withMessages([
                'assurerDatas' => ['Les garanties doivent être transmises sous forme de liste.'],
            ]);
        }

        if (isset($guarantees['garantieData'])) {
            $guarantees = $guarantees['garantieData'];
        }

        if (isset($guarantees['garantieObligatoire']) || isset($guarantees['garantieOptionnel'])) {
            if (
                ! is_array($guarantees['garantieObligatoire'] ?? [])
                || ! is_array($guarantees['garantieOptionnel'] ?? [])
            ) {
                throw ValidationException::withMessages([
                    'assurerDatas' => ['Les catégories de garanties doivent être des listes.'],
                ]);
            }

            $guarantees = array_merge(
                $guarantees['garantieObligatoire'] ?? [],
                $guarantees['garantieOptionnel'] ?? []
            );
        }

        if (isset($guarantees['codeGarantie'])) {
            $guarantees = [$guarantees];
        }

        if (! is_array($guarantees) || array_filter($guarantees, fn ($guarantee) => ! is_array($guarantee)) !== []) {
            throw ValidationException::withMessages([
                'assurerDatas' => ['Les garanties doivent être une liste de garanties.'],
            ]);
        }

        return $guarantees;
    }

    private function validateNewActorEntries(array $data): void
    {
        $requiredActorFields = [
            'civilite', 'genre', 'nom', 'prenoms', 'date_naissance', 'lieunaissance_code',
            'email', 'mobile', 'telephone', 'numero_piece', 'nni', 'nature_piece',
            'situation_matrimoniale', 'profession_code', 'employeur', 'lieuresidence_code',
            'pays_code',
        ];
        $errors = [];

        foreach (['assurerDatas', 'BeneficiaireDatas'] as $section) {
            foreach ($data[$section] ?? [] as $index => $entry) {
                if (! empty($entry['uuid_contrat_acteur']) || ($entry['is_adherent'] ?? null) === 'oui') {
                    continue;
                }

                foreach ($requiredActorFields as $field) {
                    if (! array_key_exists($field, $entry['info'] ?? [])) {
                        $errors["{$section}.{$index}.info.{$field}"][] = "Le champ {$field} est obligatoire pour créer un acteur.";
                    }
                }
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    public function storeSouscription(Request $request, ?string $draftUuid = null)
    {
        $userUuid = $request->user()?->uuid_user;
        if ($draftUuid !== null && !$userUuid) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non authentifié.',
                'code' => 401,
            ], 401);
        }

        try {
            $result = DB::transaction(function () use ($request, $draftUuid) {

                if ($draftUuid !== null) {
                    $accessibleDraft = $this->propositionService->findAccessibleByUuid(
                        $request->user()->uuid_user,
                        $draftUuid
                    );
                    $draft = Contrat::query()->lockForUpdate()->findOrFail($accessibleDraft->uuid_contrat);
                } else {
                    $draft = null;
                }

                if ($draft && (int) $draft->etape !== 0) {
                    throw ValidationException::withMessages([
                        'uuid' => 'Ce contrat n’est plus un brouillon et ne peut pas être complété ici.',
                    ]);
                }

                $contratUuid = $draft?->uuid_contrat ?? Str::uuid()->toString();
                $keyIntegration = now()->format('Ymdh');


                $adherentData      = $request->adherentData ?? [];
                $assurerDatas      = $request->assurerDatas ?? [];
                $BeneficiaireDatas = $request->BeneficiaireDatas ?? [];
                $DocumentDatas     = $request->documentDatas ?? [];
                $contratData       = $request->contratData ?? [];
                if ($draft) {
                    $contratData = array_merge([
                        'code_produit' => $draft->code_produit,
                        'libelle_produit' => $draft->libelle_produit,
                        'formule_produit_code' => $draft->formule_produit_code,
                        'etape' => 1,
                    ], $contratData);
                    $contratData['etape'] = 1;
                }

                $createdBy = $request->user()?->uuid_user ?? $adherentData['created_by'] ?? 'system';

                if (isset($contratData['etape']) && $contratData['etape'] !== '') {

                    if ($contratData['etape'] == '3') {

                        $dateTransmission = now()->format('Y-m-d H:i:s');
                        $transmisPar = $createdBy;
                        $dateAcceptee = now()->format('Y-m-d H:i:s');
                        $accepted_by = $createdBy;

                    } elseif ($contratData['etape'] == '2') {

                        $dateTransmission = now()->format('Y-m-d H:i:s');
                        $transmisPar = $createdBy;

                    } else {

                        $dateTransmission = null;
                        $transmisPar = null;
                        $dateAcceptee = null;
                        $accepted_by = null;
                    }
                }
                

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
                    'filiation_uuid'      => $adherentData['filiation_uuid'] ?? null,
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
                    $assureGaranties = data_get($assure, 'garanties.garantieData')
                        ?? data_get($assure, 'garantie.garantieData')
                        ?? ($assure['garanties'] ?? $assure['garantie'] ?? []);
                    if (isset($assureGaranties['garantieData'])) {
                        $assureGaranties = $assureGaranties['garantieData'];
                    }
                    if (
                        is_array($assureGaranties)
                        && (
                            array_key_exists('garantieObligatoire', $assureGaranties)
                            || array_key_exists('garantieOptionnel', $assureGaranties)
                        )
                    ) {
                        $garantiesObligatoires = $assureGaranties['garantieObligatoire'] ?? [];
                        $garantiesOptionnelles = $assureGaranties['garantieOptionnel'] ?? [];
                        if (!is_array($garantiesObligatoires) || !is_array($garantiesOptionnelles)) {
                            throw new \InvalidArgumentException('Les garanties de l’assuré doivent être des listes.');
                        }
                        $assureGaranties = array_merge($garantiesObligatoires, $garantiesOptionnelles);
                    }
                    if (isset($assureGaranties['codeGarantie'])) {
                        $assureGaranties = [$assureGaranties];
                    }
                    if (!is_array($assureGaranties)) {
                        throw new \InvalidArgumentException('Les garanties de l’assuré doivent être une liste.');
                    }
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
                            'filiation_uuid'      => $adherentData['filiation_uuid'] ?? null,
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
                            'filiation_uuid'      => $assureInfo['filiation_uuid'] ?? null,
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

                    foreach ($assureGaranties as $assureGarantie) {
                        if (
                            !is_array($assureGarantie)
                            || !isset(
                                $assureGarantie['uuid_garantie'],
                                $assureGarantie['codeGarantie'],
                                $assureGarantie['libelle'],
                                $assureGarantie['capital'],
                                $assureGarantie['prime']
                            )
                        ) {
                            throw new \InvalidArgumentException('Une garantie de l’assuré est incomplète.');
                        }

                        $this->assurerGarantieService->create([
                            'code'          => $assureGarantie['codeGarantie'],
                            'uuid_garantie' => $assureGarantie['uuid_garantie'],
                            'uuid_assurer'  => $uuidAssure,
                            'uuid_contrat'  => $contratUuid,
                            'libelle'       => $assureGarantie['libelle'],
                            'capital'       => $assureGarantie['capital'],
                            'prime'         => $assureGarantie['prime'],
                            'type'         => "ASS",
                        ]);
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
                            'filiation_uuid'      => $beneficiaire['filiation_uuid'] ?? null,
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
                            'filiation_uuid'      => $beneficiaire['filiation_uuid'] ?? null,
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

                if ($DocumentDatas !== [] && !$documentStore) {
                    throw new \Exception("Échec de l'enregistrement des documents.");
                }

                // ==================== 5. Contrat ====================
                $contratPayload = [
                    'uuid_contrat'                  => $contratUuid,
                    'date_effet'                    => $contratData['date_effet'] ?? null,
                    'mode_paiement'                 => $contratData['mode_paiement'] ?? null,
                    'organisme'                     => $contratData['organisme'] ?? null,
                    'duree'                         => $contratData['duree'] ?? null,
                    'code_periodicite'              => $contratData['code_periodicite'] ?? null,
                    'prime'                         => $contratData['prime'] ?? null,
                    'prime_principale'              => $contratData['prime_principale'] ?? null,
                    'sur_prime'                     => $contratData['sur_prime'] ?? null,
                    'capital'                       => $contratData['capital'] ?? null,
                    'frais_adhesion'                => $contratData['frais_adhesion'] ?? null,
                    'montant_rente'                 => $contratData['montant_rente'] ?? null,
                    'periodicite_rente'             => $contratData['periodicite_rente'] ?? null,
                    'duree_rente'                   => $contratData['duree_rente'] ?? null,
                    'code_banque'                   => $contratData['code_banque'] ?? null,
                    'code_guichet'                  => $contratData['code_guichet'] ?? null,
                    'rib'                           => $contratData['rib'] ?? null,
                    'numero_compte'                 => $contratData['numero_compte'] ?? null,
                    'numecompte_complet'            => $contratData['numecompte_complet'] ?? null,
                    'autre_ref_prelev'              => $contratData['autre_ref_prelev'] ?? null,
                    'agence_uuid'                   => $contratData['agence_uuid'] ?? null,
                    'code_produit'                  => $contratData['code_produit'] ?? null,
                    'libelle_produit'               => $contratData['libelle_produit'] ?? null,
                    'etape'                         => $contratData['etape'] ?? null,
                    'formule_produit_code'          => $contratData['formule_produit_code'] ?? null,
                    'contact_personne_nom'          => $contratData['contact_personne_nom'] ?? null,
                    'contact_personne_mobile'       => $contratData['contact_personne_mobile'] ?? null,
                    'contact_personne_nom_2'        => $contratData['contact_personne_nom_2'] ?? null,
                    'contact_personne_mobile_2'     => $contratData['contact_personne_mobile_2'] ?? null,
                    'branch_code'                   => $contratData['branch_code'] ?? null,
                    'partner_uuid'                  => $contratData['partner_uuid'] ?? null,
                    'conseiller_uuid'               => $contratData['conseiller_uuid'] ?? null,
                    'is_paid'                       => $contratData['is_paid'] ?? null,
                    'observation'                   => $contratData['observation'] ?? null,
                    'bulletin_num'                  => $contratData['bulletin_num'] ?? null,
                    'formule'                       => $contratData['formule'] ?? null,
                    'source_data'                   => $contratData['source_data'] ?? null,
                    'integration_key'               => $keyIntegration,
                    'created_by'                    => $draft?->created_by ?? $createdBy,
                    'date_transmission'             => $dateTransmission ?? null,
                    'transmis_par'                  => $transmisPar ?? null,
                    'acceptance_date'             => $dateAcceptee ?? null,
                    'accepted_by'                  => $accepted_by ?? null,
                ];

                if ($draft) {
                    $contratPayload['updated_by'] = $createdBy;
                    $contratStore = $this->contratService->update($draft, $contratPayload);
                } else {
                    $contratStore = $this->contratService->create($contratPayload);
                }

                if (!$contratStore) {
                    throw new \Exception("Échec de la création du contrat.");
                }

                return [
                    'contratData'     => $contratStore,
                    // 'key_integration' => $keyIntegration,
                    // 'contrat_uuid'    => $contratUuid,
                    'documentStore'   => $documentStore,
                    'adherentEmail'   => $adherentData['email'] ?? null,
                ];
            });

            

            return response()->json([
                'success'         => true,
                'message'         => 'Souscription créée avec succès',
                'code'            => 200,
                'contratData'     => $result['contratData'],
                'documentStore'   => $result['documentStore'],
            ]);

        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Brouillon introuvable.',
                'code' => 404,
            ], 404);
        } catch (\Illuminate\Auth\Access\AuthorizationException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
                'code' => 403,
            ], 403);
        } catch (ValidationException $exception) {
            throw $exception;
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
