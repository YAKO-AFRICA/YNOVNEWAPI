<?php
namespace App\Notifications\Api\Ynov;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class AccountFrozenNotification extends Notification
{
    use Queueable;

    public function __construct(
        public int $level,
        public int $duration
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('🔒 YNOV Sécurité — Compte temporairement gelé')
            ->greeting('Bonjour ' . ($notifiable->details?->prenoms ?? 'Utilisateur'))
            ->line('Votre compte a été temporairement gelé après plusieurs tentatives de connexion échouées.')
            ->line("Niveau de gel : {$this->level} — Durée : {$this->duration} secondes.")
            ->line('Si vous n\'êtes pas à l\'origine de ces tentatives, contactez immédiatement l\'administrateur.')
            ->salutation('L\'équipe YNOV — YAKO AFRICA');
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => 'Compte temporairement gelé',
            'level' => $this->level,
            'duration' => $this->duration,
            'message' => "Gel niveau {$this->level} pendant {$this->duration}s",
        ];
    }
}