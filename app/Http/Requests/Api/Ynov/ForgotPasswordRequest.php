<?php
namespace App\Http\Requests\Api\Ynov;

use Illuminate\Foundation\Http\FormRequest;

class ForgotPasswordRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return ['login' => ['required', 'string', 'max:100', 'exists:users,login']];
    }
    public function messages(): array
    {
        return ['login.exists' => 'Aucun compte associé à cet login.'];
    }
}