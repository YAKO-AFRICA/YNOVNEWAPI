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
            'motif_traitements' => ['required', 'array', 'min:1'],
            'motif_traitements.*' => ['required', 'string', 'exists:motif_traitements,uuid'],
            'observation' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'date_traitement.required' => 'La date de traitement est requise.',
            'date_traitement.date' => 'La date de traitement doit être une date valide.',
            'motif_traitements.required' => 'Au moins un motif de traitement est requis.',
            'motif_traitements.array' => 'Les motifs de traitement doivent être un tableau.',
            'motif_traitements.min' => 'Au moins un motif de traitement est requis.',
            'motif_traitements.*.required' => 'Chaque motif de traitement est requis.',
            'motif_traitements.*.exists' => 'Un ou plusieurs motifs de traitement sont invalides.',
            'observation.max' => 'L\'observation ne peut pas dépasser 1000 caractères.',
        ];
    }
}
