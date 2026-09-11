<?php

namespace App\Http\Requests\Api\Ynov;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMotifTraitementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('motif_traitements.modifier') ?? false;
    }

    public function rules(): array
    {
        return [
            'libelle' => ['sometimes', 'string', 'max:255'],
            'status' => ['sometimes', 'string', 'in:actif,inactif'],
            'type' => ['sometimes', 'array'],
            'type.*' => ['string', 'max:100'],
            'module' => ['sometimes', 'array'],
            'module.*' => ['string', 'max:100'],
        ];
    }
}
