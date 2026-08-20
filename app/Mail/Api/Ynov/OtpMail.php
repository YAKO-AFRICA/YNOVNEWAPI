<?php

namespace App\Mail\Api\Ynov;

use App\Models\Api\Ynov\parameter\UserDetails;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Email envoyé lors de l'envoi d'un code OTP (SMS/Email)
 * pour la double authentification ou la connexion.
 */
class OtpMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public ?UserDetails $userDetails,
        public string $purpose,
        public string $code,
        public int $expiresInMinutes = 10,
    ) {}

    public function envelope(): Envelope
    {
        $subject = match ($this->purpose) {
            'login' => '🔐 YNOV — Code de connexion OTP',
            '2fa' => '🔐 YNOV — Code de vérification 2FA',
            'reset' => '🔐 YNOV — Code de réinitialisation',
            default => '🔐 YNOV — Code de vérification OTP',
        };

        return new Envelope(
            subject: $subject,
        );
    }

    public function content(): Content
    {
        $name = $this->userDetails?->prenoms 
            ? $this->userDetails->prenoms . ' ' . ($this->userDetails->nom ?? '')
            : ($this->userDetails?->nom ?? 'Utilisateur');

        $purposeLabel = match ($this->purpose) {
            'login' => 'connexion à votre compte',
            '2fa' => 'vérification de votre identité (double authentification)',
            'reset' => 'réinitialisation de votre mot de passe',
            default => 'vérification de votre identité',
        };

        return new Content(
            view: 'emails.api.ynov.otp',
            with: [
                'name' => trim($name) ?: 'Utilisateur',
                'purpose' => $this->purpose,
                'purposeLabel' => $purposeLabel,
                'code' => $this->code,
                'expiresInMinutes' => $this->expiresInMinutes,
            ]
        );
    }
}