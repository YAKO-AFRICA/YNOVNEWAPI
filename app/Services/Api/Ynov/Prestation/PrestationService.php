<?php
// app/Services/Api/Ynov/Prestation/PrestationService.php

namespace App\Services\Api\Ynov\Prestation;

use App\Models\Api\Ynov\Prestation;
use App\Models\Api\Ynov\parameter\Produit;
use App\Models\Api\Ynov\parameter\ProduitPrestation;
use App\Models\Api\Ynov\parameter\TypePrestation;
use App\Models\Api\Ynov\parameter\CategoryTypePrestation;
use App\Models\Api\Ynov\parameter\ActivityLog;
use App\Models\Api\Ynov\parameter\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PrestationService
{
    /**
     * Créer une catégorie de prestation
     */
    public function createCategory(array $data, string $creatorUuid): CategoryTypePrestation
    {
        return DB::transaction(function () use ($data, $creatorUuid) {
            // Vérifier si le code existe déjà
            if (isset($data['code']) && CategoryTypePrestation::where('code', $data['code'])->exists()) {
                throw ValidationException::withMessages([
                    'code' => ['Ce code est déjà utilisé.']
                ]);
            }

            $category = CategoryTypePrestation::create([
                'uuid_category_type_prestations' => (string) Str::uuid(),
                'code' => $data['code'] ?? Str::slug($data['libelle'], '_'),
                'libelle' => $data['libelle'],
                'description' => $data['description'] ?? null,
                'status' => $data['status'] ?? 'actif',
                'created_by' => $creatorUuid,
            ]);

            ActivityLog::log([
                'user_uuid' => $creatorUuid,
                'action' => 'create',
                'action_type' => 'crud',
                'module' => 'category_type_prestations',
                'description' => "Création de la catégorie de prestation : {$category->libelle}",
                'resource_type' => 'category_type_prestation',
                'resource_id' => $category->uuid_category_type_prestations,
                'new_values' => $category->toArray(),
                'level' => 'info',
            ]);

            return $category;
        });
    }

    /**
     * Mettre à jour une catégorie
     */
    public function updateCategory(CategoryTypePrestation $category, array $data, string $updaterUuid): CategoryTypePrestation
    {
        return DB::transaction(function () use ($category, $data, $updaterUuid) {
            // Vérifier si le code existe déjà (pour une autre catégorie)
            if (isset($data['code']) && 
                CategoryTypePrestation::where('code', $data['code'])
                    ->where('uuid_category_type_prestations', '!=', $category->uuid_category_type_prestations)
                    ->exists()) {
                throw ValidationException::withMessages([
                    'code' => ['Ce code est déjà utilisé.']
                ]);
            }

            $oldValues = $category->toArray();

            $category->update([
                'code' => $data['code'] ?? $category->code,
                'libelle' => $data['libelle'] ?? $category->libelle,
                'description' => $data['description'] ?? $category->description,
                'status' => $data['status'] ?? $category->status,
                'updated_by' => $updaterUuid,
            ]);

            ActivityLog::log([
                'user_uuid' => $updaterUuid,
                'action' => 'update',
                'action_type' => 'crud',
                'module' => 'category_type_prestations',
                'description' => "Mise à jour de la catégorie : {$category->libelle}",
                'resource_type' => 'category_type_prestation',
                'resource_id' => $category->uuid_category_type_prestations,
                'old_values' => $oldValues,
                'new_values' => $category->toArray(),
                'level' => 'info',
            ]);

            return $category->fresh();
        });
    }

    /**
     * Supprimer une catégorie
     */
    public function deleteCategory(CategoryTypePrestation $category, string $deleterUuid): void
    {
        // Vérifier si la catégorie contient des types de prestations
        if ($category->typePrestations()->count() > 0) {
            throw new \RuntimeException('Cette catégorie contient des types de prestations et ne peut pas être supprimée.');
        }

        $category->update([
            'status' => 'inactif',
            'deleted_by' => $deleterUuid,
        ]);

        $category->delete();

        ActivityLog::log([
            'user_uuid' => $deleterUuid,
            'action' => 'delete',
            'action_type' => 'crud',
            'module' => 'category_type_prestations',
            'description' => "Suppression de la catégorie : {$category->libelle}",
            'resource_type' => 'category_type_prestation',
            'resource_id' => $category->uuid_category_type_prestations,
            'level' => 'warning',
        ]);
    }

    /**
     * Créer un type de prestation
     */
    public function createTypePrestation(array $data, string $creatorUuid): TypePrestation
    {
        return DB::transaction(function () use ($data, $creatorUuid) {
            // Vérifier si le code existe déjà
            if (isset($data['code']) && TypePrestation::where('code', $data['code'])->exists()) {
                throw ValidationException::withMessages([
                    'code' => ['Ce code est déjà utilisé.']
                ]);
            }

            $typePrestation = TypePrestation::create([
                'uuid_type_prestation' => (string) Str::uuid(),
                'code' => $data['code'] ?? Str::slug($data['libelle'], '_'),
                'libelle' => $data['libelle'],
                'description' => $data['description'] ?? null,
                'category_uuid' => $data['category_uuid'],
                'impact' => $data['impact'] ?? TypePrestation::IMPACT_NON_SORTIE_PORTEFEUILLE,
                'delai_traitement' => $data['delai_traitement'] ?? null,
                'status' => $data['status'] ?? 'actif',
                'created_by' => $creatorUuid,
            ]);

            ActivityLog::log([
                'user_uuid' => $creatorUuid,
                'action' => 'create',
                'action_type' => 'crud',
                'module' => 'type_prestations',
                'description' => "Création du type de prestation : {$typePrestation->libelle}",
                'resource_type' => 'type_prestation',
                'resource_id' => $typePrestation->uuid_type_prestation,
                'new_values' => $typePrestation->toArray(),
                'level' => 'info',
            ]);

            return $typePrestation;
        });
    }

    /**
     * Mettre à jour un type de prestation
     */
    public function updateTypePrestation(TypePrestation $typePrestation, array $data, string $updaterUuid): TypePrestation
    {
        return DB::transaction(function () use ($typePrestation, $data, $updaterUuid) {
            // Vérifier si le code existe déjà (pour un autre type)
            if (isset($data['code']) && 
                TypePrestation::where('code', $data['code'])
                    ->where('uuid_type_prestation', '!=', $typePrestation->uuid_type_prestation)
                    ->exists()) {
                throw ValidationException::withMessages([
                    'code' => ['Ce code est déjà utilisé.']
                ]);
            }

            $oldValues = $typePrestation->toArray();

            $typePrestation->update([
                'code' => $data['code'] ?? $typePrestation->code,
                'libelle' => $data['libelle'] ?? $typePrestation->libelle,
                'description' => $data['description'] ?? $typePrestation->description,
                'category_uuid' => $data['category_uuid'] ?? $typePrestation->category_uuid,
                'impact' => $data['impact'] ?? $typePrestation->impact,
                'delai_traitement' => $data['delai_traitement'] ?? $typePrestation->delai_traitement,
                'status' => $data['status'] ?? $typePrestation->status,
                'updated_by' => $updaterUuid,
            ]);

            ActivityLog::log([
                'user_uuid' => $updaterUuid,
                'action' => 'update',
                'action_type' => 'crud',
                'module' => 'type_prestations',
                'description' => "Mise à jour du type de prestation : {$typePrestation->libelle}",
                'resource_type' => 'type_prestation',
                'resource_id' => $typePrestation->uuid_type_prestation,
                'old_values' => $oldValues,
                'new_values' => $typePrestation->toArray(),
                'level' => 'info',
            ]);

            return $typePrestation->fresh();
        });
    }

    /**
     * Supprimer un type de prestation
     */
    public function deleteTypePrestation(TypePrestation $typePrestation, string $deleterUuid): void
    {
        // Vérifier si le type de prestation est associé à des produits
        if ($typePrestation->produits()->count() > 0) {
            throw new \RuntimeException('Ce type de prestation est associé à des produits et ne peut pas être supprimé.');
        }

        $typePrestation->update([
            'status' => 'inactif',
            'deleted_by' => $deleterUuid,
        ]);

        $typePrestation->delete();

        ActivityLog::log([
            'user_uuid' => $deleterUuid,
            'action' => 'delete',
            'action_type' => 'crud',
            'module' => 'type_prestations',
            'description' => "Suppression du type de prestation : {$typePrestation->libelle}",
            'resource_type' => 'type_prestation',
            'resource_id' => $typePrestation->uuid_type_prestation,
            'level' => 'warning',
        ]);
    }

    /**
     * Associer une ou plusieurs prestations à un produit
     */
    public function assignPrestations(Produit $produit, array $data, string $creatorUuid): array
    {
        return DB::transaction(function () use ($produit, $data, $creatorUuid) {
            $associations = [];
            $prestationUuids = $data['type_prestation_uuids'] ?? [];
            $produitType = $produit->typeProduit->libelle ?? null;
            $status = $data['status'] ?? 'actif';

            if (empty($prestationUuids)) {
                throw new \RuntimeException('Aucune prestation sélectionnée.');
            }

            // Récupérer tous les types de prestations en une seule requête
            $typePrestations = TypePrestation::whereIn('uuid_type_prestation', $prestationUuids)
                ->where('status', 'actif')
                ->get()
                ->keyBy('uuid_type_prestation');

            // Vérifier que toutes les prestations existent
            $missing = array_diff($prestationUuids, $typePrestations->keys()->toArray());
            if (!empty($missing)) {
                throw new \RuntimeException('Certaines prestations sélectionnées n\'existent pas ou sont inactives.');
            }

            // Récupérer les associations déjà existantes
            $existingAssociations = ProduitPrestation::where('produit_uuid', $produit->uuid_produit)
                ->whereIn('type_prestation_uuid', $prestationUuids)
                ->pluck('type_prestation_uuid')
                ->toArray();

            $existingUuids = [];

            foreach ($typePrestations as $uuid => $typePrestation) {
                // Vérifier si l'association existe déjà
                if (in_array($uuid, $existingAssociations)) {
                    $existingUuids[] = $uuid;
                    continue;
                }

                $association = ProduitPrestation::create([
                    'uuid_product_prestation' => (string) Str::uuid(),
                    'produit_uuid' => $produit->uuid_produit,
                    'produit_type' => $produitType,
                    'type_prestation_uuid' => $uuid,
                    'status' => $status,
                    'created_by' => $creatorUuid,
                ]);

                $associations[] = $association;

                ActivityLog::log([
                    'user_uuid' => $creatorUuid,
                    'action' => 'assign',
                    'action_type' => 'crud',
                    'module' => 'produit_prestations',
                    'description' => "Association de la prestation {$typePrestation->libelle} au produit {$produit->libelle}",
                    'resource_type' => 'produit_prestation',
                    'resource_id' => $association->uuid_product_prestation,
                    'new_values' => $association->toArray(),
                    'level' => 'info',
                ]);
            }

            // Si des prestations étaient déjà associées, les retourner dans le message
            if (!empty($existingUuids) && empty($associations)) {
                throw new \RuntimeException('Toutes les prestations sélectionnées sont déjà associées à ce produit.');
            }

            return [
                'associations' => $associations,
                'already_assigned' => $existingUuids,
                'assigned_count' => count($associations),
                'skipped_count' => count($existingUuids),
            ];
        });
    }

    /**
     * Associer une seule prestation à un produit (méthode existante)
     */
    public function assignPrestation(Produit $produit, TypePrestation $typePrestation, array $data, string $creatorUuid): ProduitPrestation
    {
        return DB::transaction(function () use ($produit, $typePrestation, $data, $creatorUuid) {
            // Vérifier si l'association existe déjà
            $existing = ProduitPrestation::where('produit_uuid', $produit->uuid_produit)
                ->where('type_prestation_uuid', $typePrestation->uuid_type_prestation)
                ->first();

            if ($existing) {
                throw new \RuntimeException('Cette prestation est déjà associée à ce produit.');
            }

            $association = ProduitPrestation::create([
                'uuid_product_prestation' => (string) Str::uuid(),
                'produit_uuid' => $produit->uuid_produit,
                'produit_type' => $produit->typeProduit->libelle ?? null,
                'type_prestation_uuid' => $typePrestation->uuid_type_prestation,
                'status' => $data['status'] ?? 'actif',
                'created_by' => $creatorUuid,
            ]);

            ActivityLog::log([
                'user_uuid' => $creatorUuid,
                'action' => 'assign',
                'action_type' => 'crud',
                'module' => 'produit_prestations',
                'description' => "Association de la prestation {$typePrestation->libelle} au produit {$produit->libelle}",
                'resource_type' => 'produit_prestation',
                'resource_id' => $association->uuid_product_prestation,
                'new_values' => $association->toArray(),
                'level' => 'info',
            ]);

            return $association;
        });
    }

    /**
     * Retirer une prestation d'un produit
     */
    public function removePrestation(ProduitPrestation $association, string $removerUuid): void
    {
        $association->update([
            'status' => 'inactif',
            'deleted_by' => $removerUuid,
        ]);

        $association->delete();

        ActivityLog::log([
            'user_uuid' => $removerUuid,
            'action' => 'remove',
            'action_type' => 'crud',
            'module' => 'produit_prestations',
            'description' => "Retrait de la prestation du produit",
            'resource_type' => 'produit_prestation',
            'resource_id' => $association->uuid_product_prestation,
            'level' => 'info',
        ]);
    }

    /**
     * Récupérer les catégories avec leurs types de prestations
     */
    public function getCategoriesWithTypes(array $filters = [], int $perPage = 20)
    {
        $query = CategoryTypePrestation::with(['typePrestations' => function ($q) {
            $q->where('status', 'actif')->orderBy('libelle', 'DESC');
            // $q->where('status', 'actif')->orderBy('libelle');
        }]);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['search'])) {
            $query->search($filters['search']);
        }

        return $query->orderBy('libelle')->paginate($perPage);
    }

    /**
     * Récupérer les types de prestations disponibles pour un produit
     */
    public function getAvailableTypesForProduit(string $produitUuid, array $filters = [])
    {
        $query = TypePrestation::whereDoesntHave('produits', function ($q) use ($produitUuid) {
            $q->where('produit_uuid', $produitUuid);
        })->where('status', 'actif');

        if (isset($filters['category_uuid'])) {
            $query->where('category_uuid', $filters['category_uuid']);
        }

        if (isset($filters['search'])) {
            $query->search($filters['search']);
        }

        return $query->orderBy('libelle')->get();
    }

    /**
     * Statistiques des prestations
     */
    public function getStats(): array
    {
        return [
            'categories_total' => CategoryTypePrestation::count(),
            'categories_active' => CategoryTypePrestation::active()->count(),
            'types_total' => TypePrestation::count(),
            'types_active' => TypePrestation::active()->count(),
            'associations_total' => ProduitPrestation::count(),
            'associations_active' => ProduitPrestation::active()->count(),
        ];
    }

    /**
     * Liste des prestations
     */
    public function getPrestations(array $filters = [], int $perPage = 20)
    {
        $query = Prestation::with([
            'client',
            'typePrestation.category',
            'rdv',
            'gestionnaire',
            'partner',
        ]);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['client_uuid'])) {
            $query->where('client_uuid', $filters['client_uuid']);
        }

        if (isset($filters['type_prestation_uuid'])) {
            $query->where('type_prestation_uuid', $filters['type_prestation_uuid']);
        }

        if (isset($filters['gestionnaire_uuid'])) {
            $query->where('gestionnaire_uuid', $filters['gestionnaire_uuid']);
        }

        if (isset($filters['partner_uuid'])) {
            $query->where('partner_uuid', $filters['partner_uuid']);
        }

        if (isset($filters['is_migrated'])) {
            $query->where('is_migrated', (bool) $filters['is_migrated']);
        }

        if (isset($filters['search'])) {
            $query->search($filters['search']);
        }

        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    /**
     * Trouver une prestation par UUID
     */
    public function findPrestation(string $uuid): Prestation
    {
        return Prestation::with([
            'client',
            'typePrestation.category',
            'rdv',
            'gestionnaire',
            'partner',
        ])->where('uuid_prestation', $uuid)->firstOrFail();
    }

    /**
     * Créer une prestation
     */
    public function createPrestation(array $data, string $creatorUuid): Prestation
    {
        return DB::transaction(function () use ($data, $creatorUuid) {

            $prestation = Prestation::create([
                'uuid_prestation' => (string) Str::uuid(),
                'code' => RefgenerateCode(Prestation::class, 'PREST-', 'code'),
                'client_uuid' => $data['client_uuid'] ?? $creatorUuid,
                'type_prestation_uuid' => $data['type_prestation_uuid'],
                'id_contrat' => $data['id_contrat'],
                'rdv_uuid' => $data['rdv_uuid'] ?? null,
                'montant' => (float) $data['montant'] ?? 0,
                'mode_paiement' => $data['mode_paiement'] ?? null,
                'operateur_mobile' => $data['operateur_mobile'] ?? null,
                'tel_paiement_1' => $data['tel_paiement_1'] ?? null,
                'tel_paiement_2' => $data['tel_paiement_2'] ?? null,
                'code_banque' => $data['code_banque'] ?? null,
                'code_guichet' => $data['code_guichet'] ?? null,
                'numero_compte' => $data['numero_compte'] ?? null,
                'cle_rib' => $data['cle_rib'] ?? null,
                'ville_declaration' => $data['ville_declaration'] ?? null,
                'partner_uuid' => $data['partner_uuid'] ?? null,
                'status' => $data['status'] ?? 'en_attente',
                'notes' => $data['notes'] ?? null,
                'created_by' => $creatorUuid,
            ]);

            ActivityLog::log([
                'user_uuid' => $creatorUuid,
                'action' => 'create',
                'action_type' => 'crud',
                'module' => 'prestations',
                'description' => "Création de la prestation {$prestation->code} pour le client {$prestation->client_uuid}",
                'resource_type' => 'prestation',
                'resource_id' => $prestation->uuid_prestation,
                'new_values' => $prestation->toArray(),
                'level' => 'info',
            ]);

            return $prestation->fresh()->load([
                'client',
                'typePrestation.category',
                'rdv',
                'gestionnaire',
                'partner',
            ]);
        });
    }

    /**
     * Mettre à jour une prestation
     */
    public function updatePrestation(Prestation $prestation, array $data, string $updaterUuid): Prestation
    {
        return DB::transaction(function () use ($prestation, $data, $updaterUuid) {
            $prestation->update(array_merge($data, [
                'updated_by' => $updaterUuid,
            ]));

            return $prestation->fresh()->load([
                'client',
                'typePrestation.category',
                'rdv',
                'gestionnaire',
                'partner',
            ]);
        });
    }

    /**
     * Supprimer une prestation
     */
    public function deletePrestation(Prestation $prestation, string $deleterUuid): void
    {
        DB::transaction(function () use ($prestation, $deleterUuid) {
            $prestation->update([
                'deleted_by' => $deleterUuid,
            ]);

            $prestation->delete();
        });
    }

    /**
     * Statistiques des prestations
     */
    public function getPrestationStats(): array
    {
        return [
            'total' => Prestation::count(),
            'inacheve' => Prestation::where('status', 'inacheve')->count(),
            'en_attente' => Prestation::where('status', 'en_attente')->count(),
            'transmis' => Prestation::where('status', 'transmis')->count(),
            'accepte' => Prestation::where('status', 'accepte')->count(),
            'rejete' => Prestation::where('status', 'rejete')->count(),
            'annule' => Prestation::where('status', 'annule')->count(),
            'migrated' => Prestation::where('is_migrated', true)->count(),
            'not_migrated' => Prestation::where('is_migrated', false)->count(),
        ];
    }

    /**
     * Récupérer tous les gestionnaires avec le rôle gestionnaire_prestation
     */
    public function getGestionnairesPrestation(): array
    {
        $gestionnaires = User::whereHas('role', function ($query) {
            $query->where('code', 'gestionnaire_prestation');
        })
        ->with(['details', 'agences'])
        ->where('status', 'actif')
        ->get()
        ->map(function ($gestionnaire) {
            return [
                'uuid_user' => $gestionnaire->uuid_user,
                'login' => $gestionnaire->login,
                'email' => $gestionnaire->email,
                'nom' => $gestionnaire->details?->nom,
                'prenoms' => $gestionnaire->details?->prenoms,
                'full_name' => trim(($gestionnaire->details?->nom ?? '') . ' ' . ($gestionnaire->details?->prenoms ?? '')),
                'mobile' => $gestionnaire->details?->mobile_1 ?? $gestionnaire->details?->mobile_2 ?? null,
                'agences' => $gestionnaire->agences->map(function ($agence) {
                    return [
                        'uuid_agence' => $agence->uuid_agence,
                        'code' => $agence->code,
                        'libelle' => $agence->libelle,
                        'ville' => $agence->ville,
                    ];
                }),
            ];
        });

        return $gestionnaires->toArray();
    }

    /**
     * Calculer le montant maximum disponible pour une prestation (15% du cumul des cotisations à terme)
     */
    public function calculateMaximumAmount(int $idContrat): array
    {
        $encaissementService = new \App\Services\EncaissementBisService();
        $contratData = $encaissementService->getContrat($idContrat);

        if (!$contratData['success']) {
            return [
                'success' => false,
                'code' => $contratData['code'] ?? 'CONTRACT_ERROR',
                'message' => $contratData['message'] ?? 'Erreur lors de la récupération du contrat',
                'montant_max' => 0,
            ];
        }

        $details = $contratData['data']['details'][0] ?? null;
        if (!$details) {
            return [
                'success' => false,
                'code' => 'CONTRACT_DETAILS_ERROR',
                'message' => 'Détails du contrat non disponibles',
                'montant_max' => 0,
            ];
        }

        $montantMax = $details['ContisationQuinzePourcent'] ?? 0;

        return [
            'success' => true,
            'code' => 'MAX_AMOUNT_CALCULATED',
            'message' => 'Montant maximum calculé avec succès',
            'montant_max' => (float) $montantMax,
            'details' => [
                'cumul_cotisation_terme' => $details['CumulCotisationTerme'] ?? 0,
                'duree_cotisation_mois' => $details['DureeCotisationMois'] ?? 0,
                'prime' => $details['TotalPrime'] ?? 0,
                'periodicite' => $details['periodicite'] ?? null,
            ],
        ];
    }

    /**
     * Récupérer les motifs de prestations pour un produit avec le montant maximum
     */
    public function getMotifsWithMaxAmount(string $codeProduit, int $idContrat, ?string $categoryUuid = null): array
    {
        $produit = Produit::where('code', $codeProduit)->first();
        if (!$produit) {
            return [
                'success' => false,
                'code' => 'PRODUCT_NOT_FOUND',
                'message' => 'Produit non trouvé',
                'motifs' => [],
                'montant_max' => 0,
            ];
        }

        // Calculer le montant maximum
        $maxAmountData = $this->calculateMaximumAmount($idContrat);

        // Construire la requête pour les motifs
        $query = $produit->typePrestations()
            ->wherePivot('status', 'actif')
            ->where('type_prestations.status', 'actif')
            ->with('category')
            ->orderBy('type_prestations.libelle');

        // Filtrer par catégorie si spécifié
        if ($categoryUuid !== null) {
            $query->where('type_prestations.category_uuid', $categoryUuid);
        }

        $prestations = $query->get();

        return [
            'success' => true,
            'code' => 'MOTIFS_WITH_MAX_AMOUNT',
            'message' => 'Motifs récupérés avec montant maximum',
            'motifs' => $prestations->map(function ($prestation) {
                return [
                    'uuid_type_prestation' => $prestation->uuid_type_prestation,
                    'code' => $prestation->code,
                    'libelle' => $prestation->libelle,
                    'description' => $prestation->description,
                    'impact' => $prestation->impact,
                    'impact_label' => $prestation->getImpactLabel(),
                    'category' => $prestation->category ? [
                        'uuid' => $prestation->category->uuid_category_type_prestations,
                        'libelle' => $prestation->category->libelle,
                    ] : null,
                ];
            })->toArray(),
            'montant_max' => $maxAmountData['montant_max'] ?? 0,
            'details_montant' => $maxAmountData['details'] ?? [],
        ];
    }

    /**
     * Vérifier si un motif de prestation nécessite une prise de rendez-vous
     */
    public function checkMotifRequiresAppointment(string $typePrestationUuid): array
    {
        $typePrestation = TypePrestation::where('uuid_type_prestation', $typePrestationUuid)
            ->where('status', 'actif')
            ->first();

        if (!$typePrestation) {
            return [
                'success' => false,
                'code' => 'MOTIF_NOT_FOUND',
                'message' => 'Motif de prestation non trouvé',
                'requires_appointment' => false,
            ];
        }

        $requiresAppointment = $typePrestation->impact === TypePrestation::IMPACT_SORTIE_PORTEFEUILLE;

        return [
            'success' => true,
            'code' => 'MOTIF_CHECKED',
            'message' => $requiresAppointment 
                ? 'Ce motif nécessite une prise de rendez-vous' 
                : 'Ce motif ne nécessite pas de prise de rendez-vous',
            'requires_appointment' => $requiresAppointment,
            'impact' => $typePrestation->impact,
            'impact_label' => $typePrestation->getImpactLabel(),
        ];
    }

}