<?php
// app/Http/Requests/Api/Ynov/StorePartnerRequest.php
namespace App\Http\Requests\Api\Ynov;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePartnerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('partners.creer') ?? false;
    }



    public function rules(): array
    {
        return [
            'code'            => ['required', 'string', 'max:55', 'unique:partners,code'],
            'designation'     => ['required', 'string', 'max:100'],
            'code_contractant'=> ['required', 'string', 'max:100'],
            'code_branche'    => ['nullable', 'string', 'max:35'],
            'description'     => ['nullable', 'string'],
            'logo'            => ['nullable', 'file', 'max:' . config('documents.max_size')],
            'is_active'       => ['nullable', 'boolean'],
            'status'          => ['nullable', 'string', Rule::in(['actif', 'inactif', 'suspendu'])],
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
            'code.required' => 'Le code est obligatoire.',
            'code.string' => 'Le code doit être une chaîne de caractères.',
            'code.max' => 'Le code ne peut pas dépasser 55 caractères.',
            'code.unique' => 'Ce code existe déjà.',
            
            'designation.required' => 'La désignation est obligatoire.',
            'designation.string' => 'La désignation doit être une chaîne de caractères.',
            'designation.max' => 'La désignation ne peut pas dépasser 100 caractères.',
            
            'code_contractant.string' => 'Le code contractant doit être une chaîne de caractères.',
            'code_contractant.max' => 'Le code contractant ne peut pas dépasser 100 caractères.',
            
            'code_branche.string' => 'Le code branche doit être une chaîne de caractères.',
            'code_branche.max' => 'Le code branche ne peut pas dépasser 35 caractères.',
            
            'description.string' => 'La description doit être une chaîne de caractères.',
            
            'logo.file' => 'Le logo doit être un fichier.',
            'logo.max' => 'Le logo ne peut pas dépasser ' . config('documents.max_size') . ' octets.',
            
            'is_active.boolean' => 'Le champ actif doit être un booléen.',
            
            'status.string' => 'Le statut doit être une chaîne de caractères.',
            'status.in' => 'Le statut doit être actif, inactif ou suspendu.',
        ];
    }
}