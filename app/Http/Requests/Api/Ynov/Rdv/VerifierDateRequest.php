<?php
// app/Http/Requests/Api/Ynov/Rdv/VerifierDateRequest.php

namespace App\Http\Requests\Api\Ynov\Rdv;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class VerifierDateRequest extends FormRequest
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
            'date_rdv' => ['required', 'date', 'after_or_equal:today'],
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
            'date_rdv.required' => 'La date du rendez-vous est obligatoire.',
            'date_rdv.date' => 'La date du rendez-vous doit être une date valide.',
            'date_rdv.after_or_equal' => 'La date du rendez-vous doit être aujourd\'hui ou une date future.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'agence_uuid' => 'UUID de l\'agence',
            'date_rdv' => 'date du rendez-vous',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('date_rdv')) {
            $this->merge([
                'date_rdv' => Carbon::parse($this->date_rdv)->format('Y-m-d'),
            ]);
        }
    }
}