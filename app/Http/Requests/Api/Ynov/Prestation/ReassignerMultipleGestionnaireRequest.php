<?php

namespace App\Http\Requests\Api\Ynov\Prestation;

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
            'prestation_uuids' => ['required', 'array', 'min:1'],
            'prestation_uuids.*' => ['required', 'string', 'exists:prestations,uuid_prestation'],
            'gestionnaire_uuid' => ['required', 'string', 'exists:users,uuid_user'],
            'observation' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'in:en_attente,transmis,accepte,rejete,annule'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'prestation_uuids.required' => 'La liste des prestations est requise.',
            'prestation_uuids.array' => 'La liste des prestations doit être un tableau.',
            'prestation_uuids.min' => 'Au moins une prestation doit être sélectionnée.',
            'prestation_uuids.*.exists' => 'Une ou plusieurs prestations n\'existent pas.',
            'gestionnaire_uuid.required' => 'Le gestionnaire est requis.',
            'gestionnaire_uuid.exists' => 'Le gestionnaire n\'existe pas.',
            'status.in' => 'Le statut doit être l\'une de ces valeurs : en_attente, transmis, accepte, rejete, annule.',
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
