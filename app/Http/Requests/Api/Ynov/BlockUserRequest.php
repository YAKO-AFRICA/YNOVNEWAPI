<?php
namespace App\Http\Requests\Api\Ynov;

use Illuminate\Foundation\Http\FormRequest;

class BlockUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('users.bloquer') ?? false;
    }

    public function rules(): array
    {
        return ['reason' => ['required', 'string', 'max:500']];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'Le motif est obligatoire.',
            'reason.string' => 'Le motif doit contenir des caractères.',
            'reason.max' => 'Le motif ne doit pas contenir plus de 500 caractères.',
        ];
    }
}