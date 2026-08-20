<?php

namespace App\Services\Api\Ynov\Auth;

use App\Models\Api\Ynov\parameter\User;
use Illuminate\Cache\RateLimiter;

/**
 * Service centralisé pour la gestion du rate limiting
 * Utilisé pour OTP, 2FA, et autres endpoints sensibles
 */
class ThrottleService
{
    public function __construct(
        private RateLimiter $rateLimiter
    ) {}

    /**
     * Vérifier si une tentative est autorisée
     */
    public function attempt(string $key, int $maxAttempts = 5, int $decaySeconds = 60): bool
    {
        return !$this->rateLimiter->tooManyAttempts($key, $maxAttempts);
    }

    /**
     * Incrémenter le compteur de tentatives
     */
    public function increment(string $key): void
    {
        $this->rateLimiter->hit($key);
    }

    /**
     * Obtenir le nombre de tentatives restantes
     */
    public function remainingAttempts(string $key, int $maxAttempts = 5): int
    {
        return $this->rateLimiter->remaining($key, $maxAttempts);
    }

    /**
     * Obtenir le temps restant avant réinitialisation
     */
    public function availableIn(string $key): int
    {
        return $this->rateLimiter->availableIn($key);
    }

    /**
     * Réinitialiser le compteur
     */
    public function clear(string $key): void
    {
        $this->rateLimiter->clear($key);
    }

    /**
     * Générer une clé pour un utilisateur et une action
     */
    public function key(User $user, string $action): string
    {
        return "throttle:{$action}:{$user->uuid_user}";
    }

    /**
     * Générer une clé pour une IP et une action
     */
    public function keyForIp(string $ip, string $action): string
    {
        return "throttle:{$action}:{$ip}";
    }

    /**
     * Vérifier et incrémenter en une seule opération
     * 
     * @throws \RuntimeException si le taux est dépassé
     */
    public function checkAndIncrement(string $key, int $maxAttempts = 5, int $decaySeconds = 60): void
    {
        if ($this->rateLimiter->tooManyAttempts($key, $maxAttempts)) {
            $availableIn = $this->rateLimiter->availableIn($key);
            throw new \RuntimeException(
                "Trop de tentatives. Réessayez dans {$availableIn} secondes.",
                429
            );
        }

        $this->rateLimiter->hit($key, $decaySeconds);
    }
}