<?php
namespace App\Http\Requests\Api\Ynov;

use Illuminate\Foundation\Http\FormRequest;

class AssignPermissionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('roles.gerer_permissions') ?? false;
    }

    public function rules(): array
    {
        return [
            'permission_uuids' => ['required', 'array', 'min:1'],
            'permission_uuids.*' => ['string', 'exists:permissions,uuid_permission'],
        ];
    }

    public function messages(): array
    {
        return [
            'permission_uuids.required' => 'Les UUIDs des permissions sont requis.',
            'permission_uuids.array' => 'Les UUIDs des permissions doivent être un tableau.',
            'permission_uuids.min' => 'Au moins une permission doit être sélectionnée.',
            'permission_uuids.*.string' => 'Les UUIDs des permissions doivent être des chaînes de caractères.',
            'permission_uuids.*.exists' => 'Les UUIDs des permissions doivent correspondre à des permissions existantes.',
        ];
    }
}