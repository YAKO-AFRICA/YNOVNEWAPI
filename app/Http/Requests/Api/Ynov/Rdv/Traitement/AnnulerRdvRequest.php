<?php

namespace App\Http\Requests\Api\Ynov\Rdv\Traitement;

use Illuminate\Foundation\Http\FormRequest;

class AnnulerRdvRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'motif_annulations' => ['required', 'array', 'min:1'],
            'motif_annulations.*' => ['required', 'string', 'exists:motif_traitements,uuid_motif_traitements'],
            'observation' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'motif_annulations.required' => 'Au moins un motif d\'annulation est requis.',
            'motif_annulations.array' => 'Les motifs d\'annulation doivent être un tableau.',
            'motif_annulations.min' => 'Au moins un motif d\'annulation est requis.',
            'motif_annulations.*.required' => 'Chaque motif d\'annulation est requis.',
            'motif_annulations.*.exists' => 'Un ou plusieurs motifs d\'annulation sont invalides.',
            'observation.max' => 'L\'observation ne peut pas dépasser 1000 caractères.',
        ];
    }
}
