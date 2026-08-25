<?php
// app/Http/Requests/Api/Ynov/StoreFaqCategoryRequest.php
namespace App\Http\Requests\Api\Ynov;

use Illuminate\Foundation\Http\FormRequest;

class StoreFaqCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('faq_categories.creer') ?? false;
    }

    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:100'],
            'code' => ['nullable', 'string', 'max:50', 'unique:faq_categories,code'],
            'icon' => ['nullable', 'string', 'max:50'],
            'color' => ['nullable', 'string', 'max:20'],
            'description' => ['nullable', 'string', 'max:500'],
            'order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'label.required' => 'Le libellé est obligatoire.',
            'label.max' => 'Le libellé ne doit pas dépasser 100 caractères.',
            'code.unique' => 'Ce code est déjà utilisé.',
            'order.integer' => 'L\'ordre doit être un nombre entier.',
            'order.min' => 'L\'ordre doit être supérieur ou égal à 0.',
        ];
    }
}