<?php
// app/Http/Resources/Api/Ynov/NotificationResource.php
namespace App\Http\Resources\Api\Ynov;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid_notification' => $this->uuid_notification,
            'title' => $this->title,
            'body' => $this->body,
            'type' => $this->type,
            'action_url' => $this->action_url,
            'action_label' => $this->action_label,
            'is_read' => $this->isRead(),
            'is_important' => $this->isImportant(),
            'read_at' => $this->read_at?->toDateTimeString(),
            'important_at' => $this->important_at?->toDateTimeString(),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
            'group_notif' => $this->whenLoaded('groupNotif', function () {
                return [
                    'uuid_group_notif' => $this->groupNotif->uuid_group_notif,
                    'code' => $this->groupNotif->code,
                    'libelle' => $this->groupNotif->libelle,
                ];
            }),
            'metadata' => $this->metadata,
        ];
    }
}