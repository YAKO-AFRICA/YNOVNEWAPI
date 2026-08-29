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
 * Email envoyé quand une session (token Sanctum) autre que la session
 * courante est révoquée manuellement — utile si ce n'est pas l'utilisateur
 * qui est à l'origine de l'action depuis cet appareil.
 */
class SessionRevokedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $sessionName,
        public bool $allSessions = false,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->allSessions
                ? 'YNOV — Déconnexion de tous vos appareils'
                : 'YNOV — Une session a été révoquée',
        );
    }

    public function content(): Content
    {
        $details = $this->user->details;

        return new Content(
            view: 'emails.api.ynov.session-revoked',
            with: [
                'fullName' => $details ? trim($details->prenoms . ' ' . $details->nom) : $this->user->login,
                'sessionName' => $this->sessionName,
                'allSessions' => $this->allSessions,
                'revokedAt' => now()->format('d/m/Y à H:i'),
                'securityUrl' => config('app.frontend_url'),
            ],
        );
    }
}