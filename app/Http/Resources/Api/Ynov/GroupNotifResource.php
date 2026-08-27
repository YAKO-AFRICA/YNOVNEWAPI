<?php
// app/Http/Resources/Api/Ynov/GroupNotifResource.php
namespace App\Http\Resources\Api\Ynov;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GroupNotifResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid_group_notif' => $this->uuid_group_notif,
            'code' => $this->code,
            'libelle' => $this->libelle,
            'description' => $this->description,
            'channels' => $this->channels,
            'preferences' => $this->preferences,
            'status' => $this->status,
            'is_active' => $this->isActive(),
            'users_count' => $this->users_count ?? $this->users()->count(),
            'users' => $this->whenLoaded('users', function () {
                return $this->users->map(function ($user) {
                    return [
                        'uuid_user' => $user->uuid_user,
                        'email' => $user->email,
                        'login' => $user->login,
                        'details' => [
                            'nom' => $user->details?->nom,
                            'prenoms' => $user->details?->prenoms,
                            'full_name' => $user->details?->full_name,
                        ],
                        'pivot' => [
                            'is_primary' => $user->pivot->is_primary ?? false,
                            'is_active' => $user->pivot->is_active ?? true,
                            'assigned_at' => $user->pivot->assigned_at?->toDateTimeString(),
                        ],
                    ];
                });
            }),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}