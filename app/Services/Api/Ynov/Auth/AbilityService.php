<?php

namespace App\Services\Api\Ynov\Auth;

use App\Models\Api\Ynov\parameter\User;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Service centralisé pour la gestion des abilities (scopes) de Sanctum
 */
class AbilityService
{
    /**
     * Vérifier si un token a une ability spécifique
     */
    public function tokenHasAbility(PersonalAccessToken $token, string $ability): bool
    {
        $abilities = $token->abilities ?? [];
        return in_array($ability, $abilities) || in_array('*', $abilities);
    }

    /**
     * Vérifier si un token a toutes les abilities
     */
    public function tokenHasAllAbilities(PersonalAccessToken $token, array $abilities): bool
    {
        $tokenAbilities = $token->abilities ?? [];
        
        if (in_array('*', $tokenAbilities)) {
            return true;
        }

        foreach ($abilities as $ability) {
            if (!in_array($ability, $tokenAbilities)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Vérifier si un token a au moins une des abilities
     */
    public function tokenHasAnyAbility(PersonalAccessToken $token, array $abilities): bool
    {
        $tokenAbilities = $token->abilities ?? [];
        
        if (in_array('*', $tokenAbilities)) {
            return true;
        }

        foreach ($abilities as $ability) {
            if (in_array($ability, $tokenAbilities)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Créer un token avec des abilities spécifiques
     */
    public function createToken(User $user, string $name, array $abilities, ?int $expiresAt = null): PersonalAccessToken
    {
        $expiresAt = $expiresAt ?? now()->addHours(24);
        return $user->createToken($name, $abilities, $expiresAt);
    }

    /**
     * Créer un token complet (toutes les abilities)
     */
    public function createFullToken(User $user, string $name, ?int $expiresAt = null): PersonalAccessToken
    {
        $expiresAt = $expiresAt ?? now()->addHours(24);
        return $user->createToken($name, ['*'], $expiresAt);
    }

    /**
     * Créer un token temporaire pour la 2FA
     */
    public function createTwoFactorToken(User $user, int $minutes = 5): PersonalAccessToken
    {
        return $this->createToken(
            $user,
            '2fa-auth',
            ['2fa-verify'],
            now()->addMinutes($minutes)
        );
    }

    /**
     * Créer un token temporaire pour le changement de mot de passe
     */
    public function createPasswordChangeToken(User $user, int $hours = 1): PersonalAccessToken
    {
        return $this->createToken(
            $user,
            'password-change',
            ['password-change'],
            now()->addHours($hours)
        );
    }

    /**
     * Créer un token de vérification d'email
     */
    public function createEmailVerificationToken(User $user, int $hours = 24): PersonalAccessToken
    {
        return $this->createToken(
            $user,
            'email-verification',
            ['email-verify'],
            now()->addHours($hours)
        );
    }

    /**
     * Révéquer tous les tokens d'un utilisateur
     */
    public function revokeAllTokens(User $user): void
    {
        $user->tokens()->delete();
    }

    /**
     * Révéquer un token spécifique
     */
    public function revokeToken(PersonalAccessToken $token): void
    {
        $token->delete();
    }

    /**
     * Révéquer tous les tokens sauf un
     */
    public function revokeAllTokensExcept(User $user, PersonalAccessToken $exceptToken): void
    {
        $user->tokens()
            ->where('id', '!=', $exceptToken->id)
            ->delete();
    }

    /**
     * Obtenir les abilities d'un token
     */
    public function getTokenAbilities(PersonalAccessToken $token): array
    {
        return $token->abilities ?? [];
    }

    /**
     * Vérifier si un token est expiré
     */
    public function isTokenExpired(PersonalAccessToken $token): bool
    {
        return $token->expires_at && $token->expires_at->isPast();
    }

    /**
     * Vérifier si un token est valide (non expiré)
     */
    public function isTokenValid(PersonalAccessToken $token): bool
    {
        return !$this->isTokenExpired($token);
    }
}