<?php
// app/Http/Resources/Api/Ynov/PartnerResource.php
namespace App\Http\Resources\Api\Ynov;

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
            'code_branche' => $this->code_branche,
            'description' => $this->description,
            'logo_document' => $this->whenLoaded('logoDocuments', function () {
                $logos = $this->logoDocuments;
                
                if ($logos->isEmpty()) {
                    return [];
                }
                
                return $logos->map(function ($logo) {
                    return [
                        'uuid_document' => $logo->uuid_document,
                        'reference_uuid' => $logo->reference_uuid,
                        'nom_fichier' => $logo->nom_fichier,
                        'libelle' => $logo->libelle,
                        'source' => $logo->source,
                        'chemin' => $logo->chemin,
                        'type_document' => $logo->type_document,
                        'taille_fichier' => $logo->taille_fichier,
                        'mime_type' => $logo->mime_type,
                        'statut' => $logo->statut,
                        'url' => url('preview/doc/' . $logo->nom_fichier),
                        'created_at' => $logo->created_at?->format('Y-m-d H:i:s'),
                        'updated_at' => $logo->updated_at?->format('Y-m-d H:i:s'),
                    ];
                })->values()->all();
            }),
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