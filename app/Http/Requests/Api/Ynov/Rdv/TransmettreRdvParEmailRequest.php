<?php

namespace App\Http\Requests\Api\Ynov\Rdv;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class TransmettreRdvParEmailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'gestionnaire_uuid' => ['required', 'exists:users,uuid_user'],
            'fichier' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240'], // Max 10MB
            'copie_cc' => ['nullable', 'array'],
            'copie_cc.*' => ['email'],
            'rdv_uuids' => ['nullable', 'array'],
            'rdv_uuids.*' => ['exists:rdvs,uuid_rdvs'],
        ];
    }

    public function messages(): array
    {
        return [
            'gestionnaire_uuid.required' => 'Le gestionnaire est requis.',
            'gestionnaire_uuid.exists' => 'Le gestionnaire n\'existe pas.',
            'fichier.required' => 'Le fichier Excel est requis.',
            'fichier.file' => 'Le fichier doit être un fichier valide.',
            'fichier.mimes' => 'Le fichier doit être au format Excel (xlsx ou xls).',
            'fichier.max' => 'Le fichier ne peut pas dépasser 10MB.',
            'copie_cc.*.email' => 'Les adresses en copie doivent être valides.',
            'rdv_uuids.*.exists' => 'Un ou plusieurs RDV n\'existent pas.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'code' => 'VALIDATION_ERROR',
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}
