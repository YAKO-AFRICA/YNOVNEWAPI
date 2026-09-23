<?php

namespace App\Http\Requests\Api\Ynov;

use Illuminate\Foundation\Http\FormRequest;

class SetPrimaryAgenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('agences.assigner_utilisateurs') ?? false;
    }

    public function rules(): array
    {
        return [
            'agence_uuid' => ['required', 'exists:agences,uuid_agence']
        ];
    }

    public function messages(): array
    {
        return [
            'agence_uuid.required' => 'L\'agence est requise.',
            'agence_uuid.exists' => 'L\'agence sélectionnée n\'existe pas.',
        ];
    }
}
