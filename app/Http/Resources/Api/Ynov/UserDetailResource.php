<?php
namespace App\Http\Resources\Api\Ynov;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid_user_details' => $this->uuid_user_details,
            'uuid_user' => $this->uuid_user,
            'code_agent' => $this->code_agent,
            'matricule' => $this->matricule,
            'nom' => $this->nom,
            'prenoms' => $this->prenoms,
            'full_name' => $this->full_name,
            'fonction' => $this->fonction,
            'service' => $this->service,
            'departement' => $this->departement,
            'mobile_1' => $this->mobile_1,
            'mobile_2' => $this->mobile_2,
            'email_pro' => $this->email_pro,
            'photo' => $this->photo,
            'photo_path' => $this->photo_path,
            'photo_url' => $this->photo_url,
            'date_naissance' => $this->date_naissance,
            'lieu_naissance' => $this->lieu_naissance,
            'ville' => $this->ville,
            'pays' => $this->pays,
            'genre' => $this->genre,
            'civilite' => $this->civilite,        
        ];
    }
}