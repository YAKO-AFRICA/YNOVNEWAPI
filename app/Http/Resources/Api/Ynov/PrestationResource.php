<?php

namespace App\Http\Resources\Api\Ynov;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PrestationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid_prestation' => $this->uuid_prestation,
            'client_uuid' => $this->client_uuid,
            'code' => $this->code,
            'id_contrat' => $this->id_contrat,
            'type_prestation_uuid' => $this->type_prestation_uuid,
            'rdv_uuid' => $this->rdv_uuid,
            'notes' => $this->notes,
            'montant' => $this->montant,
            'mode_paiement' => $this->mode_paiement,
            'operateur_mobile' => $this->operateur_mobile,
            'tel_paiement_1' => $this->tel_paiement_1,
            'tel_paiement_2' => $this->tel_paiement_2,
            'code_banque' => $this->code_banque,
            'code_guichet' => $this->code_guichet,
            'numero_compte' => $this->numero_compte,
            'cle_rib' => $this->cle_rib,
            'ville_declaration' => $this->ville_declaration,
            'partner_uuid' => $this->partner_uuid,
            'gestionnaire_uuid' => $this->gestionnaire_uuid,
            'date_transmission' => $this->date_transmission?->format('Y-m-d H:i:s'),
            'traiter_par' => $this->traiter_par,
            'date_traitement' => $this->date_traitement?->format('Y-m-d H:i:s'),
            'status' => $this->status,
            'status_label' => $this->getPrestationStatusLabel(),
            'is_migrated' => (bool) $this->is_migrated,
            'migration_date' => $this->migration_date?->format('Y-m-d H:i:s'),
            'motif_traitement' => $this->motif_traitement,
            'observation' => $this->observation,

            'client' => $this->whenLoaded('client', function () {
                $client = $this->client;

                return $client ? [
                    'uuid_user' => $client->uuid_user,
                    'login' => $client->login,
                    'email' => $client->email,
                    'status' => $client->status,
                    'user_type' => $client->user_type,
                    'numero_client' => $client->details?->numero_client,
                    'nom' => $client->details?->nom,
                    'prenoms' => $client->details?->prenoms,
                    'full_name' => trim(($client->details?->nom ?? '') . ' ' . ($client->details?->prenoms ?? '')),
                    'date_naissance' => $client->details?->date_naissance?->format('Y-m-d'),
                    'lieu_naissance' => $client->details?->lieu_naissance,
                    'mobile_1' => $client->details?->mobile_1,
                    'mobile_2' => $client->details?->mobile_2,
                    'genre' => $client->details?->genre,
                    'civilite' => $client->details?->civilite,
                    'nationalite' => $client->details?->nationalite,
                    'lieu_residence' => $client->details?->lieu_residence,
                    'adresse' => $client->details?->adresse_complete,
                ] : null;
            }),

            'type_prestation' => $this->whenLoaded('typePrestation', function () {
                $type = $this->typePrestation;

                if (!$type) {
                    return null;
                }

                return [
                    'uuid_type_prestation' => $type->uuid_type_prestation,
                    'code' => $type->code,
                    'libelle' => $type->libelle,
                    'description' => $type->description,
                    'impact' => $type->impact,
                    'impact_label' => method_exists($type, 'getImpactLabel') ? $type->getImpactLabel() : null,
                    'delai_traitement' => $type->delai_traitement,
                    'status' => $type->status,
                    'category' => $type->category ? [
                        'uuid_category_type_prestations' => $type->category->uuid_category_type_prestations,
                        'code' => $type->category->code,
                        'libelle' => $type->category->libelle,
                        'description' => $type->category->description,
                        'status' => $type->category->status,
                    ] : null,
                ];
            }),

            'gestionnaire' => $this->whenLoaded('gestionnaire', function () {
                $gestionnaire = $this->gestionnaire;

                return $gestionnaire ? [
                    'uuid_user' => $gestionnaire->uuid_user,
                    'login' => $gestionnaire->login,
                    'email' => $gestionnaire->email,
                    'nom' => $gestionnaire->details?->nom,
                    'prenoms' => $gestionnaire->details?->prenoms,
                    'full_name' => trim(($gestionnaire->details?->nom ?? '') . ' ' . ($gestionnaire->details?->prenoms ?? '')),
                    'mobile' => $gestionnaire->details?->mobile_1 ?? $gestionnaire->details?->mobile_2 ?? null,
                ] : null;
            }),

            'partner' => $this->whenLoaded('partner', function () {
                $partner = $this->partner;

                return $partner ? [
                    'uuid_partner' => $partner->uuid_partner,
                    'code' => $partner->code,
                    'designation' => $partner->designation,
                    'sigle' => $partner->sigle,
                    'ville' => $partner->ville,
                    'pays' => $partner->pays,
                    'status' => $partner->status,
                ] : null;
            }),

            'rdv' => $this->whenLoaded('rdv', function () {
                $rdv = $this->rdv;

                if (!$rdv) {
                    return null;
                }

                return [
                    'uuid_rdvs' => $rdv->uuid_rdvs,
                    'code' => $rdv->code,
                    'status' => $rdv->status,
                    'date_rdv_souhaiter' => $rdv->date_rdv_souhaiter?->format('Y-m-d H:i:s'),
                    'date_rdv_effective' => $rdv->date_rdv_effective?->format('Y-m-d H:i:s'),
                    'motif_rdv' => $rdv->motif_rdv,
                    'demandeur' => $rdv->demandeur,
                ];
            }),

            'traiter_par_user' => $this->when($this->traiter_par !== null, function () {
                $user = $this->traiterPar;

                return $user ? [
                    'uuid_user' => $user->uuid_user,
                    'login' => $user->login,
                    'email' => $user->email,
                    'nom' => $user->details?->nom,
                    'prenoms' => $user->details?->prenoms,
                    'full_name' => trim(($user->details?->nom ?? '') . ' ' . ($user->details?->prenoms ?? '')),
                ] : null;
            }),

            'documents' => $this->whenLoaded('documents', function () {
                return $this->documents->map(function ($document) {
                    return [
                        'uuid_document' => $document->uuid_document,
                        'reference_uuid' => $document->reference_uuid,
                        'nom_fichier' => $document->nom_fichier,
                        'libelle' => $document->libelle,
                        'source' => $document->source,
                        'chemin' => $document->chemin,
                        'type_document' => $document->type_document,
                        'taille_fichier' => $document->taille_fichier,
                        'mime_type' => $document->mime_type,
                        'statut' => $document->statut,
                        'url' => url('preview/doc/' . $document->nom_fichier),
                        'created_at' => $document->created_at?->format('Y-m-d H:i:s'),
                    ];
                })->values()->all();
            }),

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'deleted_at' => $this->deleted_at?->format('Y-m-d H:i:s'),
        ];
    }
}
