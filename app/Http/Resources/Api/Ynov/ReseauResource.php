<?php
// app/Http/Resources/Api/Ynov/ReseauResource.php
namespace App\Http\Resources\Api\Ynov;

use App\Http\Resources\Api\Ynov\AgenceResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReseauResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid_reseau' => $this->uuid_reseau,
            'code' => $this->code,
            'libelle' => $this->libelle,
            'description' => $this->description,
            'partner' => $this->whenLoaded('partner', function () {
                return [
                    'uuid_partner' => $this->partner->uuid_partner,
                    'designation' => $this->partner->designation,
                    'code' => $this->partner->code,
                ];
            }),
            'email' => $this->email,
            'telephone' => $this->telephone,
            'status' => $this->status,
            'is_active' => $this->isActive(),
            'agences_count' => $this->agences_count ?? $this->agences()->count(),
            'users_count' => $this->users_count ?? $this->users()->count(),
            'agences' => AgenceResource::collection($this->whenLoaded('agences')),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}