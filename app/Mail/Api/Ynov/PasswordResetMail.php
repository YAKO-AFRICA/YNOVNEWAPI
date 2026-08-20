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
 * Email envoyé lors d'une demande de réinitialisation de mot de passe
 * (POST /auth/forgot-password). Contient le lien contenant le token en clair.
 */
class PasswordResetMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $token,
        public int $expiresInMinutes = 60,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'YNOV — Réinitialisation de votre mot de passe',
        );
    }

    public function content(): Content
    {
        $details = $this->user->details;

        return new Content(
            view: 'emails.api.ynov.password-reset',
            with: [
                'fullName' => $details ? trim($details->prenoms . ' ' . $details->nom) : $this->user->login,
                'expiresInMinutes' => $this->expiresInMinutes,
                'resetUrl' => config('app.frontend_url') . '/reset-password.html?token=' . $this->token . '&email=' . urlencode($this->user->email),
                // 'resetUrl' => config('app.frontend_url') . '/reset-password?token=' . $this->token . '&email=' . urlencode($this->user->email),
            ],
        );
    }
}