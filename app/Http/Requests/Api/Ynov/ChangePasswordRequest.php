<?php
namespace App\Http\Requests\Api\Ynov;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ChangePasswordRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string'],
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
            'password.min' => 'Le mot de passe doit contenir au moins 12 caractères.',
            'password.mixed_case' => 'Majuscules et minuscules requis.',
            'password.numbers' => 'Au moins un chiffre requis.',
            'password.symbols' => 'Au moins un symbole requis.',
            'password.uncompromised' => 'Ce mot de passe a été compromis dans une fuite de données. Veuillez en choisir un autre.',
        ];
    }
}