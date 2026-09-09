<?php

namespace App\Http\Requests\Api\Ynov\Rdv\Traitement;

use Illuminate\Foundation\Http\FormRequest;

class ExpirerRdvRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'motif_expiration' => ['required', 'string', 'max:500'],
            'observation' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'motif_expiration.required' => 'Le motif d\'expiration est requis.',
            'motif_expiration.max' => 'Le motif d\'expiration ne peut pas dépasser 500 caractères.',
            'observation.max' => 'L\'observation ne peut pas dépasser 1000 caractères.',
        ];
    }
}
