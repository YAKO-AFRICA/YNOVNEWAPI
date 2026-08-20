<?php
// app/Mail/Api/Ynov/AccountFrozenMail.php

namespace App\Mail\Api\Ynov;

use App\Models\Api\Ynov\parameter\UserDetails;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountFrozenMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ?UserDetails $userDetails,  // ✅ UserDetails, pas User
        public int $level,
        public int $duration,
        public ?string $reason = null,
        public ?string $adminName = null
    ) {}

    public function envelope(): Envelope
    {
        $subject = $this->adminName 
            ? '🔒 YNOV Sécurité — Compte gelé par un administrateur'
            : '🔒 YNOV Sécurité — Compte temporairement gelé';

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        $name = $this->userDetails?->prenoms 
            ? $this->userDetails->prenoms . ' ' . ($this->userDetails->nom ?? '')
            : ($this->userDetails?->nom ?? 'Utilisateur');

        $isManual = $this->level === 4;
        $durationMinutes = round($this->duration / 60, 1);

        return new Content(
            view: 'emails.api.ynov.account-frozen',
            with: [
                'name' => trim($name) ?: 'Utilisateur',
                'level' => $this->level,
                'duration' => $this->duration,
                'durationMinutes' => $durationMinutes,
                'isManual' => $isManual,
                'reason' => $this->reason,
                'adminName' => $this->adminName,
            ]
        );
    }
}