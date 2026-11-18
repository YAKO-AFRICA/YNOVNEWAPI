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
            'numero_client' => $this->numero_client,
            'matricule' => $this->matricule,
            'nom' => $this->nom,
            'prenoms' => $this->prenoms,
            'full_name' => $this->full_name,
            'fonction' => ($this->userProfession) ? $this->userProfession->MonLibelle : $this->fonction,
            'service' => $this->service,
            'departement' => $this->departement,
            'mobile_1' => $this->mobile_1,
            'mobile_2' => $this->mobile_2,
            'email_pro' => $this->email_pro,
            'photo_url' => $this->photoUrl(),
            'date_naissance' => $this->date_naissance,
            'lieu_naissance' => ($this->userLieunaissance) ? $this->userLieunaissance->MonLibelle : $this->lieu_naissance,
            'lieu_residence' => ($this->userLieuresidence) ? $this->userLieuresidence->MonLibelle : $this->lieu_residence,
            'nationalite' => $this->nationalite,
            'date_embauche' => $this->date_embauche,
            'statut_employe' => $this->statut_employe,
            'type_contrat' => $this->type_contrat,
            'code_postal' => $this->code_postal,
            'adresse_complete' => $this->adresse_complete,
            'quartier' => $this->quartier,
            'ville' => ($this->userLieuresidence) ? $this->userLieuresidence->MonLibelle : $this->ville,
            'pays' => $this->pays,
            'genre' => $this->genre,
            'civilite' => $this->civilite,
        ];
    }

    /**
     * Construit l'URL publique de la photo de profil.
     *
     * - photo_path = 'PROFILE_PICTURE/2026/11/x.jpg' → preview/doc/x.jpg
     * - photo_path = 'profiles/...' (legacy local) → route storage.documents
     * - photo_path/photo = URL externe → retournée telle quelle
     */
    protected function photoUrl(): ?string
    {
        $path = $this->photo_path ?: $this->photo;

        if (!$path) {
            return null;
        }

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        $cleanPath = ltrim(str_replace('\\', '/', $path), '/');

        // Anciennes photos stockées en local (UPLOADS_PATH)
        if (str_starts_with($cleanPath, 'profiles/')) {
            try {
                return route('storage.documents', ['file' => $cleanPath]);
            } catch (\Exception $e) {
                return url('/storage/documents/' . $cleanPath);
            }
        }

        // Documents stockés via DocumentService (WebDAV)
        return url('preview/doc/' . basename($cleanPath));
    }
}