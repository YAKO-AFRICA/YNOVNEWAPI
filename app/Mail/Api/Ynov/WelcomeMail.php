<?php

namespace App\Mail\Api\Ynov;

use App\Models\Api\Ynov\parameter\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public User $user, public string $password)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Bienvenue sur YNOV — Votre compte a été créé',
        );
    }

    public function content(): Content
    {
        $details = $this->user->details;

        return new Content(
            view: 'emails.api.ynov.welcome',
            with: [
                'fullName' => $details ? trim($details->prenoms . ' ' . $details->nom) : $this->user->login,
                'login' => $this->user->login,
                'email' => $this->user->email,
                'password' => $this->password,
                // 'roleLabel' => $this->user->role?->libelle ?? 'Non défini',
                'loginUrl' => config('app.frontend_url') . '/',
                // 'loginUrl' => config('app.frontend_url') . '/login',
            ],
        );
    }
}
