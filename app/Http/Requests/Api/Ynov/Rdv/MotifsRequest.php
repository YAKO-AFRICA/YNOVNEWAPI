<?php
// app/Http/Requests/Api/Ynov/Rdv/MotifsRequest.php

namespace App\Http\Requests\Api\Ynov\Rdv;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MotifsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'code_produit' => ['required', 'string', 'exists:produits,code'],
            'impact' => ['nullable', 'string', Rule::in(['0', '1'])],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'code_produit.required' => 'Le code du produit est obligatoire.',
            'code_produit.exists' => 'Le code du produit n\'existe pas.',
            'impact.in' => 'L\'impact doit être 0 (non sortie portefeuille) ou 1 (sortie portefeuille).',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'code_produit' => 'code du produit',
            'impact' => 'impact',
        ];
    }

    /**
     * Get the impact value or null if not provided.
     */
    public function getImpact(): ?string
    {
        return $this->has('impact') && $this->impact !== '' ? $this->impact : null;
    }
}