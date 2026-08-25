<?php
// app/Http/Requests/Api/Ynov/UpdateFaqRequest.php
namespace App\Http\Requests\Api\Ynov;

use Illuminate\Foundation\Http\FormRequest;
// use Illuminate\Validation\Rule;

class UpdateFaqRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('faqs.modifier') ?? false;
    }

    public function rules(): array
    {
        return [
            'faq_category_uuid' => ['sometimes', 'exists:faq_categories,uuid_faq_category'],
            'category' => ['nullable', 'string', 'max:50'],
            'category_label' => ['nullable', 'string', 'max:100'],
            'question' => ['sometimes', 'string', 'max:255'],
            'answer' => ['sometimes', 'string'],
            'order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
        ];
    }
}