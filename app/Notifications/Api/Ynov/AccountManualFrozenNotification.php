<?php
// app/Notifications/Api/Ynov/AccountManualFrozenNotification.php

namespace App\Notifications\Api\Ynov;

use App\Models\Api\Ynov\parameter\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class AccountManualFrozenNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $duration,
        public string $reason,
        public ?User $admin = null
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $adminName = $this->admin?->details?->full_name ?? $this->admin?->email ?? 'Administrateur';
        $durationMinutes = round($this->duration / 60, 1);

        return (new MailMessage)
            ->subject('🔒 YNOV Sécurité — Compte gelé par un administrateur')
            ->greeting('Bonjour ' . ($notifiable->details?->prenoms ?? 'Utilisateur'))
            ->line('Votre compte a été **gelé** par un administrateur.')
            ->line("**Motif :** {$this->reason}")
            ->line("**Par :** {$adminName}")
            ->line("**Durée :** {$this->duration} secondes (" . $durationMinutes . " minutes)")
            ->line('Vous ne pourrez pas vous connecter pendant cette période.')
            ->line('Si vous avez des questions, veuillez contacter votre administrateur.')
            ->salutation('L\'équipe YNOV — YAKO AFRICA');
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title' => 'Compte gelé par un administrateur',
            'duration' => $this->duration,
            'reason' => $this->reason,
            'admin' => $this->admin?->details?->full_name ?? $this->admin?->email ?? 'Administrateur',
            'message' => "Votre compte a été gelé. Motif: {$this->reason}",
            'frozen_at' => now()->toDateTimeString(),
            'unfrozen_at' => now()->addSeconds($this->duration)->toDateTimeString(),
        ];
    }
}