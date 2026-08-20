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
 * Email envoyé après plusieurs tentatives de connexion échouées consécutives,
 * même si le compte n'est pas encore gelé — sensibilisation sécurité proactive.
 */
class LoginFailedAlertMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public int $attemptsCount,
        public ?string $ipAddress = null,
        public ?string $location = null,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'YNOV — Plusieurs tentatives de connexion échouées',
        );
    }

    public function content(): Content
    {
        $details = $this->user->details;

        return new Content(
            view: 'emails.api.ynov.login-failed-alert',
            with: [
                'fullName' => $details ? trim($details->prenoms . ' ' . $details->nom) : $this->user->login,
                'attemptsCount' => $this->attemptsCount,
                'ipAddress' => $this->ipAddress ?? '—',
                'location' => $this->location ?? 'Non déterminée',
                'attemptedAt' => now()->format('d/m/Y à H:i'),
                'securityUrl' => config('app.frontend_url') . '/profile/security',
            ],
        );
    }
}