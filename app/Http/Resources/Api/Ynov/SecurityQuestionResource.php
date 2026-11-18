<?php
// app/Http/Resources/Api/Ynov/SecurityQuestionResource.php
namespace App\Http\Resources\Api\Ynov;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SecurityQuestionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'question_text' => $this->question_text,
            'category' => $this->category,
            'is_active' => $this->is_active,
            'is_system' => $this->is_system,
            'user_answers_count' => $this->whenCounted('userAnswers'),
            'created_by' => $this->whenLoaded('createdBy', fn () => $this->userPayload($this->createdBy)),
            'updated_by' => $this->whenLoaded('updatedBy', fn () => $this->userPayload($this->updatedBy)),
            'deleted_by' => $this->whenLoaded('deletedBy', fn () => $this->userPayload($this->deletedBy)),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }

    private function userPayload($user): ?array
    {
        if (!$user) {
            return null;
        }

        return [
            'uuid_user' => $user->uuid_user,
            'login' => $user->login,
            'email' => $user->email,
            'details' => $user->relationLoaded('details') && $user->details ? [
                'nom' => $user->details->nom,
                'prenoms' => $user->details->prenoms,
                'full_name' => $user->details->full_name,
            ] : null,
        ];
    }
}
