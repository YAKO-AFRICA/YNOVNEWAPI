<?php
namespace App\Http\Resources\Api\Ynov;

use App\Http\Resources\Api\Ynov\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoginResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'user' => new UserResource($this->user),
            'access_token' => $this->token,
            'token_type' => 'Bearer',
            'expires_at' => $this->expires_at,
            'requires_2fa' => $this->requires_2fa,
            'must_change_password' => $this->must_change_password,
            'trusted_device' => $this->trusted_device,
        ];
    }
}