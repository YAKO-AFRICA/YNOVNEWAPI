<?php

namespace App\Http\Resources\Api\Ynov;

use App\Models\Api\Ynov\Rdv;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RdvResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid_rdvs' => $this->uuid_rdvs,
            'code' => $this->code,
            'status' => $this->status,
            'status_label' => $this->status ? (Rdv::STATUS[$this->status] ?? $this->status) : null,
            'motif_rdv' => $this->motif_rdv,
            'id_contrat' => $this->id_contrat,
            'motif_rdv_label' => $this->whenLoaded('motif', fn () => optional($this->motif)->libelle),
            'demandeur' => $this->demandeur,
            'date_rdv_souhaiter' => $this->date_rdv_souhaiter?->format('Y-m-d H:i:s'),
            'date_rdv_effective' => $this->date_rdv_effective?->format('Y-m-d H:i:s'),
            'date_transmission' => $this->date_transmission?->format('Y-m-d H:i:s'),
            'transmis_par' => $this->transmis_par,
            'date_traitement' => $this->date_traitement?->format('Y-m-d H:i:s'),
            'is_permitted' => (bool) $this->is_permitted,
            'is_present' => (bool) $this->is_present,
            'observation' => $this->observation,
            'motif_traitement' => $this->getMotifsTraitement(),
            'motifs_par_type' => [
                'traitement' => $this->getMotifsByType('traitement'),
                'report' => $this->getMotifsByType('report'),
                'rejet' => $this->getMotifsByType('rejet'),
                'annulation' => $this->getMotifsByType('annulation'),
                'expiration' => $this->getMotifsByType('expiration'),
                'reassignation' => $this->getMotifsByType('reassignation'),
            ],
            'motifs_traitement_details' => $this->when(fn () => !empty($this->motif_traitement), fn () => $this->getMotifsTraitementDetails()),
            'has_motifs' => [
                'traitement' => $this->hasMotifsType('traitement'),
                'report' => $this->hasMotifsType('report'),
                'rejet' => $this->hasMotifsType('rejet'),
                'annulation' => $this->hasMotifsType('annulation'),
                'expiration' => $this->hasMotifsType('expiration'),
                'reassignation' => $this->hasMotifsType('reassignation'),
            ],

            'client' => $this->whenLoaded('client', function () {
                return [
                    'uuid_user' => $this->client->uuid_user,
                    'numero_client' => $this->client->details?->numero_client,
                    'login' => $this->client->login,
                    'email' => $this->client->email,
                    'nom' => $this->client->details?->nom,
                    'prenoms' => $this->client->details?->prenoms,
                    'date_naissance' => $this->client->details?->date_naissance?->format('Y-m-d'),
                    'lieu_naissance' => $this->client->details?->lieu_naissance,
                    'full_name' => trim(($this->client->details?->nom ?? '') . ' ' . ($this->client->details?->prenoms ?? '')),
                    'phone' => $this->client->details?->mobile_1 ?? $this->client->details?->mobile_2 ?? null,
                    'lieu_residence' => $this->client->details?->lieu_residence,
                    'adresse' => $this->client->details?->adresse_complete,
                    'genre' => $this->client->details?->genre,
                    'civilite' => $this->client->details?->civilite,
                    'nationalite' => $this->client->details?->nationalite,
                    'status' => $this->client->status,
                    'user_type' => $this->client->user_type,
                ];
            }),

            'gestionnaire' => $this->whenLoaded('gestionnaire', function () {
                return [
                    'uuid_user' => $this->gestionnaire->uuid_user,
                    'login' => $this->gestionnaire->login,
                    'email' => $this->gestionnaire->email,
                    'nom' => $this->gestionnaire->details?->nom,
                    'prenoms' => $this->gestionnaire->details?->prenoms,
                    'full_name' => trim(($this->gestionnaire->details?->nom ?? '') . ' ' . ($this->gestionnaire->details?->prenoms ?? '')),
                ];
            }),
            'transmisParUser' => $this->when($this->transmis_par !== null, function () {
                // Si transmis_par est 'system', retourner un objet système
                if ($this->transmis_par === 'system') {
                    return [
                        'uuid_user' => 'system',
                        'login' => 'system',
                        'email' => 'system@ynov.ci',
                        'nom' => 'Système',
                        'prenoms' => 'Automatique',
                        'full_name' => 'Système Automatique',
                    ];
                }
                
                // Sinon retourner les infos de l'utilisateur si la relation est chargée
                return $this->whenLoaded('transmisParUser', function () {
                    return [
                        'uuid_user' => $this->transmisParUser->uuid_user,
                        'login' => $this->transmisParUser->login,
                        'email' => $this->transmisParUser->email,
                        'nom' => $this->transmisParUser->details?->nom,
                        'prenoms' => $this->transmisParUser->details?->prenoms,
                        'full_name' => trim(($this->transmisParUser->details?->nom ?? '') . ' ' . ($this->transmisParUser->details?->prenoms ?? '')),
                    ];
                });
            }),

            'agence_souhaitee' => $this->whenLoaded('agenceSouhaitee', function () {
                return [
                    'uuid_agence' => $this->agenceSouhaitee->uuid_agence,
                    'libelle' => $this->agenceSouhaitee->libelle,
                    'code' => $this->agenceSouhaitee->code,
                    'ville' => $this->agenceSouhaitee->ville,
                    'adresse' => $this->agenceSouhaitee->adresse,
                ];
            }),

            'agence_effective' => $this->whenLoaded('agenceEffective', function () {
                return [
                    'uuid_agence' => $this->agenceEffective->uuid_agence,
                    'libelle' => $this->agenceEffective->libelle,
                    'code' => $this->agenceEffective->code,
                    'ville' => $this->agenceEffective->ville,
                    'adresse' => $this->agenceEffective->adresse,
                ];
            }),

            'detail_bordereau' => $this->whenLoaded('detailBordereau', function () {
                if (!$this->detailBordereau) {
                    return null;
                }

                return [
                    'uuid_detail_bordereau_rdv' => $this->detailBordereau->uuid_detail_bordereau_rdv,
                    'bordereau_rdv_uuid' => $this->detailBordereau->bordereau_rdv_uuid,
                    'date_effet' => $this->detailBordereau->date_effet?->format('Y-m-d'),
                    'date_echeance' => $this->detailBordereau->date_echeance?->format('Y-m-d'),
                    'duree_contrat' => $this->detailBordereau->duree_contrat,
                    'type_operation' => $this->detailBordereau->type_operation,
                    'produit' => $this->detailBordereau->produit,
                    'status' => $this->detailBordereau->status,
                    'observation' => $this->detailBordereau->observation,
                    'cumul_rachats_partiels' => $this->detailBordereau->cumul_rachats_partiels,
                    'cumul_avances' => $this->detailBordereau->cumul_avances,
                    'provision_nette' => $this->detailBordereau->provision_nette,
                    'valeur_rachat' => $this->detailBordereau->valeur_rachat,
                    'valeur_max_rachat' => $this->detailBordereau->valeur_max_rachat,
                    'valeur_max_avance' => $this->detailBordereau->valeur_max_avance,
                    'montant_transformation' => $this->detailBordereau->montant_transformation,
                    'garantie_surete' => $this->detailBordereau->garantie_surete,
                    'conservation_capital' => $this->detailBordereau->conservation_capital,

                    'bordereau' => $this->whenLoaded('detailBordereau.bordereauRdv', function () {
                        return [
                            'uuid_bordereau_rdv' => $this->detailBordereau->bordereauRdv->uuid_bordereau_rdv,
                            'reference' => $this->detailBordereau->bordereauRdv->reference,
                            'periode_1' => $this->detailBordereau->bordereauRdv->periode_1?->format('Y-m-d'),
                            'periode_2' => $this->detailBordereau->bordereauRdv->periode_2?->format('Y-m-d'),
                            'status' => $this->detailBordereau->bordereauRdv->status,
                            'observation' => $this->detailBordereau->bordereauRdv->observation,
                        ];
                    }),
                ];
            }),

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
