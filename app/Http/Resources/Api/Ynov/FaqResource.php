<?php
// app/Http/Resources/Api/Ynov/FaqResource.php
namespace App\Http\Resources\Api\Ynov;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FaqResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid_faq' => $this->uuid_faq,
            'faq_category' => $this->whenLoaded('faqCategory', function () {
                return [
                    'uuid' => $this->faqCategory->uuid_faq_category,
                    'code' => $this->faqCategory->code,
                    'label' => $this->faqCategory->label,
                    'icon' => $this->faqCategory->icon,
                    'color' => $this->faqCategory->color,
                ];
            }),
            'category' => $this->category,
            'category_label' => $this->category_label ?? $this->faqCategory?->label,
            'question' => $this->question,
            'answer' => $this->answer,
            'order' => $this->order,
            'is_active' => $this->is_active,
            'is_featured' => $this->is_featured,
            'tags' => $this->tags,
            'views' => $this->views,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}