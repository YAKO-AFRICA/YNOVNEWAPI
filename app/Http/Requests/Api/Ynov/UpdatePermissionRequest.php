<?php

namespace App\Http\Requests\Api\Ynov;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePermissionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('permissions.creer') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'permission_group_uuid' => ['required', 'exists:permission_groups,uuid_permission_group'],
            'libelle' => ['required', 'string', 'max:100'],
            // 'libelle' => ['required', 'string', 'max:100', 'unique:permissions,libelle'],
            'action' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:100'],

        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'permission_group_uuid.required' => 'Le module est requis.',
            'permission_group_uuid.exists' => 'Le module n\'existe pas.',
            'libelle.required' => 'Le libelle est requis.',
            'libelle.string' => 'Le libelle doit être une chaîne de caractères.',
            'libelle.max' => 'Le libelle ne doit pas dépasser 100 caractères.',
            'action.required' => 'L\'action est requise.',
            'action.string' => 'L\'action doit être une chaîne de caractères.',
            'action.max' => 'L\'action ne doit pas dépasser 100 caractères.',
            // 'libelle.unique' => 'Le libelle doit être unique.',
            'description.string' => 'La description doit être une chaîne de caractères.',
            'category.string' => 'La category doit être une chaîne de caractères.',
            'category.max' => 'La category ne doit pas dépasser 100 caractères.',

        ];
    }
}
