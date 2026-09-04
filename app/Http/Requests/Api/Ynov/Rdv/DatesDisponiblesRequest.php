<?php
// app/Http/Requests/Api/Ynov/Rdv/DatesDisponiblesRequest.php

namespace App\Http\Requests\Api\Ynov\Rdv;

use Illuminate\Foundation\Http\FormRequest;

class DatesDisponiblesRequest extends FormRequest
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
            'agence_uuid' => ['required', 'string', 'exists:agences,uuid_agence'],
            'mois' => ['required', 'integer', 'min:1', 'max:12'],
            'annee' => ['required', 'integer', 'min:2020', 'max:2100'],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'agence_uuid.required' => 'L\'UUID de l\'agence est obligatoire.',
            'agence_uuid.exists' => 'L\'agence n\'existe pas.',
            'mois.required' => 'Le mois est obligatoire.',
            'mois.min' => 'Le mois doit être compris entre 1 et 12.',
            'mois.max' => 'Le mois doit être compris entre 1 et 12.',
            'annee.required' => 'L\'année est obligatoire.',
            'annee.min' => 'L\'année doit être comprise entre 2020 et 2100.',
            'annee.max' => 'L\'année doit être comprise entre 2020 et 2100.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'agence_uuid' => 'UUID de l\'agence',
            'mois' => 'mois',
            'annee' => 'année',
        ];
    }

    /**
     * Get the validated date parameters.
     */
    public function getDateParams(): array
    {
        return [
            'agence_uuid' => $this->agence_uuid,
            'mois' => (int) $this->mois,
            'annee' => (int) $this->annee,
        ];
    }
}