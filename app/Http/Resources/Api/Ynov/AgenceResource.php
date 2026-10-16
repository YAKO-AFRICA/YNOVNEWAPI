<?php
// app/Http/Resources/Api/Ynov/AgenceResource.php
namespace App\Http\Resources\Api\Ynov;

use App\Models\Api\Ynov\parameter\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AgenceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid_agence' => $this->uuid_agence,
            'code' => $this->code,
            'libelle' => $this->libelle,
            'description' => $this->description,
            'reseau' => $this->whenLoaded('reseau', function () {
                return [
                    'uuid_reseau' => $this->reseau->uuid_reseau,
                    'libelle' => $this->reseau->libelle,
                    'code' => $this->reseau->code,
                ];
            }),
            'email' => $this->email,
            'telephone' => $this->telephone,
            'telephone_2' => $this->telephone_2,
            'adresse' => $this->adresse,
            'adresse_complete' => $this->full_address,
            'ville' => $this->ville,
            'quartier' => $this->quartier,
            'code_postal' => $this->code_postal,
            'pays' => $this->pays,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'photo' => $this->photo,
            'photos' => $this->photos,
            'responsable' => $this->responsable,
            'site_web' => $this->site_web,
            'status' => $this->status,
            'status_label' => $this->getAgenceStatusLabel(),
            'is_open' => $this->isOpen(),
            'recoit_sur_rdv' => $this->recoitSurRdv(),
            'users_count' => $this->whenCounted('users', $this->users_count ?? $this->users()->count()),
            'horaires' => $this->whenLoaded('horaires', function () {
                return $this->horaires->map(function ($horaire) {
                    return [
                        'uuid_horaire' => $horaire->uuid_horaire,
                        'jour' => $horaire->jour,
                        'heure_ouverture' => $horaire->heure_ouverture?->format('H:i'),
                        'heure_fermeture' => $horaire->heure_fermeture?->format('H:i'),
                        'heure_ouverture_midi' => $horaire->heure_ouverture_midi?->format('H:i'),
                        'heure_fermeture_midi' => $horaire->heure_fermeture_midi?->format('H:i'),
                        'rendez_vous_actif' => $horaire->rendez_vous_actif,
                        'capacite_rendez_vous' => $horaire->capacite_rendez_vous,
                        'ferme' => $horaire->ferme,
                        'commentaire' => $horaire->commentaire,
                        'plage_horaire' => $horaire->plage_horaire,
                    ];
                });
            }),
            'horaires_formatted' => $this->getHorairesFormatted(),
            'created_by' => $this->formatUser($this->createdBy),
            'updated_by' => $this->formatUser($this->updatedBy),
            'deleted_by' => $this->formatUser($this->deletedBy),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }

    private function formatUser(?User $user): ?array
    {
        if (!$user) {
            return null;
        }

        $nom = $user->details?->nom;
        $prenoms = $user->details?->prenoms;

        return [
            'uuid_user' => $user->uuid_user,
            'login' => $user->login,
            'email' => $user->email,
            'nom' => $nom,
            'prenoms' => $prenoms,
            'full_name' => trim(($nom ?? '') . ' ' . ($prenoms ?? '')),
        ];
    }
}