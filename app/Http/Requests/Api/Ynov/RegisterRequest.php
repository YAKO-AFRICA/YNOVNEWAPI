<?php

// app/Http/Requests/Api/Ynov/RegisterRequest.php
namespace App\Http\Requests\Api\Ynov;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;


class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'prenoms' => ['required', 'string', 'max:255'],
            'nom' => ['required', 'string', 'max:55'],
            'email' => ['required', 'email', 'max:100', 'unique:users,email'],
            'login' => ['nullable', 'string', 'max:100', 'unique:users,login'],
            'mobile_1' => ['nullable', 'string', 'max:25'],
            'password' => [
                'required',
                'confirmed',
                Password::min(12)
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),  // Vérification Have I Been Pwned
            ],

            // ================================================================
            // CORRECTION #23 : Captcha optionnel (à activer selon besoin)
            // ================================================================
            // 'g-recaptcha-response' => ['sometimes', 'required', 'recaptcha'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'Cet email est déjà utilisé.',
            'login.unique' => 'Cet identifiant est déjà utilisé.',
            'password.min' => 'Le mot de passe doit contenir au moins 12 caractères.',
            'password.confirmed' => 'Les mots de passe ne correspondent pas.',
            'password.mixed_case' => 'Le mot de passe doit contenir au moins une majuscule et une minuscule.',
            'password.numbers' => 'Le mot de passe doit contenir au moins un chiffre.',
            'password.symbols' => 'Le mot de passe doit contenir au moins un symbole (+, *, #, etc...).',
            'password.uncompromised' => 'Ce mot de passe a été compromis dans une fuite de données. Veuillez en choisir un autre.',
        ];
    }

}
