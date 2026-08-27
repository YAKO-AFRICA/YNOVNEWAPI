<?php
// app/Services/Api/Ynov/Auth/FreezeService.php

namespace App\Services\Api\Ynov\Auth;

use App\Mail\Api\Ynov\AccountFreezeWarningMail;
use App\Mail\Api\Ynov\AccountFrozenMail;
use App\Mail\Api\Ynov\AccountUnfrozenMail;
use App\Models\Api\Ynov\parameter\AccountFreeze;
use App\Models\Api\Ynov\parameter\ActivityLog;
use App\Models\Api\Ynov\parameter\User;

use App\Services\Api\Ynov\UserService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

class FreezeService
{
    private const LEVELS = [
        1 => ['attempts' => 3, 'duration' => 10, 'label' => 'Léger'],
        2 => ['attempts' => 4, 'duration' => 60, 'label' => 'Modéré'],
        3 => ['attempts' => 5, 'duration' => 180, 'label' => 'Sévère'],
    ];

    // Durées autorisées pour un gel manuel administrateur (en secondes)
    // Évite qu'un admin gèle un compte pour une durée absurde (1s ou 10 ans) par erreur
    private const MANUAL_MIN_DURATION = 10;      // 10 secondes minimum
    private const MANUAL_MAX_DURATION = 86400;   // 24h maximum

    public function __construct(
        private UserService $userService,
    ) {}
    

    /**
     * Gérer une tentative échouée
     */
    public function handleFailedAttempt(User $user): void
    {
        try {
            $user->increment('failed_login_count');
            $count = $user->fresh()->failed_login_count;

            foreach (array_reverse(self::LEVELS, true) as $level => $config) {
                if ($count >= $config['attempts']) {
                    $this->applyFreeze($user, $level, $count, $config);
                    break;
                }
            }

            if ($count >= 6) {
                $this->applyBlockUser($user);
            }
        } catch (\Exception $e) {
            Log::error('Erreur handleFailedAttempt: ' . $e->getMessage());
        }
    }

    /**
     * Appliquer le gel automatique
     */
    private function applyFreeze(User $user, int $level, int $count, array $config): void
    {
        $duration = $config['duration'];
        $unfrozenAt = now()->addSeconds($duration);

        DB::transaction(function () use ($user, $level, $count, $unfrozenAt) {
            $user->update([
                'freeze_level' => $level,
                'frozen_until' => $unfrozenAt,
                'status' => 'gele',
            ]);

            try {
                AccountFreeze::create([
                    'user_uuid' => $user->uuid_user,
                    'freeze_level' => $level,
                    'failed_attempts_count' => $count,
                    'frozen_at' => now(),
                    'unfrozen_at' => null,
                    'unfrozen_by' => null,
                ]);
            } catch (\Exception $e) {
                Log::warning('Erreur création AccountFreeze: ' . $e->getMessage());
            }

            ActivityLog::log([
                'user_uuid' => $user->uuid_user,
                'action' => 'freeze',
                'success' => true,
                'action_type' => 'auth',
                'module' => 'auth',
                'resource_id' => $user->uuid_user,
                'description' => "Gel automatique niveau {$level} suite à {$count} tentatives échouées.",
                'level' => 'warning',
            ]);
        });

        $this->sendFreezeNotifications($user, $level, $count, $duration);
    }

