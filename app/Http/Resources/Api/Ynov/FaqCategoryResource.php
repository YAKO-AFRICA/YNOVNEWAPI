<?php
// app/Http/Resources/Api/Ynov/FaqCategoryResource.php
namespace App\Http\Resources\Api\Ynov;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FaqCategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid_faq_category' => $this->uuid_faq_category,
            'code' => $this->code,
            'label' => $this->label,
            'icon' => $this->icon,
            'color' => $this->color,
            'description' => $this->description,
            'order' => $this->order,
            'is_active' => $this->is_active,
            'is_default' => $this->is_default,
            'faqs_count' => $this->faqs()->count(),
            'active_faqs_count' => $this->activeFaqs()->count(),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}