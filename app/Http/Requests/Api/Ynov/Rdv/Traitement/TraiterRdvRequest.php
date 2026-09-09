<?php

namespace App\Http\Requests\Api\Ynov\Rdv\Traitement;

use Illuminate\Foundation\Http\FormRequest;

class TraiterRdvRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date_traitement' => ['required', 'date'],
            'is_permitted' => ['required', 'boolean'], // false = conservation, true = sortie de portefeuille
            'motif_traitement' => ['nullable', 'array'],
            'motif_traitement.*' => ['string', 'max:500'],
            'observation' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'date_traitement.required' => 'La date de traitement est requise.',
            'date_traitement.date' => 'La date de traitement doit être une date valide.',
            'motif_traitement.*.max' => 'Chaque motif ne peut pas dépasser 500 caractères.',
            'observation.max' => 'L\'observation ne peut pas dépasser 1000 caractères.',
        ];
    }
}
