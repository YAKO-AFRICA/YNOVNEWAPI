<?php
// app/Http/Requests/Api/Ynov/UpdateProfileRequest.php
namespace App\Http\Requests\Api\Ynov;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $user = $this->user();
        
        return [
            // Champs de l'utilisateur
            'login' => ['sometimes', 'nullable', 'string', 'max:100', "unique:users,login,{$user->uuid_user},uuid_user"],
            'email' => ['nullable', 'email', 'max:100', "unique:users,email,{$user->uuid_user},uuid_user"],
            
            // Champs des détails
            'nom' => ['sometimes', 'string', 'max:55'],
            'prenoms' => ['sometimes', 'string', 'max:255'],
            'fonction' => ['nullable', 'string', 'max:55'],
            'service' => ['nullable', 'string', 'max:55'],
            'departement' => ['nullable', 'string', 'max:55'],
            'mobile_1' => ['nullable', 'string', 'max:25'],
            'mobile_2' => ['nullable', 'string', 'max:25'],
            'telephone_fixe' => ['nullable', 'string', 'max:25'],
            'email_pro' => ['nullable', 'email', 'max:100', "unique:user_details,email_pro,{$user->details?->uuid_user_details},uuid_user_details"],
            'ville' => ['nullable', 'string', 'max:100'],
            'pays' => ['nullable', 'string', 'max:100'],
            'date_naissance' => ['nullable', 'date', 'before:today'],
            'lieu_naissance' => ['nullable', 'string', 'max:55'],
            'lieu_residence' => ['nullable', 'string', 'max:55'],
            'nationalite' => ['nullable', 'string', 'max:55'],
            'genre' => ['nullable', 'in:M,F'],
            'civilite' => ['nullable', 'string', 'max:20', 'in:M.,Mme,Mlle,Dr,Pr'],
            'adresse_complete' => ['nullable', 'string', 'max:255'],
            'code_postal' => ['nullable', 'string', 'max:20'],
            'date_embauche' => ['nullable', 'date'],
            'statut_employe' => ['nullable', 'string', 'max:50'],
            'type_contrat' => ['nullable', 'string', 'max:50'],
            'code_agent' => ['nullable', 'string', 'max:35'],
            'matricule' => ['nullable', 'string', 'max:35'],
            'numero_client' => ['nullable', 'string', 'max:35'],
            
            // Gestion de la photo
            'photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
            'photo_url' => ['nullable', 'string', 'max:255', 'url'],
            'remove_photo' => ['nullable', 'boolean'],
            
            // Préférences
            'preferences' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'photo.image' => 'Le fichier doit être une image.',
            'photo.mimes' => 'Le fichier doit être au format jpeg, png, jpg, gif ou webp.',
            'photo.max' => 'La photo ne doit pas dépasser 2 Mo.',
            'date_naissance.before' => 'La date de naissance doit être dans le passé.',
            'genre.in' => 'Le genre doit être M ou F.',
            'civilite.in' => 'La civilité doit être M., Mme, Mlle, Dr ou Pr.',
            'email_pro.unique' => 'Cet email professionnel est déjà utilisé.',
            'email.unique' => 'Cet email est déjà utilisé.',
            'login.unique' => 'Cet identifiant est déjà utilisé.',
        ];
    }
}