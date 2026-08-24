<?php
// app/Mail/Api/Ynov/TwoFactorRecoveryCodesMail.php

namespace App\Mail\Api\Ynov;

use App\Models\Api\Ynov\parameter\UserDetails;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TwoFactorRecoveryCodesMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public ?UserDetails $userDetails,
        public array $recoveryCodes,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'YNOV — Codes de récupération 2FA',
        );
    }

    public function content(): Content
    {
        $name = $this->userDetails?->prenoms 
            ? $this->userDetails->prenoms . ' ' . ($this->userDetails->nom ?? '')
            : ($this->userDetails?->nom ?? 'Utilisateur');

        return new Content(
            view: 'emails.api.ynov.two-factor-recovery-codes',
            with: [
                'name' => trim($name) ?: 'Utilisateur',
                'recoveryCodes' => $this->recoveryCodes,
                'count' => count($this->recoveryCodes),
            ]
        );
    }
}