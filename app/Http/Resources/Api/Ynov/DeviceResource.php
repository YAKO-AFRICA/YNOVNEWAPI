<?php
namespace App\Http\Resources\Api\Ynov;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeviceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            
            'uuid_device' => $this->uuid_device,
            'device_name' => $this->device_name,
            'device_type' => $this->device_type,
            'os' => $this->os,
            'browser' => $this->browser,
            'ip_address' => $this->ip_address,
            'location' => $this->location,
            'is_trusted' => $this->is_trusted,
            'last_used_at' => $this->last_used_at,
            'created_at' => $this->created_at,

            'user' => new UserResource($this->user),
        ];
    }
}