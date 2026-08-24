<?php
namespace App\Http\Requests\Api\Ynov;

use Illuminate\Foundation\Http\FormRequest;

class ForgotPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'login' => [
                'required',
                'string',
                'max:100',
                'exists:users,login',
            ],

            'option' => [
                'required',
                'in:sms,email,whatsapp,question_secrete',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'login.exists' =>
                'Aucun compte associé à ce login.',

            'option.in' =>
                'Option non supportée. Utilisez sms, email, whatsapp ou question_secrete.',

            'option.required' =>
                'Option manquante.',

            'login.required' =>
                'Login manquant.',
        ];
    }
}