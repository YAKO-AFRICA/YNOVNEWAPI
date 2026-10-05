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
            'code_contractant' => $this->code_contractant,
            'description' => $this->description,
            'logo' => $this->logo,
            'is_active' => $this->is_active,
            'status' => $this->status,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'deleted_by' => $this->deleted_by,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
            'deleted_at' => $this->deleted_at?->toDateTimeString(),
        ];
    }
}