<?php
namespace App\Notifications\Api\Ynov;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class PasswordChangedNotification extends Notification
{
    use Queueable;

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('🔑 YNOV — Modification de mot de passe')
            ->greeting('Bonjour ' . ($notifiable->details?->prenoms ?? 'Utilisateur'))
            ->line('Votre mot de passe a été modifié avec succès.')
            ->line('Si vous n\'êtes pas à l\'origine de cette modification, contactez immédiatement l\'administrateur.')
            ->salutation('L\'équipe YNOV — YAKO AFRICA');
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => 'Mot de passe modifié',
            'message' => 'Votre mot de passe a été changé.',
        ];
    }
}