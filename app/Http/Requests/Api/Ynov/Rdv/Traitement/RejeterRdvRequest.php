<?php

namespace App\Http\Requests\Api\Ynov\Rdv\Traitement;

use Illuminate\Foundation\Http\FormRequest;

class RejeterRdvRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'motif_rejets' => ['required', 'array', 'min:1'],
            'motif_rejets.*' => ['required', 'string', 'exists:motif_traitements,uuid'],
            'observation' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'motif_rejets.required' => 'Au moins un motif de rejet est requis.',
            'motif_rejets.array' => 'Les motifs de rejet doivent être un tableau.',
            'motif_rejets.min' => 'Au moins un motif de rejet est requis.',
            'motif_rejets.*.required' => 'Chaque motif de rejet est requis.',
            'motif_rejets.*.exists' => 'Un ou plusieurs motifs de rejet sont invalides.',
            'observation.max' => 'L\'observation ne peut pas dépasser 1000 caractères.',
        ];
    }
}
