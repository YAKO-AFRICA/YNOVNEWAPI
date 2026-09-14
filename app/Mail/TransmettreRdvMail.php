<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TransmettreRdvMail extends Mailable
{
    use Queueable, SerializesModels;

    public $gestionnaireEmail;
    public $fichierPath;
    public $fichierNom;
    public $sujet;
    public $message;

    public function __construct(string $gestionnaireEmail, string $fichierPath, string $fichierNom, string $sujet, string $message)
    {
        $this->gestionnaireEmail = $gestionnaireEmail;
        $this->fichierPath = $fichierPath;
        $this->fichierNom = $fichierNom;
        $this->sujet = $sujet;
        $this->message = $message;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->sujet,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.api.ynov.transmettre_rdv',
            text: 'emails.api.ynov.transmettre_rdv_plain',
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromPath($this->fichierPath)
                ->as($this->fichierNom)
                ->withMime('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
        ];
    }
}
