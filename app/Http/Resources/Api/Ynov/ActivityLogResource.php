<?php

namespace App\Http\Resources\Api\Ynov;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid_activity_log' => $this->uuid_activity_log,
            'user_uuid' => $this->user_uuid,
            'user' => $this->whenLoaded('user', fn () => $this->user ? [
                'uuid_user' => $this->user->uuid_user,
                'login' => $this->user->login,
                'email' => $this->user->email,
            ] : null),
            'action' => $this->action,
            'action_type' => $this->action_type,
            'module' => $this->module,
            'description' => $this->description,
            'resource_type' => $this->resource_type,
            'resource_id' => $this->resource_id,
            'old_values' => $this->redact($this->old_values),
            'new_values' => $this->redact($this->new_values),
            'metadata' => $this->redact($this->metadata),
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'location' => $this->location,
            'level' => $this->level,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }

    private function redact(mixed $value): mixed
    {
        if (!is_array($value)) {
            return $value;
        }

        $sanitized = [];

        foreach ($value as $key => $item) {
            if (is_string($key) && preg_match('/password|token|secret|api.?key|authorization|signature|otp|recovery.?code/i', $key)) {
                $sanitized[$key] = '[REDACTED]';
                continue;
            }

            $sanitized[$key] = $this->redact($item);
        }

        return $sanitized;
    }
}
