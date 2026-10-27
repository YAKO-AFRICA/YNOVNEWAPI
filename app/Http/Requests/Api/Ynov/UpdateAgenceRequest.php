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


    public function messages(): array
    {
        return [
            'libelle.required' => 'Le libellé de l\'agence est requis.',
            'libelle.max' => 'Le libellé de l\'agence doit avoir au maximum 255 caractères.',
            'description.max' => 'La description de l\'agence doit avoir au maximum 255 caractères.',
            'reseau_uuid.exists' => 'Le reseau_uuid fourni n\'existe pas.',
            'email.email' => 'L\'adresse email doit avoir un format valide.',
            'email.max' => 'L\'adresse email doit avoir au maximum 100 caractères.',
            'telephone.string' => 'Le num&eacute;ro de t&eacute;l&eacute;phone doit avoir au maximum 25 caractères.',
            'telephone_2.string' => 'Le num&eacute;ro de t&eacute;l&eacute;phone 2 doit avoir au maximum 25 caractères.',
            'adresse.max' => 'L\'adresse doit avoir au maximum 255 caractères.',
            'ville.max' => 'La ville doit avoir au maximum 100 caractères.',
            'quartier.max' => 'Le quartier doit avoir au maximum 100 caractères.',
            'code_postal.max' => 'Le code postal doit avoir au maximum 20 caractères.',
            'pays.max' => 'Le pays doit avoir au maximum 100 caractères.',
            'latitude.numeric' => 'La latitude doit avoir une valeur num&eacute;rique.',
            'latitude.between' => 'La latitude doit avoir une valeur comprise entre -90 et 90 degrés.',
            'longitude.numeric' => 'La longitude doit avoir une valeur num&eacute;rique.',
            'longitude.between' => 'La longitude doit avoir une valeur comprise entre -180 et 180 degrés.',
            'photo.max' => 'L\'URL de la photo doit avoir au maximum 255 caractères.',
            'photos.*.max' => 'L\'URL de la photo doit avoir au maximum 255 caractères.',
            'responsable.max' => 'Le responsable doit avoir au maximum 255 caractères.',
            'site_web.url' => 'Le site web doit avoir un format valide.',
            'site_web.max' => 'Le site web doit avoir au maximum 255 caractères.',
            'horaires.*.jour.required_with' => 'Le jour est requis pour chaque horaire.',
            'horaires.*.jour.in' => 'Le jour doit être un jour valide de la semaine.',
            'horaires.*.heure_ouverture.date_format' => 'L\'heure d\'ouverture doit être au format HH:MM.',
            'horaires.*.heure_fermeture.date_format' => 'L\'heure de fermeture doit être au format HH:MM.',
            'horaires.*.heure_fermeture.after' => 'L\'heure de fermeture doit être après l\'heure d\'ouverture.',
            'horaires.*.heure_ouverture_midi.date_format' => 'L\'heure d\'ouverture du midi doit être au format HH:MM.',
            'horaires.*.heure_fermeture_midi.date_format' => 'L\'heure de fermeture du midi doit être au format HH:MM.',
            'horaires.*.heure_fermeture_midi.after' => 'L\'heure de fermeture du midi doit être après l\'heure d\'ouverture du midi.',
            'horaires.*.heure_fermeture_midi.before' => 'L\'heure de fermeture du midi doit être avant l\'heure de fermeture.',
            'horaires.*.capacite_rendez_vous.integer' => 'La capacité de rendez-vous doit être un nombre entier.',
            'horaires.*.capacite_rendez_vous.min' => 'La capacité de rendez-vous ne peut pas être négative.',
        ];
    }
}