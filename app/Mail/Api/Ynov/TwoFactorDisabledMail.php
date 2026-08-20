<?php

namespace App\Mail\Api\Ynov;

use App\Models\Api\Ynov\parameter\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Email envoyé lorsque la 2FA est désactivée — événement sensible qui
 * mérite une notification symétrique à TwoFactorEnabledMail.
 */
class TwoFactorDisabledMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public ?string $ipAddress = null,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'YNOV — Authentification à deux facteurs désactivée',
        );
    }

    public function content(): Content
    {
        $details = $this->user->details;

        return new Content(
            view: 'emails.api.ynov.two-factor-disabled',
            with: [
                'fullName' => $details ? trim($details->prenoms . ' ' . $details->nom) : $this->user->login,
                'disabledAt' => now()->format('d/m/Y à H:i'),
                'ipAddress' => $this->ipAddress ?? '—',
                'securityUrl' => config('app.frontend_url') . '/profile/security',
            ],
        );
    }
}