<?php

namespace App\Http\Requests\Api\Ynov\Prestation\Traitement;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class AnnulerPrestationRequest extends FormRequest
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
            'motif_traitements' => ['nullable', 'array'],
            'motif_traitements.*' => ['string', 'exists:motif_traitements,uuid_motif_traitement'],
            'observation' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'motif_traitements.*.exists' => 'Le motif de traitement sélectionné n\'existe pas.',
            'observation.max' => 'L\'observation ne doit pas dépasser 1000 caractères.',
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