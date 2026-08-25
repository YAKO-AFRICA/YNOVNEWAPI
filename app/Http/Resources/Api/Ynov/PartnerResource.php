<?php
// app/Http/Resources/Api/Ynov/PartnerResource.php
namespace App\Http\Resources\Api\Ynov;

use App\Http\Resources\Api\Ynov\ReseauResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PartnerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid_partner' => $this->uuid_partner,
            'code' => $this->code,
            'designation' => $this->designation,
            'sigle' => $this->sigle,
            'description' => $this->description,
            'logo' => $this->logo,
            'code_branche' => $this->code_branche,
            'email' => $this->email,
            'email_2' => $this->email_2,
            'telephone' => $this->telephone,
            'telephone_2' => $this->telephone_2,
            'adresse' => $this->adresse,
            'adresse_complete' => $this->full_address,
            'ville' => $this->ville,
            'pays' => $this->pays,
            'site_web' => $this->site_web,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'type' => $this->type,
            'secteur_activite' => $this->secteur_activite,
            'categorie' => $this->categorie,
            'is_active' => $this->is_active,
            'status' => $this->status,
            'date_agrement' => $this->date_agrement?->toDateString(),
            'date_expiration' => $this->date_expiration?->toDateString(),
            'is_expired' => $this->isExpired(),
            'reseaux_count' => $this->whenCounted('reseaux', $this->reseaux()->count()),
            'users_count' => $this->whenCounted('users', $this->users()->count()),
            'reseaux' => ReseauResource::collection($this->whenLoaded('reseaux')),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}