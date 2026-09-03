<?php
// app/Http/Requests/Api/Ynov/UpdateAgenceRequest.php
namespace App\Http\Requests\Api\Ynov;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAgenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('agences.modifier') ?? false;
    }

    public function rules(): array
    {
        $agence = $this->route('agence');
        $uuid = $agence ? $agence->uuid_agence : null;

        return [
            'code' => ['sometimes', 'string', 'max:55', Rule::unique('agences', 'code')->ignore($uuid, 'uuid_agence')],
            'libelle' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'reseau_uuid' => ['nullable', 'exists:reseaux,uuid_reseau'],
            'email' => ['nullable', 'email', 'max:100'],
            'telephone' => ['nullable', 'string', 'max:25'],
            'telephone_2' => ['nullable', 'string', 'max:25'],
            'adresse' => ['nullable', 'string', 'max:255'],
            'ville' => ['nullable', 'string', 'max:100'],
            'quartier' => ['nullable', 'string', 'max:100'],
            'code_postal' => ['nullable', 'string', 'max:20'],
            'pays' => ['nullable', 'string', 'max:100'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'photo' => ['nullable', 'string', 'max:255'],
            'photos' => ['nullable', 'array'],
            'photos.*' => ['nullable', 'string', 'max:255'],
            'responsable' => ['nullable', 'string', 'max:255'],
            'site_web' => ['nullable', 'url', 'max:255'],
            'status' => ['nullable', 'string', Rule::in(['actif', 'inactif'])],
            'horaires' => ['nullable', 'array'],
            'horaires.*.jour' => ['required_with:horaires', 'string', Rule::in(['lundi','mardi','mercredi','jeudi','vendredi','samedi','dimanche'])],
            'horaires.*.heure_ouverture' => ['nullable', 'date_format:H:i'],
            'horaires.*.heure_fermeture' => ['nullable', 'date_format:H:i', 'after:heure_ouverture'],
            'horaires.*.heure_ouverture_midi' => ['nullable', 'date_format:H:i', 'after:heure_ouverture'],
            'horaires.*.heure_fermeture_midi' => ['nullable', 'date_format:H:i', 'after:heure_ouverture_midi', 'before:heure_fermeture'],
            'horaires.*.ferme' => ['nullable', 'boolean'],
            'horaires.*.commentaire' => ['nullable', 'string', 'max:255'],
            'horaires.*.capacite_rendez_vous' => ['nullable', 'integer', 'min:0'],
            'horaires.*.rendez_vous_actif' => ['nullable', 'boolean'],
        ];
    }
}