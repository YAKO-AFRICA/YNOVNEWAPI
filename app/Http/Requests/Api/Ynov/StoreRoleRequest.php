<?php
namespace App\Http\Requests\Api\Ynov;

use Illuminate\Foundation\Http\FormRequest;

class StoreRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('roles.creer') ?? false;
    }

    public function rules(): array
    {
        return [
            'libelle' => ['required', 'string', 'max:100', 'unique:roles,libelle'],
            'description' => ['nullable', 'string'],
            'level' => ['nullable', 'integer', 'min:0'],
            'priority' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', 'in:actif,inactif'],
        ];
    }

    public function attributes(): array
    {
        return [
            'libelle' => 'Libellé',
            'description' => 'Description',
            'level' => 'Niveau',
            'priority' => 'Priorité',
        ];
    }

    public function messages(): array
    {
        return [
            'libelle.required' => 'Le libellé est requis.',
            'libelle.string' => 'Le libellé doit être une chaîne de caractères.',
            'libelle.max' => 'Le libellé ne doit pas dépasser 100 caractères.',
            'libelle.unique' => 'Le libellé doit être unique.',
            'description.string' => 'La description doit être une chaîne de caractères.',
            'level.integer' => 'Le niveau doit être un entier.',
            'level.min' => 'Le niveau doit être supérieur ou égal à 0.',
            'priority.integer' => 'La priorité doit être un entier.',
            'priority.min' => 'La priorité doit être supérieur ou égal à 0.',
            'status.in' => 'Le statut doit être "actif" ou "inactif".',
        ];
    }
}