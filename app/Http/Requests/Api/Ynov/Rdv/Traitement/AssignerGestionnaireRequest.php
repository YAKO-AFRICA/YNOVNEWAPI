<?php

namespace App\Http\Requests\Api\Ynov\Rdv\Traitement;

use Illuminate\Foundation\Http\FormRequest;

class AssignerGestionnaireRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'gestionnaire_uuid' => ['required', 'exists:users,uuid_user'],
        ];
    }

    public function messages(): array
    {
        return [
            'gestionnaire_uuid.required' => 'Le gestionnaire est requis.',
            'gestionnaire_uuid.exists' => 'Le gestionnaire n\'existe pas.',
        ];
    }
}
