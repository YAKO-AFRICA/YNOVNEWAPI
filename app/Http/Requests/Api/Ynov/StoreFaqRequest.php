<?php
// app/Http/Requests/Api/Ynov/StoreFaqRequest.php
namespace App\Http\Requests\Api\Ynov;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFaqRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('faqs.creer') ?? false;
    }

    public function rules(): array
    {
        return [
            'faq_category_uuid' => ['required', 'exists:faq_categories,uuid_faq_category'],
            'category' => ['nullable', 'string', 'max:50'],
            'category_label' => ['nullable', 'string', 'max:100'],
            'question' => ['required', 'string', 'max:255'],
            'answer' => ['required', 'string'],
            'order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'faq_category_uuid.required' => 'La catégorie est obligatoire.',
            'faq_category_uuid.exists' => 'La catégorie sélectionnée n\'existe pas.',
            'question.required' => 'La question est obligatoire.',
            'answer.required' => 'La réponse est obligatoire.',
        ];
    }
}