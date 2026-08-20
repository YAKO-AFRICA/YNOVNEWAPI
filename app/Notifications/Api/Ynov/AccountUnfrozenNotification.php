<?php
// app/Notifications/Api/Ynov/AccountUnfrozenNotification.php

namespace App\Notifications\Api\Ynov;

use App\Models\Api\Ynov\parameter\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class AccountUnfrozenNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $reason,
        public ?User $admin = null
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $adminName = $this->admin?->details?->full_name ?? 'Système';
        
        return (new MailMessage)
            ->subject('✅ YNOV Sécurité — Compte dégelé')
            ->greeting('Bonjour ' . ($notifiable->details?->prenoms ?? 'Utilisateur'))
            ->line('Votre compte a été dégelé.')
            ->line("**Motif :** {$this->reason}")
            ->line("**Par :** {$adminName}")
            ->line('Vous pouvez maintenant vous reconnecter à votre compte.')
            ->salutation('L\'équipe YNOV — YAKO AFRICA');
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title' => 'Compte dégelé',
            'reason' => $this->reason,
            'admin' => $this->admin?->details?->full_name ?? 'Système',
            'message' => "Votre compte a été dégelé. Motif: {$this->reason}",
            'unfrozen_at' => now()->toDateTimeString(),
        ];
    }
}