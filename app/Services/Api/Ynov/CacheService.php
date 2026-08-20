<?php

namespace App\Services\Api\Ynov;

use Illuminate\Support\Facades\Cache;
use App\Models\Api\Ynov\parameter\User;
use App\Models\Api\Ynov\parameter\Role;

/**
 * Service centralisé pour la gestion du cache
 * Permet d'invalider facilement tous les caches liés aux utilisateurs et rôles
 */
class CacheService
{
    private const PERMISSION_PREFIX = 'user_permissions_';
    private const PERMISSION_TTL = 3600; // 1 heure
    private const USER_PREFIX = 'user_data_';
    private const USER_TTL = 3600; // 1 heure
    private const ROLE_PREFIX = 'role_data_';
    private const ROLE_TTL = 3600; // 1 heure

    /**
     * Générer la clé de cache pour les permissions d'un utilisateur
     */
    public function getPermissionKey(User $user): string
    {
        return self::PERMISSION_PREFIX . $user->uuid_user;
    }

    /**
     * Générer la clé de cache pour les données d'un utilisateur
     */
    public function getUserKey(User $user): string
    {
        return self::USER_PREFIX . $user->uuid_user;
    }

    /**
     * Générer la clé de cache pour les données d'un rôle
     */
    public function getRoleKey(Role $role): string
    {
        return self::ROLE_PREFIX . $role->uuid_role;
    }

    /**
     * Récupérer les permissions d'un utilisateur depuis le cache
     * Si non présent, exécuter le callback et mettre en cache
     */
    public function rememberUserPermissions(User $user, callable $callback): array
    {
        $key = $this->getPermissionKey($user);
        return Cache::remember($key, self::PERMISSION_TTL, $callback);
    }

    /**
     * Invalider le cache des permissions d'un utilisateur
     */
    public function invalidateUserPermissions(User $user): void
    {
        Cache::forget($this->getPermissionKey($user));
    }

    /**
     * Invalider le cache des permissions de tous les utilisateurs d'un rôle
     */
    public function invalidateRolePermissions(Role $role): void
    {
        $users = $role->users()->get();
        foreach ($users as $user) {
            $this->invalidateUserPermissions($user);
        }
    }

    /**
     * Invalider le cache des permissions de tous les utilisateurs
     * ATTENTION : Cette méthode peut être coûteuse en production
     */
    public function invalidateAllUserPermissions(): void
    {
        User::chunk(100, function ($users) {
            foreach ($users as $user) {
                $this->invalidateUserPermissions($user);
            }
        });
    }

    /**
     * Récupérer les données d'un utilisateur depuis le cache
     */
    public function rememberUserData(User $user, callable $callback)
    {
        $key = $this->getUserKey($user);
        return Cache::remember($key, self::USER_TTL, $callback);
    }

    /**
     * Invalider le cache des données d'un utilisateur
     */
    public function invalidateUserData(User $user): void
    {
        Cache::forget($this->getUserKey($user));
    }

    /**
     * Récupérer les données d'un rôle depuis le cache
     */
    public function rememberRoleData(Role $role, callable $callback)
    {
        $key = $this->getRoleKey($role);
        return Cache::remember($key, self::ROLE_TTL, $callback);
    }

    /**
     * Invalider le cache des données d'un rôle
     */
    public function invalidateRoleData(Role $role): void
    {
        Cache::forget($this->getRoleKey($role));
    }

    /**
     * Nettoyer les anciens caches expirés (à appeler via un job planifié)
     */
    public function cleanExpiredCaches(): void
    {
        // Laravel gère automatiquement l'expiration des caches
        // Cette méthode est un placeholder pour une éventuelle logique de nettoyage
    }
}