    /**
     * Bloquer un utilisateur si le nombre de tentatives échouées est >= 6
     */
    private function applyBlockUser(User $user): bool
    {
        try {
            $this->userService->block($user, "tentatives de connexion echouées >= 6", $user->uuid_user);
            ActivityLog::log([
                'user_uuid' => $user->uuid_user,
                'action' => 'login',
                'success' => false,
                'action_type' => 'auth',
                'module' => 'auth',
                'resource_id' => $user->uuid_user,
                'description' => "Bloquage de l'utilisateur : {$user->email} (pour tentatives de connexion echouées >= 6)",
                'level' => 'warning',
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Erreur blockUser: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Gel manuel par un administrateur
     *
     * @throws \InvalidArgumentException si la durée est hors bornes autorisées
     */
    public function manualFreeze(User $user, User $admin, int $duration, string $reason): bool
    {
        // Validation stricte de la durée (garde-fou anti-erreur de saisie)
        if ($duration < self::MANUAL_MIN_DURATION || $duration > self::MANUAL_MAX_DURATION) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Durée invalide : doit être comprise entre %d et %d secondes.',
                    self::MANUAL_MIN_DURATION,
                    self::MANUAL_MAX_DURATION
                )
            );
        }

        if (trim($reason) === '') {
            throw new \InvalidArgumentException('Le motif du gel est obligatoire.');
        }

        try {
            // Utiliser la méthode centralisée du modèle plutôt que dupliquer la logique
            if (!$user->canBeFrozenManually()) {
                Log::warning("Tentative de gel manuel refusée pour {$user->email} (statut: {$user->status})");
                return false;
            }

            $unfrozenAt = now()->addSeconds($duration);

            DB::transaction(function () use ($user, $admin, $unfrozenAt, $duration, $reason) {
                $user->update([
                    'freeze_level' => 4, // Niveau spécial : gel manuel
                    'frozen_until' => $unfrozenAt,
                    'status' => 'gele',
                    'failed_login_count' => 0,
                ]);

                AccountFreeze::create([
                    'user_uuid' => $user->uuid_user,
                    'freeze_level' => 4,
                    'failed_attempts_count' => 0,
                    'frozen_at' => now(),
                    'unfrozen_at' => null,
                    'unfrozen_by' => null,
                    'metadata' => [
                        'manual' => true,
                        'admin_uuid' => $admin->uuid_user,
                        'reason' => $reason,
                        'duration' => $duration,
                    ],
                ]);

                ActivityLog::log([
                    'user_uuid' => $user->uuid_user,
                    'action' => 'freeze',
                    'success' => true,
                    'action_type' => 'auth',
                    'module' => 'auth',
                    'resource_id' => $user->uuid_user,
                    'description' => "Gel manuel par {$admin->email} - Durée: {$duration}s - Motif: {$reason}",
                    'level' => 'warning',
                ]);
            });

            $this->sendManualFreezeNotifications($user, $admin, $duration, $reason);

            Log::info("Gel manuel appliqué à {$user->email} par {$admin->email} - Durée: {$duration}s - Motif: {$reason}");

            return true;
        } catch (\InvalidArgumentException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Erreur manualFreeze: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Envoyer les notifications de gel manuel
     */
    private function sendManualFreezeNotifications(User $user, User $admin, int $duration, string $reason): void
    {
        try {

            // Notification::send($user, new AccountManualFrozenNotification(
            //     $duration,
            //     $reason,
            //     $admin
            // ));

            if ($user->email){

                Mail::to($user->email)->queue(new AccountFrozenMail(
                    $user->details,
                    4,
                    $duration,
                    $reason,
                    $admin->details?->full_name ?? $admin->email
                ));
            }
        } catch (\Exception $e) {
            Log::error('Erreur envoi notifications gel manuel: ' . $e->getMessage());
        }
    }

    /**
     * Envoyer les notifications de gel automatique
     */
    private function sendFreezeNotifications(User $user, int $level, int $count, int $duration): void
    {
        try {
            // Notification::send($user, new AccountFrozenNotification($level, $duration));
            if ($user->email){
                if ($level === 3) {
                    Mail::to($user->email)->queue(new AccountFrozenMail(
                        $user->details,
                        $level,
                        $duration
                    ));
                } else {
                    $nextLevelConfig = self::LEVELS[$level + 1] ?? null;
                    $remaining = $nextLevelConfig ? max(0, $nextLevelConfig['attempts'] - $count) : 0;

                    Mail::to($user->email)->queue(new AccountFreezeWarningMail(
                        $user->details,
                        $level,
                        $count,
                        $remaining,
                    ));
                }
            }
        } catch (\Exception $e) {
            Log::error('Erreur envoi notifications freeze: ' . $e->getMessage());
        }
    }

    /**
     * Vérifier si l'utilisateur est gelé (et dégeler automatiquement si expiré)
     */
    public function isFrozen(User $user): bool
    {
        if (!$user->frozen_until) {
            return false;
        }

        if (now()->lt($user->frozen_until)) {
            return true;
        }

        $this->autoUnfreeze($user);

        return false;
    }

    /**
     * Dégeler automatiquement après expiration
     */
    private function autoUnfreeze(User $user): void
    {
        Log::info("Dégelage automatique de {$user->email}");
        // $this->unfreeze($user, null, 'Expiration automatique');
        $reason = 'Expiration automatique';
        $admin = null;
        try {
            DB::transaction(function () use ($user) {
                $user->update([
                    // 'freeze_level' => ,
                    'frozen_until' => null,
                    'status' => 'actif',
                    // 'failed_login_count' => 0,
                ]);

                AccountFreeze::where('user_uuid', $user->uuid_user)
                    ->whereNull('unfrozen_at')
                    ->update([
                        'unfrozen_at' => now(),
                        'unfrozen_by' => 'system',
                    ]);

                ActivityLog::log([
                    'user_uuid' => $user->uuid_user,
                    'action' => 'unfreeze',
                    'success' => true,
                    'action_type' => 'auth',
                    'module' => 'auth',
                    'resource_id' => $user->uuid_user,
                    'description' => "Dégel automatique - Expiration automatique",
                    'level' => 'info',
                ]);
            });

            $this->sendUnfreezeNotifications($user, $admin, $reason);

            Log::info("Utilisateur dégelé automatiquement: {$user->email} - {$reason}");

        } catch (\Exception $e) {
            Log::error('Erreur unfreeze: ' . $e->getMessage());
        }
    }

    /**
     * Dégeler manuellement un utilisateur
     */
    public function manualUnfreeze(User $user, User $admin, string $reason = null): bool
    {
        if (!$user->canBeUnfrozenManually()) {
            return false;
        }

        return $this->unfreeze($user, $admin, $reason ?? 'Dégel manuel par administrateur');
    }

    /**
     * Dégeler un utilisateur
     */
    private function unfreeze(User $user, ?User $admin, string $reason): bool
    {
        try {
            DB::transaction(function () use ($user, $admin, $reason) {
                $user->update([
                    'freeze_level' => 0,
                    'frozen_until' => null,
                    'status' => 'actif',
                    'failed_login_count' => 0,
                ]);

                AccountFreeze::where('user_uuid', $user->uuid_user)
                    ->whereNull('unfrozen_at')
                    ->update([
                        'unfrozen_at' => now(),
                        'unfrozen_by' => $admin?->uuid_user,
                    ]);

                ActivityLog::log([
                    'user_uuid' => $user->uuid_user,
                    'action' => 'unfreeze',
                    'success' => true,
                    'action_type' => 'auth',
                    'module' => 'auth',
                    'resource_id' => $user->uuid_user,
                    'description' => $admin
                        ? "Dégel manuel par {$admin->email} - Motif: {$reason}"
                        : "Dégel automatique - {$reason}",
                    'level' => 'info',
                ]);
            });

            $this->sendUnfreezeNotifications($user, $admin, $reason);

            Log::info("Utilisateur dégelé: {$user->email} - {$reason}");

            return true;
        } catch (\Exception $e) {
            Log::error('Erreur unfreeze: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Envoyer les notifications de dégel
     */
    private function sendUnfreezeNotifications(User $user, ?User $admin, string $reason): void
    {
        try {
            // Notification::send($user, new AccountUnfrozenNotification($reason, $admin));

            if ($user->email){
                Mail::to($user->email)->queue(new AccountUnfrozenMail(
                    $user->details,
                    $reason,
                    $admin?->details?->full_name ?? 'Système'
                ));
            }
        } catch (\Exception $e) {
            Log::error('Erreur envoi notifications dégel: ' . $e->getMessage());
        }
    }

    /**
     * Réinitialiser les tentatives
     */
    public function resetAttempts(User $user): void
    {
        try {
            $user->update([
                'failed_login_count' => 0,
                'freeze_level' => 0,
                'frozen_until' => null,
            ]);

            AccountFreeze::where('user_uuid', $user->uuid_user)->delete();
        } catch (\Exception $e) {
            Log::error('Erreur resetAttempts: ' . $e->getMessage());
        }
    }

    /**
     * Obtenir le niveau de gel actuel avec tous les détails utiles au front
     */
    public function getCurrentLevel(User $user): array
    {
        if (!$this->isFrozen($user)) {
            return [
                'level' => 0,
                'label' => 'Aucun',
                'remaining_seconds' => 0,
                'remaining_formatted' => 'Dégelé',
                'is_frozen' => false,
                'is_manual' => false,
                'can_be_frozen' => $user->canBeFrozenManually(),
                'can_be_unfrozen' => false,
            ];
        }

        $level = $user->freeze_level;
        $config = self::LEVELS[$level] ?? ($level === 4 ? ['label' => 'Manuel (Administrateur)'] : null);

        return [
            'level' => $level,
            'label' => $config['label'] ?? 'Inconnu',
            'remaining_seconds' => $user->getFrozenRemainingSeconds(),
            'remaining_formatted' => $user->getFrozenRemainingFormatted(),
            'is_frozen' => true,
            'is_manual' => $level === 4,
            'can_be_frozen' => false,
            'can_be_unfrozen' => $user->canBeUnfrozenManually(),
            'unfrozen_at' => $user->frozen_until?->toDateTimeString(),
        ];
    }
}