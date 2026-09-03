<?php
// app/Services/Api/Ynov/ProduitService.php

namespace App\Services\Api\Ynov;

use App\Models\Api\Ynov\parameter\Produit;
use App\Models\Api\Ynov\parameter\ProduitFormule;
use App\Models\Api\Ynov\parameter\ProduitPrestation;
use App\Models\Api\Ynov\parameter\ActivityLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProduitService
{
    /**
     * Liste des produits avec filtres
     */
    public function getProduits(array $filters = [], int $perPage = 20)
    {
        $query = Produit::with(['typeProduit', 'formules' => function ($q) {
            $q->where('est_actif', true);
        }]);

        if (isset($filters['statut'])) {
            $query->where('statut', $filters['statut']);
        }

        if (isset($filters['code_branche'])) {
            $query->where('code_branche', $filters['code_branche']);
        }

        if (isset($filters['type_produit_uuid'])) {
            $query->where('type_produit_uuid', $filters['type_produit_uuid']);
        }

        if (isset($filters['search'])) {
            $query->search($filters['search']);
        }

        return $query->orderBy('libelle')->paginate($perPage);
    }

    /**
     * Créer un produit
     */
    public function createProduit(array $data, string $creatorUuid): Produit
    {
        return DB::transaction(function () use ($data, $creatorUuid) {
            // Vérifier si le code existe déjà
            if (isset($data['code']) && Produit::where('code', $data['code'])->exists()) {
                throw ValidationException::withMessages([
                    'code' => ['Ce code est déjà utilisé.']
                ]);
            }

            $produit = Produit::create([
                'uuid_produit' => (string) Str::uuid(),
                'code' => $data['code'] ?? Str::slug($data['libelle'], '_'),
                'libelle' => $data['libelle'],
                'date_creation' => $data['date_creation'] ?? now(),
                'code_branche' => $data['code_branche'] ?? null,
                'code_produit_nature' => $data['code_produit_nature'] ?? null,
                'description' => $data['description'] ?? null,
                'statut' => $data['statut'] ?? 'actif',
                'code_groupe_assure' => $data['code_groupe_assure'] ?? null,
                'code_groupe_profil' => $data['code_groupe_profil'] ?? null,
                'age_mini_adh' => $data['age_mini_adh'] ?? null,
                'age_maxi_adh' => $data['age_maxi_adh'] ?? null,
                'table_tarification' => $data['table_tarification'] ?? null,
                'table_reglementaire' => $data['table_reglementaire'] ?? null,
                'table_fiscale' => $data['table_fiscale'] ?? null,
                'table_comptable' => $data['table_comptable'] ?? null,
                'code_contractant' => $data['code_contractant'] ?? null,
                'num_seq' => $data['num_seq'] ?? null,
                'delai_carrence' => $data['delai_carrence'] ?? null,
                'capital_assure_pmok' => $data['capital_assure_pmok'] ?? null,
                'capital_assure_vers_excp_ok' => $data['capital_assure_vers_excp_ok'] ?? null,
                'code_branche_deux' => $data['code_branche_deux'] ?? null,
                'type_produit_uuid' => $data['type_produit_uuid'] ?? null,
                'capital' => $data['capital'] ?? null,
                'code_produit_court' => $data['code_produit_court'] ?? null,
                'duree_souscription_annee' => $data['duree_souscription_annee'] ?? null,
                'duree_souscription_mois' => $data['duree_souscription_mois'] ?? null,
                'vie_entiere' => $data['vie_entiere'] ?? false,
                'duree_cotisation_ans' => $data['duree_cotisation_ans'] ?? null,
                'duree_cotisation_mois' => $data['duree_cotisation_mois'] ?? null,
                'code_marque' => $data['code_marque'] ?? null,
                'created_by' => $creatorUuid,
            ]);

            ActivityLog::log([
                'user_uuid' => $creatorUuid,
                'action' => 'create',
                'action_type' => 'crud',
                'module' => 'produits',
                'description' => "Création du produit : {$produit->libelle}",
                'resource_type' => 'produit',
                'resource_id' => $produit->uuid_produit,
                'new_values' => $produit->toArray(),
                'level' => 'info',
            ]);

            return $produit;
        });
    }

    /**
     * Mettre à jour un produit
     */
    public function updateProduit(Produit $produit, array $data, string $updaterUuid): Produit
    {
        return DB::transaction(function () use ($produit, $data, $updaterUuid) {
            // Vérifier si le code existe déjà (pour un autre produit)
            if (isset($data['code']) && Produit::where('code', $data['code'])->where('uuid_produit', '!=', $produit->uuid_produit)->exists()) {
                throw ValidationException::withMessages([
                    'code' => ['Ce code est déjà utilisé.']
                ]);
            }

            $oldValues = $produit->toArray();

            $produit->update([
                'code' => $data['code'] ?? $produit->code,
                'libelle' => $data['libelle'] ?? $produit->libelle,
                'date_creation' => $data['date_creation'] ?? $produit->date_creation,
                'code_branche' => $data['code_branche'] ?? $produit->code_branche,
                'code_produit_nature' => $data['code_produit_nature'] ?? $produit->code_produit_nature,
                'description' => $data['description'] ?? $produit->description,
                'statut' => $data['statut'] ?? $produit->statut,
                'code_groupe_assure' => $data['code_groupe_assure'] ?? $produit->code_groupe_assure,
                'code_groupe_profil' => $data['code_groupe_profil'] ?? $produit->code_groupe_profil,
                'age_mini_adh' => $data['age_mini_adh'] ?? $produit->age_mini_adh,
                'age_maxi_adh' => $data['age_maxi_adh'] ?? $produit->age_maxi_adh,
                'table_tarification' => $data['table_tarification'] ?? $produit->table_tarification,
                'table_reglementaire' => $data['table_reglementaire'] ?? $produit->table_reglementaire,
                'table_fiscale' => $data['table_fiscale'] ?? $produit->table_fiscale,
                'table_comptable' => $data['table_comptable'] ?? $produit->table_comptable,
                'code_contractant' => $data['code_contractant'] ?? $produit->code_contractant,
                'num_seq' => $data['num_seq'] ?? $produit->num_seq,
                'delai_carrence' => $data['delai_carrence'] ?? $produit->delai_carrence,
                'capital_assure_pmok' => $data['capital_assure_pmok'] ?? $produit->capital_assure_pmok,
                'capital_assure_vers_excp_ok' => $data['capital_assure_vers_excp_ok'] ?? $produit->capital_assure_vers_excp_ok,
                'code_branche_deux' => $data['code_branche_deux'] ?? $produit->code_branche_deux,
                'type_produit_uuid' => $data['type_produit_uuid'] ?? $produit->type_produit_uuid,
                'capital' => $data['capital'] ?? $produit->capital,
                'code_produit_court' => $data['code_produit_court'] ?? $produit->code_produit_court,
                'duree_souscription_annee' => $data['duree_souscription_annee'] ?? $produit->duree_souscription_annee,
                'duree_souscription_mois' => $data['duree_souscription_mois'] ?? $produit->duree_souscription_mois,
                'vie_entiere' => $data['vie_entiere'] ?? $produit->vie_entiere,
                'duree_cotisation_ans' => $data['duree_cotisation_ans'] ?? $produit->duree_cotisation_ans,
                'duree_cotisation_mois' => $data['duree_cotisation_mois'] ?? $produit->duree_cotisation_mois,
                'code_marque' => $data['code_marque'] ?? $produit->code_marque,
                'updated_by' => $updaterUuid,
            ]);

            ActivityLog::log([
                'user_uuid' => $updaterUuid,
                'action' => 'update',
                'action_type' => 'crud',
                'module' => 'produits',
                'description' => "Mise à jour du produit : {$produit->libelle}",
                'resource_type' => 'produit',
                'resource_id' => $produit->uuid_produit,
                'old_values' => $oldValues,
                'new_values' => $produit->toArray(),
                'level' => 'info',
            ]);

            return $produit->fresh();
        });
    }

    /**
     * Supprimer un produit (soft delete)
     */
    public function deleteProduit(Produit $produit, string $deleterUuid): void
    {
        // Vérifier si le produit a des formules associées
        if ($produit->formules()->count() > 0) {
            throw new \RuntimeException('Ce produit a des formules associées et ne peut pas être supprimé.');
        }

        // Vérifier si le produit a des prestations associées
        if ($produit->prestations()->count() > 0) {
            throw new \RuntimeException('Ce produit a des prestations associées et ne peut pas être supprimé.');
        }

        $produit->update([
            'statut' => 'inactif',
            'deleted_by' => $deleterUuid,
        ]);

        $produit->delete();

        ActivityLog::log([
            'user_uuid' => $deleterUuid,
            'action' => 'delete',
            'action_type' => 'crud',
            'module' => 'produits',
            'description' => "Suppression du produit : {$produit->libelle}",
            'resource_type' => 'produit',
            'resource_id' => $produit->uuid_produit,
            'level' => 'warning',
        ]);
    }

    /**
     * Récupérer un produit avec ses détails
     */
    public function getProduitWithDetails(string $uuid_produit): Produit
    {
        return Produit::where('uuid_produit', $uuid_produit)
            ->with([
                'typeProduit',
                'formules' => function ($q) {
                    $q->orderBy('libelle');
                },
                'typePrestations' => function ($q) {
                    $q->wherePivot('status', 'actif')
                        ->with('category')
                        ->orderBy('libelle');
                }
            ])
            ->firstOrFail();
    }

    /**
     * Statistiques des produits
     */
    public function getStats(): array
    {
        return [
            'total' => Produit::count(),
            'active' => Produit::active()->count(),
            'inactive' => Produit::where('statut', 'inactif')->count(),
            'formules_total' => ProduitFormule::count(),
            'formules_active' => ProduitFormule::where('est_actif', true)->count(),
            'prestations_total' => ProduitPrestation::count(),
            'prestations_active' => ProduitPrestation::where('status', 'actif')->count(),
        ];
    }
}