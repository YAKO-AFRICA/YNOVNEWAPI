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
            // 'code' => ['sometimes', 'string', 'max:55', Rule::unique('partners', 'code')->ignore($uuid, 'uuid_partner')],
            'designation' => ['sometimes', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'logo' => ['nullable', 'file', 'max:' . config('documents.max_size')],
            'code_branche' => ['nullable', 'string', 'max:35'],
            'code_contractant' => ['nullable', 'string', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
            'status' => ['nullable', 'string', Rule::in(['actif', 'inactif', 'suspendu'])],
        ];
    }

    protected function prepareForValidation()
    {
        // Convertir is_active de string '1'/'0' à boolean pour FormData
        if ($this->has('is_active')) {
            $this->merge([
                'is_active' => in_array($this->input('is_active'), ['1', 'true', true], true),
            ]);
        }
    }

    public function messages(): array
    {
        return [
            'designation.string' => 'La désignation doit être une chaîne de caractères.',
            'designation.max' => 'La désignation ne peut pas dépasser 100 caractères.',
            
            'description.string' => 'La description doit être une chaîne de caractères.',
            
            'logo.file' => 'Le logo doit être un fichier.',
            'logo.max' => 'Le logo ne peut pas dépasser ' . config('documents.max_size') . ' octets.',
            
            'code_branche.string' => 'Le code branche doit être une chaîne de caractères.',
            'code_branche.max' => 'Le code branche ne peut pas dépasser 35 caractères.',
            
            'code_contractant.string' => 'Le code contractant doit être une chaîne de caractères.',
            'code_contractant.max' => 'Le code contractant ne peut pas dépasser 100 caractères.',
            
            'is_active.boolean' => 'Le champ actif doit être un booléen.',
            
            'status.string' => 'Le statut doit être une chaîne de caractères.',
            'status.in' => 'Le statut doit être actif, inactif ou suspendu.',
        ];
    }
}