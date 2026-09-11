<?php

namespace App\Http\Requests\Api\Ynov;

use Illuminate\Foundation\Http\FormRequest;

class StoreMotifTraitementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('motif_traitements.creer') ?? false;
    }

    public function rules(): array
    {
        return [
            'libelle' => ['required', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'in:actif,inactif'],
            'type' => ['nullable', 'array'],
            'type.*' => ['string', 'max:100'],
            'module' => ['nullable', 'array'],
            'module.*' => ['string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'libelle.required' => 'Le libellé est obligatoire.',
            'status.in' => 'Le statut doit être actif ou inactif.',
        ];
    }
}
