<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CustomerMail extends Mailable
{
    use Queueable, SerializesModels;

    public $mailData;
    public $emailSubject;

    public function __construct(array $mailData, string $emailSubject)
    {
        $this->mailData = $mailData;
        $this->emailSubject = $emailSubject;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->emailSubject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.sendId',
            with: [
                'messageText' => $this->mailData['messages'] ?? '',
                'documentUrl' => $this->mailData['documents'] ?? '',
            ]
        );
    }

    public function attachments(): array
    {
        $attachment = $this->mailData['attachment'] ?? null;

        if (!$attachment) {
            return [];
        }

        return [
            Attachment::fromStorageDisk($attachment['disk'], $attachment['path'])
                ->as($attachment['name'])
                ->withMime($attachment['mime'] ?? 'application/octet-stream'),
        ];
    }
}
