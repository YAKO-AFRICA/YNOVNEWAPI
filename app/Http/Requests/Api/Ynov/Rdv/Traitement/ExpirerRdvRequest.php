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
            'motif_expirations' => ['required', 'array', 'min:1'],
            'motif_expirations.*' => ['required', 'string', 'exists:motif_traitements,uuid_motif_traitements'],
            'observation' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'motif_expirations.required' => 'Au moins un motif d\'expiration est requis.',
            'motif_expirations.array' => 'Les motifs d\'expiration doivent être un tableau.',
            'motif_expirations.min' => 'Au moins un motif d\'expiration est requis.',
            'motif_expirations.*.required' => 'Chaque motif d\'expiration est requis.',
            'motif_expirations.*.exists' => 'Un ou plusieurs motifs d\'expiration sont invalides.',
            'observation.max' => 'L\'observation ne peut pas dépasser 1000 caractères.',
        ];
    }
}
