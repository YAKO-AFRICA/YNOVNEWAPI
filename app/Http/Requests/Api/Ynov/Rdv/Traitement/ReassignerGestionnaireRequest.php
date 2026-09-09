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
            'motif_reassignation' => ['required', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'gestionnaire_uuid.required' => 'Le nouveau gestionnaire est requis.',
            'gestionnaire_uuid.exists' => 'Le nouveau gestionnaire n\'existe pas.',
            'motif_reassignation.required' => 'Le motif de réassignation est requis.',
            'motif_reassignation.max' => 'Le motif de réassignation ne peut pas dépasser 500 caractères.',
        ];
    }
}
