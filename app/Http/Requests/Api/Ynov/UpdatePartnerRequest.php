<?php
// app/Http/Requests/Api/Ynov/UpdatePartnerRequest.php
namespace App\Http\Requests\Api\Ynov;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePartnerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('partners.modifier') ?? false;
    }

    public function rules(): array
    {
        $partner = $this->route('partner');
        $uuid = $partner ? $partner->uuid_partner : null;

        return [
            'code' => ['sometimes', 'string', 'max:55', Rule::unique('partners', 'code')->ignore($uuid, 'uuid_partner')],
            'designation' => ['sometimes', 'string', 'max:100'],
            'sigle' => ['nullable', 'string', 'max:20'],
            'description' => ['nullable', 'string'],
            'logo' => ['nullable', 'string', 'max:255'],
            'code_branche' => ['nullable', 'string', 'max:35'],
            'email' => ['nullable', 'email', 'max:100'],
            'email_2' => ['nullable', 'email', 'max:100'],
            'telephone' => ['nullable', 'string', 'max:25'],
            'telephone_2' => ['nullable', 'string', 'max:25'],
            'adresse' => ['nullable', 'string', 'max:255'],
            'ville' => ['nullable', 'string', 'max:100'],
            'pays' => ['nullable', 'string', 'max:100'],
            'site_web' => ['nullable', 'url', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'type' => ['nullable', 'string', 'max:50'],
            'secteur_activite' => ['nullable', 'string', 'max:100'],
            'categorie' => ['nullable', 'string', 'max:50'],
            'config' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
            'status' => ['nullable', 'string', Rule::in(['actif', 'inactif', 'suspendu'])],
            'date_agrement' => ['nullable', 'date'],
            'date_expiration' => ['nullable', 'date', 'after:date_agrement'],
        ];
    }
}