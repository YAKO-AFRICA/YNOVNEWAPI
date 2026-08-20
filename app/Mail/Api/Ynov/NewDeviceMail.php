<?php

namespace App\Mail\Api\Ynov;

use App\Models\Api\Ynov\parameter\User;
use App\Models\Api\Ynov\parameter\UserDevice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewDeviceMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public UserDevice $device,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'YNOV — Nouvelle connexion détectée sur votre compte',
        );
    }

    public function content(): Content
    {
        $details = $this->user->details;

        return new Content(
            view: 'emails.api.ynov.new-device',
            with: [
                'fullName' => $details ? trim($details->prenoms . ' ' . $details->nom) : $this->user->login,
                'deviceName' => $this->device->device_name ?? 'Appareil inconnu',
                'browserOs' => trim(($this->device->browser ?? '—') . ' / ' . ($this->device->os ?? '—')),
                'ipAddress' => $this->device->ip_address,
                'location' => $this->device->location ?? 'Non déterminée',
                'loginAt' => now()->format('d/m/Y à H:i'),
                'securityUrl' => config('app.frontend_url') . '/profile/devices',
            ],
        );
    }
}
