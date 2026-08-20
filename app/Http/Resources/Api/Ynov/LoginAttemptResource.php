<?php
namespace App\Http\Resources\Api\Ynov;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoginAttemptResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'login_attempted' => $this->login_attempted,
            'ip_address' => $this->ip_address,
            'location' => $this->location,
            'is_successful' => $this->is_successful,
            'failure_reason' => $this->failure_reason,
            'attempted_at' => $this->attempted_at,
        ];
    }
}