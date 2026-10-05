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
            'code_contractant'=> ['nullable', 'string', 'max:100'],
            'description'     => ['nullable', 'string'],
            'logo'            => ['nullable', 'string', 'max:255'],
            'is_active'       => ['nullable', 'boolean'],
            'status'          => ['nullable', 'string', Rule::in(['actif', 'inactif', 'suspendu'])],
        ];
    }
}