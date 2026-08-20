<?php
// app/Mail/Api/Ynov/AccountFreezeWarningMail.php

namespace App\Mail\Api\Ynov;

use App\Models\Api\Ynov\parameter\UserDetails;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountFreezeWarningMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ?UserDetails $userDetails,  // ✅ UserDetails, pas User
        public int $level,
        public int $attemptCount,
        public int $remainingAttempts
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '⚠️ YNOV Sécurité — Avertissement de gel imminent',
        );
    }

    public function content(): Content
    {
        $name = $this->userDetails?->prenoms 
            ? $this->userDetails->prenoms . ' ' . ($this->userDetails->nom ?? '')
            : ($this->userDetails?->nom ?? 'Utilisateur');

        return new Content(
            view: 'emails.api.ynov.account-freeze-warning',
            with: [
                'name' => trim($name) ?: 'Utilisateur',
                'level' => $this->level,
                'attemptCount' => $this->attemptCount,
                'remainingAttempts' => $this->remainingAttempts,
                'securityUrl' => config('app.frontend_url') . '/profile/security',
            ]
        );
    }
}