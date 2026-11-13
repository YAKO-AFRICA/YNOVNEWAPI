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
 * Email envoyé lorsqu'un administrateur réinitialise le mot de passe
 * d'un utilisateur (POST /users/{uuid}/reset-password).
 * Contient le mot de passe temporaire généré.
 */
class PasswordResetCredentialsMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public User $user, public string $password)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'YNOV — Votre mot de passe a été réinitialisé',
        );
    }

    public function content(): Content
    {
        $details = $this->user->details;

        return new Content(
            view: 'emails.api.ynov.password-reset-credentials',
            with: [
                'fullName' => $details ? trim($details->prenoms . ' ' . $details->nom) : $this->user->login,
                'login' => $this->user->login,
                'password' => $this->password,
                'loginUrl' => config('app.frontend_url') . '/',
            ],
        );
    }
}
