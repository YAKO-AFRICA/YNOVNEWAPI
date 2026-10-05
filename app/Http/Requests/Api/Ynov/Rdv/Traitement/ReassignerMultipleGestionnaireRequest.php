<?php

namespace App\Http\Requests\Api\Ynov\Rdv\Traitement;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ReassignerMultipleGestionnaireRequest extends FormRequest
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
            'rdv_uuids' => ['required', 'array', 'min:1'],
            'rdv_uuids.*' => ['required', 'string', 'exists:rdvs,uuid_rdvs'],
            'gestionnaire_uuid' => ['required', 'string', 'exists:users,uuid_user'],
            'motif_reassignations' => ['required', 'array', 'min:1'],
            'motif_reassignations.*' => ['required', 'string', 'exists:motif_traitements,uuid_motif_traitements'],
            'agence_effective_uuid' => ['nullable', 'string', 'exists:agences,uuid_agence'],
            'date_rdv_effective' => ['nullable', 'date'],
            'observation' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'rdv_uuids.required' => 'La liste des RDV est requise.',
            'rdv_uuids.array' => 'La liste des RDV doit être un tableau.',
            'rdv_uuids.min' => 'Au moins un RDV doit être sélectionné.',
            'rdv_uuids.*.exists' => 'Un ou plusieurs RDV n\'existent pas.',
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

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Erreur de validation.',
                'errors' => $validator->errors(),
                'code' => 'VALIDATION_ERROR',
            ], 422)
        );
    }
}
