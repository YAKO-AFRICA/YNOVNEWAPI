<?php

namespace App\Http\Resources\Api\Ynov;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DetailBordereauRdvResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid_detail_bordereau_rdv' => $this->uuid_detail_bordereau_rdv,
            'bordereau_rdv_uuid' => $this->bordereau_rdv_uuid,
            'rdv_uuid' => $this->rdv_uuid,
            'status' => $this->status,
            'status_label' => match ($this->status) {
                'en_attente' => 'En attente',
                'soumis' => 'Soumis',
                'traite' => 'Traité',
                default => $this->status,
            },
            'date_effet' => $this->date_effet?->format('Y-m-d'),
            'date_echeance' => $this->date_echeance?->format('Y-m-d'),
            'duree_contrat' => $this->duree_contrat,
            'type_operation' => $this->type_operation,
            'produit' => $this->produit,
            'cumul_rachats_partiels' => $this->cumul_rachats_partiels,
            'cumul_avances' => $this->cumul_avances,
            'provision_nette' => $this->provision_nette,
            'valeur_rachat' => $this->valeur_rachat,
            'valeur_max_rachat' => $this->valeur_max_rachat,
            'valeur_max_avance' => $this->valeur_max_avance,
            'montant_transformation' => $this->montant_transformation,
            'garantie_surete' => $this->garantie_surete,
            'conservation_capital' => $this->conservation_capital,
            'observation' => $this->observation,
            'soumis_a' => $this->soumis_a,
            'soumisAgestionnairePrestation' => $this->whenLoaded('soumisAgestionnairePrestation', function () {
                return [
                    'uuid_user' => $this->AgestionnairePrestation->uuid_user,
                    'email' => $this->AgestionnairePrestation->email,
                    'nom' => $this->AgestionnairePrestation->details?->nom,
                    'prenoms' => $this->AgestionnairePrestation->details?->prenoms,
                    'full_name' => trim(($this->AgestionnairePrestation->details?->nom ?? '') . ' ' . ($this->AgestionnairePrestation->details?->prenoms ?? '')),
                ];
            }),
            'bordereau' => $this->whenLoaded('bordereauRdv', fn () => new BordereauRdvResource($this->bordereauRdv)),
            'rdv' => $this->whenLoaded('rdv', fn () => new RdvResource($this->rdv)),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
