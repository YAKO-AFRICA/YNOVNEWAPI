<?php
namespace App\Http\Requests\Api\Ynov;

use Illuminate\Foundation\Http\FormRequest;

class TwoFactorEnableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'method' => ['required', 'string', 'in:authenticator,otp'],
            'code' => ['required', 'string', 'size:6'],
            'channel' => ['required_if:method,otp', 'string', 'in:email,sms,whatsapp'],
        ];
    }

    public function messages(): array
    {
        return [
            'method.required' => 'La méthode de 2FA est obligatoire.',
            'method.in' => 'La méthode doit être "authenticator" ou "otp".',
            'code.required' => 'Le code est obligatoire.',
            'code.size' => 'Le code doit contenir 6 caractères.',
            'channel.required_if' => 'Le canal d\'envoi est obligatoire pour la méthode OTP.',
            'channel.in' => 'Le canal doit être email, sms ou whatsapp.',
        ];
    }
}