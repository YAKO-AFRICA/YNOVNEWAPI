<?php
namespace App\Http\Resources\Api\Ynov;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserDetailResource extends JsonResource
{
    // public function toArray(Request $request): array
    // {
    //     return [
    //         'uuid_user_details' => $this->uuid_user_details,
    //         'uuid_user' => $this->uuid_user,
    //         'code_agent' => $this->code_agent,
    //         'numero_client' => $this->numero_client,
    //         'matricule' => $this->matricule,
    //         'nom' => $this->nom,
    //         'prenoms' => $this->prenoms,
    //         'full_name' => $this->full_name,
    //         'fonction' => $this->fonction,
    //         'service' => $this->service,
    //         'departement' => $this->departement,
    //         'mobile_1' => $this->mobile_1,
    //         'mobile_2' => $this->mobile_2,
    //         'email_pro' => $this->email_pro,
    //         'photo' => $this->photo,
    //         'photo_path' => $this->photo_path,
    //         // 'photo_url' => $this->photo_url,
    //         'date_naissance' => $this->date_naissance,
    //         'lieu_naissance' => $this->lieu_naissance,
    //         'lieu_residence' => $this->lieu_residence,
    //         'nationalite' => $this->nationalite,
    //         'date_embauche' => $this->date_embauche,
    //         'statut_employe' => $this->statut_employe,
    //         'type_contrat' => $this->type_contrat,
    //         'code_postal' => $this->code_postal,
    //         'adresse_complete' => $this->adresse_complete,
    //         'quartier' => $this->quartier,
    //         'ville' => $this->ville,
    //         'pays' => $this->pays,
    //         'genre' => $this->genre,
    //         'civilite' => $this->civilite,        
    //     ];
    // }

    public function toArray(Request $request): array
    {
        // Construire l'URL de la photo
        $photoUrl = null;
        if ($this->photo_path) {
            $photoUrl = url('/storage/documents/' . ltrim($this->photo_path, '/'));
        } elseif ($this->photo && filter_var($this->photo, FILTER_VALIDATE_URL)) {
            $photoUrl = $this->photo;
        }
        
        return [
            'uuid_user_details' => $this->uuid_user_details,
            'uuid_user' => $this->user_uuid ?? $this->uuid_user,
            'code_agent' => $this->code_agent,
            'numero_client' => $this->numero_client,
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
            'photo_url' => $photoUrl,
            'date_naissance' => $this->date_naissance,
            'lieu_naissance' => $this->lieu_naissance,
            'lieu_residence' => $this->lieu_residence,
            'nationalite' => $this->nationalite,
            'date_embauche' => $this->date_embauche,
            'statut_employe' => $this->statut_employe,
            'type_contrat' => $this->type_contrat,
            'code_postal' => $this->code_postal,
            'adresse_complete' => $this->adresse_complete,
            'quartier' => $this->quartier,
            'ville' => $this->ville,
            'pays' => $this->pays,
            'genre' => $this->genre,
            'civilite' => $this->civilite,
        ];
    }

}