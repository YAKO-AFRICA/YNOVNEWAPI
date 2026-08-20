<?php

namespace App\Http\Requests\Api\Ynov;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePermissionGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('permission_groups.modifier') ?? false;
    }

    public function rules(): array
    {
        return [
            'libelle' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'icone' => ['nullable', 'string', 'max:100'],
            'color' => ['nullable', 'string', 'max:50'],
            'ordre_affichage' => ['nullable', 'integer', 'min:0'],
            'parent_uuid' => ['nullable', 'exists:permission_groups,uuid_permission_group'],
            'route_prefix' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'libelle.required' => 'Le libellé est requis.',
            'libelle.string' => 'Le libellé doit être une chaîne de caractères.',
            'libelle.max' => 'Le libellé ne doit pas dépasser 100 caractères.',
            // 'libelle.unique' => 'Le libellé doit être unique.',
            'description.string' => 'La description doit être une chaîne de caractères.',
            'icone.string' => 'L\'icône doit être une chaîne de caractères.',
            'icone.max' => 'L\'icône ne doit pas dépasser 100 caractères.',
            'color.string' => 'La couleur doit être une chaîne de caractères.',
            'color.max' => 'La couleur ne doit pas dépasser 50 caractères.',
            'ordre_affichage.integer' => 'L\'ordre d\'affichage doit être un entier.',
            'ordre_affichage.min' => 'L\'ordre d\'affichage doit être supérieur ou égal à 0.',
            'parent_uuid.exists' => 'Le groupe parent n\'existe pas.',
            'route_prefix.string' => 'Le préfixe de route doit être une chaîne de caractères.',
            'route_prefix.max' => 'Le préfixe de route ne doit pas dépasser 100 caractères.',
        ];
    }
}
