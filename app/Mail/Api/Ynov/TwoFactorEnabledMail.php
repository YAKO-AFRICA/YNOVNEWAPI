<?php

namespace App\Mail\Api\Ynov;

use App\Models\Api\Ynov\parameter\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TwoFactorEnabledMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param array<int, string> $recoveryCodes Codes en clair — à usage unique, ne jamais logger ce Mailable.
     */
    public function __construct(
        public User $user,
        public array $recoveryCodes,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'YNOV — Authentification à deux facteurs activée',
        );
    }

    public function content(): Content
    {
        $details = $this->user->details;

        return new Content(
            view: 'emails.api.ynov.two-factor-enabled',
            with: [
                'fullName' => $details ? trim($details->prenoms . ' ' . $details->nom) : $this->user->login,
                'recoveryCodes' => $this->recoveryCodes,
            ],
        );
    }
}

