<?php
namespace App\Http\Requests\Api\Ynov;

use Illuminate\Foundation\Http\FormRequest;

class TwoFactorEnableRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array { return ['code' => ['required', 'string', 'size:6']]; }
    public function messages(): array { return ['code.required' => 'Le code est obligatoire.', 'code.size' => 'Le code doit contenir 6 caractères.']; }
}