<?php

namespace App\Http\Requests\Api\Ynov\Rdv\Traitement;

use Illuminate\Foundation\Http\FormRequest;

class ObservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'observation' => ['required', 'string', 'max:1000'],
            'type_observation' => ['nullable', 'string', 'in:note,commentaire,alerte,info'],
        ];
    }

    public function messages(): array
    {
        return [
            'observation.required' => 'L\'observation est requise.',
            'observation.max' => 'L\'observation ne peut pas dépasser 1000 caractères.',
            'type_observation.in' => 'Le type d\'observation doit être note, commentaire, alerte ou info.',
        ];
    }
}
