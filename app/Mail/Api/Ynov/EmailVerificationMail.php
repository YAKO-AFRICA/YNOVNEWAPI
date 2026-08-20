<?php
// app/Mail/Api/Ynov/EmailVerificationMail.php
namespace App\Mail\Api\Ynov;

use App\Models\Api\Ynov\parameter\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmailVerificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $token,
        public int $expiresInHours = 24,
    ) {
    }
    

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'YNOV — Vérifiez votre adresse email',
        );
    }

    public function content(): Content
    {
        $details = $this->user->details;

        return new Content(
            view: 'emails.api.ynov.email-verification',
            with: [
                'fullName' => $details ? trim($details->prenoms . ' ' . $details->nom) : $this->user->login,
                'expiresInHours' => $this->expiresInHours,
                'verificationUrl' => config('app.frontend_url') . '/verify-email?token=' . $this->token . '&email=' . urlencode($this->user->email),
            ],
        );
    }
}
