<?php
// app/Http/Resources/Api/Ynov/NotificationResource.php
namespace App\Http\Resources\Api\Ynov;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    private const CHANNEL_LABELS = [
        'database' => ['code' => 'database', 'label' => 'Base de données', 'icon' => 'bi-database'],
        'email' => ['code' => 'email', 'label' => 'Email', 'icon' => 'bi-envelope'],
        'sms' => ['code' => 'sms', 'label' => 'SMS', 'icon' => 'bi-phone'],
        'push' => ['code' => 'push', 'label' => 'Push mobile', 'icon' => 'bi-bell'],
        'whatsapp' => ['code' => 'whatsapp', 'label' => 'WhatsApp', 'icon' => 'bi-whatsapp'],
    ];

    public function toArray(Request $request): array
    {
        return [
            'uuid_notification' => $this->uuid_notification,
            'title' => $this->title,
            'body' => $this->body,
            'type' => $this->type,
            'channel' => $this->channel,
            'channels' => $this->metadata['channels'] ?? [$this->channel],
            'channels_details' => collect($this->metadata['channels'] ?? [$this->channel])->map(function ($code) {
                return self::CHANNEL_LABELS[$code] ?? ['code' => $code, 'label' => $code];
            })->values(),
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
            'user' => $this->whenLoaded('user', function () {
                return [
                    'uuid_user' => $this->user->uuid_user,
                    'login' => $this->user->login,
                    'email' => $this->user->email,
                    'details' => [
                        'nom' => $this->user->details?->nom,
                        'prenoms' => $this->user->details?->prenoms,
                        'full_name' => $this->user->details?->full_name,
                    ],
                ];
            }),
        ];
    }
}