<?php

namespace App\Http\Requests\Api\Ynov;

use Illuminate\Foundation\Http\FormRequest;

class AssignAgencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('agences.assigner_utilisateurs') ?? false;
    }

    public function rules(): array
    {
        return [
            'agence_uuids' => ['required', 'array', 'min:1'],
            'agence_uuids.*' => ['required', 'exists:agences,uuid_agence'],
            'replace' => ['nullable', 'boolean'] // Option pour remplacer ou ajouter
        ];
    }

    public function messages(): array
    {
        return [
            'agence_uuids.required' => 'Au moins une agence doit être sélectionnée.',
            'agence_uuids.min' => 'Au moins une agence doit être sélectionnée.',
            'agence_uuids.*.exists' => 'Une ou plusieurs agences n\'existent pas.',
        ];
    }
}
