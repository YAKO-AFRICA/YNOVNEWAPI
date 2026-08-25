<?php
// app/Http/Requests/Api/Ynov/UpdateReseauRequest.php
namespace App\Http\Requests\Api\Ynov;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateReseauRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('reseaux.modifier') ?? false;
    }

    public function rules(): array
    {
        $reseau = $this->route('reseau');
        $uuid = $reseau ? $reseau->uuid_reseau : null;

        return [
            'code' => ['sometimes', 'string', 'max:55', Rule::unique('reseaux', 'code')->ignore($uuid, 'uuid_reseau')],
            'libelle' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'partner_uuid' => ['nullable', 'exists:partners,uuid_partner'],
            'email' => ['nullable', 'email', 'max:100'],
            'telephone' => ['nullable', 'string', 'max:25'],
            'config' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
            'status' => ['nullable', 'string', Rule::in(['actif', 'inactif'])],
        ];
    }
}