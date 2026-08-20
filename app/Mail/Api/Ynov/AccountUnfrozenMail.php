<?php
// app/Mail/Api/Ynov/AccountUnfrozenMail.php

namespace App\Mail\Api\Ynov;

use App\Models\Api\Ynov\parameter\UserDetails;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountUnfrozenMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ?UserDetails $userDetails,  // ✅ UserDetails, pas User
        public string $reason,
        public string $adminName
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '✅ YNOV Sécurité — Compte dégelé',
        );
    }

    public function content(): Content
    {
        $name = $this->userDetails?->prenoms 
            ? $this->userDetails->prenoms . ' ' . ($this->userDetails->nom ?? '')
            : ($this->userDetails?->nom ?? 'Utilisateur');

        return new Content(
            view: 'emails.api.ynov.account-unfrozen',
            with: [
                'name' => trim($name) ?: 'Utilisateur',
                'reason' => $this->reason,
                'adminName' => $this->adminName,
            ]
        );
    }
}