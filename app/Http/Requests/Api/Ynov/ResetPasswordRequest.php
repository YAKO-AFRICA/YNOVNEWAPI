<?php
namespace App\Http\Requests\Api\Ynov;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'login' => ['required', 'string', 'exists:users,login'],
            'token' => ['required', 'string'],
            'password' => [
                'required',
                'confirmed',
                Password::min(12)
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'login.required' => 'Le login est obligatoire.',
            'login.exists' => 'Le login n\'existe pas.',
            'token.required' => 'Le token est obligatoire.',
            'token.string' => 'Le token doit avoir un format valide.',
            'password.required' => 'Le mot de passe est obligatoire.',
            'password.confirmed' => 'Le mot de passe et la confirmation doivent être identiques.',
            'password.min' => 'Le mot de passe doit comporter au moins 12 caractères.',
            'password.mixedCase' => 'Le mot de passe doit contenir au moins une majuscule et une minuscule.',
            'password.symbols' => 'Le mot de passe doit contenir au moins un symbole (+, *, #, etc...).',
            'password.numbers' => 'Le mot de passe doit contenir au moins un chiffre.',
            'password.uncompromised' => 'Ce mot de passe a été compromis dans une fuite de données. Veuillez en choisir un autre.',
        ];
    }
}