<?php
// app/Http/Resources/Api/Ynov/NotificationCampaignResource.php
namespace App\Http\Resources\Api\Ynov;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationCampaignResource extends JsonResource
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
        $channels = isset($this->channels_json)
            ? (json_decode($this->channels_json, true) ?? [$this->channel])
            : [$this->channel];

        return [
            'batch_uuid' => $this->batch_uuid,
            'title' => $this->title,
            'body' => $this->body,
            'type' => $this->type,
            'channel' => $this->channel,
            'channels' => $channels,
            'channels_details' => collect($channels)->map(function ($code) {
                return self::CHANNEL_LABELS[$code] ?? ['code' => $code, 'label' => $code];
            })->values(),
            'action_url' => $this->action_url,
            'action_label' => $this->action_label,
            'sent_at' => $this->sent_at,
            'recipients' => [
                'total' => (int) $this->recipients_total,
                'read' => (int) $this->recipients_read,
                'unread' => (int) $this->recipients_unread,
                'important' => (int) $this->recipients_important,
            ],
            'created_by' => $this->whenLoaded('createdBy', function () {
                return $this->createdBy ? [
                    'uuid_user' => $this->createdBy->uuid_user,
                    'login' => $this->createdBy->login,
                    'email' => $this->createdBy->email,
                    'details' => [
                        'nom' => $this->createdBy->details?->nom,
                        'prenoms' => $this->createdBy->details?->prenoms,
                        'full_name' => $this->createdBy->details?->full_name,
                    ],
                ] : null;
            }),
        ];
    }
}
