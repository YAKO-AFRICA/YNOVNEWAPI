<?php

namespace App\Http\Requests\Api\Ynov;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('users.creer') ?? false;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'unique:users,email'],
            'login' => ['nullable', 'string', 'max:100', 'unique:users,login'],
            'password' => ['required', Password::min(12)->mixedCase()->numbers()->symbols()->uncompromised(),],
            'role_uuid' => ['required', 'exists:roles,uuid_role'],
            'user_type' => ['required', 'in:client,user_interne,user_partner,admin'],
            'partner_uuid' => ['nullable', 'exists:partners,uuid_partner'],
            'reseau_uuid' => ['nullable', 'exists:reseaux,uuid_reseau'],
            'agence_uuid' => ['nullable', 'exists:agences,uuid_agence'],
            'nom' => ['required', 'string', 'max:55'],
            'prenoms' => ['required', 'string', 'max:255'],
            'fonction' => ['nullable', 'string', 'max:55'],
            'mobile_1' => ['nullable', 'string', 'max:25'],
            'genre' => ['nullable', 'in:M,F'],
            'civilite' => ['nullable', 'string', 'max:20'],
            'date_naissance' => ['nullable', 'date'],
            'lieu_naissance' => ['nullable', 'string', 'max:55'],
            'lieu_residence' => ['nullable', 'string', 'max:255'],
            'photo' => ['nullable', 'image', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'L\'email est requis.',
            'email.email' => 'L\'email doit être une adresse email valide.',
            'email.unique' => 'L\'email doit être unique.',
            'login.string' => 'Le login doit être une chaîne de caractères.',
            'login.max' => 'Le login ne doit pas dépasser 100 caractères.',
            'login.unique' => 'Le login doit être unique.',
            'password.required' => 'Le mot de passe est requis.',
            'password.uncompromised' => 'Ce mot de passe a été compromis dans une fuite de données. Veuillez en choisir un autre.',
            'password.mixed_case' => 'Le mot de passe doit contenir au moins une majuscule et une minuscule.',
            'password.numbers' => 'Le mot de passe doit contenir au moins un chiffre.',
            'password.symbols' => 'Le mot de passe doit contenir au moins un symbole (+, *, #, etc...).',
            'password.min' => 'Le mot de passe doit contenir au moins 12 caractères.',
            'role_uuid.required' => 'Le role est requis.',
            'role_uuid.exists' => 'Le role n\'existe pas.',
            'user_type.required' => 'Le type d\'utilisateur est requis.',
            'user_type.in' => 'Le type d\'utilisateur est incorrect.',
            'partner_uuid.exists' => 'Le partenaire n\'existe pas.',
            'reseau_uuid.exists' => 'Le réseau n\'existe pas.',
            'agence_uuid.exists' => 'L\'agence n\'existe pas.',
            'nom.required' => 'Le nom est requis.',
            'nom.string' => 'Le nom doit être une chaîne de caractères.',
            'nom.max' => 'Le nom ne doit pas dépasser 55 caractères.',
            'prenoms.required' => 'Les prénoms sont requis.',
            'prenoms.string' => 'Les prénoms doivent être une chaîne de caractères.',
            'prenoms.max' => 'Les prénoms ne doivent pas dépasser 255 caractères.',
            'fonction.string' => 'La fonction doit être une chaîne de caractères.',
            'fonction.max' => 'La fonction ne doit pas dépasser 55 caractères.',
            'mobile_1.string' => 'Le numéro de téléphone 1 doit être une chaîne de caractères.',
            'mobile_1.max' => 'Le numéro de téléphone 1 ne doit pas dépasser 25 caractères.',
            'genre.in' => 'Le genre est incorrect.',
            'civilite.string' => 'La civilité doit être une chaîne de caractères.',
            'civilite.max' => 'La civilité ne doit pas dépasser 20 caractères.',
            'date_naissance.date' => 'La date de naissance doit être une date.',
            'lieu_naissance.string' => 'Le lieu de naissance doit être une chaîne de caractères.',
            'lieu_naissance.max' => 'Le lieu de naissance ne doit pas dépasser 55 caractères.',
            'lieu_residence.string' => 'Le lieu de résidence doit être une chaîne de caractères.',
            'lieu_residence.max' => 'Le lieu de résidence ne doit pas dépasser 255 caractères.',
            'photo.image' => 'La photo doit être une image.',
            'photo.max' => 'La photo ne doit pas dépasser 2Mo.',
        ];
    }
}
