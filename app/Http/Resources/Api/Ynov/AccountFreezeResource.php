<?php

namespace App\Http\Resources\Api\Ynov;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountFreezeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->getKey(),
            'user_uuid' => $this->user_uuid,
            'user' => $this->whenLoaded('user', fn () => $this->user ? [
                'uuid_user' => $this->user->uuid_user,
                'login' => $this->user->login,
                'email' => $this->user->email,
            ] : null),
            'freeze_level' => $this->freeze_level,
            'failed_attempts_count' => $this->failed_attempts_count,
            'frozen_at' => $this->frozen_at?->toISOString(),
            'unfrozen_at' => $this->unfrozen_at?->toISOString(),
            'unfrozen_by' => $this->unfrozen_by,
            'unfrozen_by_user' => $this->whenLoaded('unfrozenBy', fn () => $this->unfrozenBy ? [
                'uuid_user' => $this->unfrozenBy->uuid_user,
                'login' => $this->unfrozenBy->login,
                'email' => $this->unfrozenBy->email,
            ] : null),
            'is_active' => $this->isActive(),
            'remaining_seconds' => $this->remaining_seconds,
        ];
    }
}
