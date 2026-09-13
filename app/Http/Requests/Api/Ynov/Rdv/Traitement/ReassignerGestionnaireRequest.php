<?php

namespace App\Http\Requests\Api\Ynov\Rdv\Traitement;

use Illuminate\Foundation\Http\FormRequest;

class ReassignerGestionnaireRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'gestionnaire_uuid' => ['required', 'exists:users,uuid_user'],
            'motif_reassignations' => ['required', 'array', 'min:1'],
            'motif_reassignations.*' => ['required', 'string', 'exists:motif_traitements,uuid'],
            'agence_effective_uuid' => ['nullable', 'exists:agences,uuid_agence'],
            'date_rdv_effective' => ['nullable', 'date'],
            'observation' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'gestionnaire_uuid.required' => 'Le nouveau gestionnaire est requis.',
            'gestionnaire_uuid.exists' => 'Le nouveau gestionnaire n\'existe pas.',
            'motif_reassignations.required' => 'Au moins un motif de réassignation est requis.',
            'motif_reassignations.array' => 'Les motifs de réassignation doivent être un tableau.',
            'motif_reassignations.min' => 'Au moins un motif de réassignation est requis.',
            'motif_reassignations.*.required' => 'Chaque motif de réassignation est requis.',
            'motif_reassignations.*.exists' => 'Un ou plusieurs motifs de réassignation sont invalides.',
            'agence_effective_uuid.exists' => 'L\'agence spécifiée n\'existe pas.',
            'date_rdv_effective.date' => 'La date du RDV doit être une date valide.',
            'observation.max' => 'L\'observation ne peut pas dépasser 1000 caractères.',
        ];
    }
}
