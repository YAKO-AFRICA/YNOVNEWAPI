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
 * Email envoyé lorsqu'un administrateur débloque un compte utilisateur.
 */
class AccountUnblockedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;


    public function __construct(
        public User $user,
        public ?string $unblockedByName = null,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'YNOV — Votre compte a été débloqué',
        );
    }

    public function content(): Content
    {
        $details = $this->user->details;

        return new Content(
            view: 'emails.api.ynov.account-unblocked',
            with: [
                'fullName' => $details ? trim($details->prenoms . ' ' . $details->nom) : $this->user->login,
                'unblockedAt' => now()->format('d/m/Y à H:i'),
                'unblockedBy' => $this->unblockedByName ?? 'Administrateur',
                'loginUrl' => config('app.frontend_url') . '/login',
            ],
        );
    }
}