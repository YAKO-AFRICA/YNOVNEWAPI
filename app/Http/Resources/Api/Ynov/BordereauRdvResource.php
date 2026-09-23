<?php

namespace App\Http\Resources\Api\Ynov;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BordereauRdvResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid_bordereau_rdv' => $this->uuid_bordereau_rdv,
            'reference' => $this->reference,
            'periode_1' => $this->periode_1?->format('Y-m-d'),
            'periode_2' => $this->periode_2?->format('Y-m-d'),
            'status' => $this->status,
            'status_label' => match ($this->status) {
                'en_attente' => 'En attente',
                'transfere' => 'Transféré',
                'cloture' => 'Clôturé',
                default => $this->status,
            },
            'observation' => $this->observation,
            'details_count' => $this->details_count ?? $this->details()->count(),
            'details' => $this->whenLoaded('details', fn () => $this->details->map(
                fn ($detail) => [
                    'uuid_detail_bordereau_rdv' => $detail->uuid_detail_bordereau_rdv,
                    'rdv_uuid' => $detail->rdv_uuid,
                    'status' => $detail->status,
                    'status_label' => match ($detail->status) {
                        'en_attente' => 'En attente',
                        'soumis' => 'Soumis',
                        'traite' => 'Traité',
                        default => $detail->status,
                    },
                ]
            )->values()),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
