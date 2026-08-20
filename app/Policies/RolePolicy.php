<?php

namespace App\Policies;

use App\Models\Api\Ynov\parameter\Permission;
use App\Models\Api\Ynov\parameter\Role;
use App\Models\Api\Ynov\parameter\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RolePolicy
{
    use HandlesAuthorization;

    /**
     * Vérification globale : le Super Admin a tous les droits
     */
    public function before(User $user, $ability)
    {
        if ($user->isSuperAdmin()) {
            return true;
        }
    }

    /**
     * Vérifier si l'utilisateur peut assigner un rôle à un autre utilisateur
     * 
     * Règle : Un utilisateur ne peut assigner qu'un rôle de niveau inférieur ou égal au sien
     */
    public function assign(User $user, Role $role): bool
    {
        if ($user->role === null) {
            return false;
        }

        // On ne peut pas assigner un rôle de niveau supérieur au sien
        return $role->level <= $user->role->level;
    }

    /**
     * Vérifier si l'utilisateur peut attribuer une permission à un rôle
     */
    public function assignPermission(User $user, Role $role, Permission $permission): bool
    {
        if ($user->role === null) {
            return false;
        }

        // Hiérarchie : on ne peut pas modifier un rôle de niveau supérieur
        if ($role->level > $user->role->level) {
            return false;
        }

        // Les permissions "de garde" nécessitent une permission spéciale
        if ($permission->is_guard && !$user->hasPermission('permissions.gérer_sensibles')) {
            return false;
        }

        return true;
    }

    /**
     * Vérifier si l'utilisateur peut créer un rôle
     */
    public function create(User $user): bool
    {
        // Seul un Super Admin ou un utilisateur avec la permission spécifique
        // peut créer un rôle
        return $user->hasPermission('roles.creer');
    }

    /**
     * Vérifier si l'utilisateur peut modifier un rôle
     */
    public function update(User $user, Role $role): bool
    {
        // Un rôle système ne peut être modifié que par un Super Admin
        if ($role->is_system && !$user->isSuperAdmin()) {
            return false;
        }

        // Règle hiérarchique
        if ($user->role === null) {
            return false;
        }

        return $role->level <= $user->role->level;
    }

    /**
     * Vérifier si l'utilisateur peut supprimer un rôle
     */
    public function delete(User $user, Role $role): bool
    {
        // Un rôle système ne peut être supprimé que par un Super Admin
        if ($role->is_system && !$user->isSuperAdmin()) {
            return false;
        }

        // Règle hiérarchique
        if ($user->role === null) {
            return false;
        }

        return $role->level <= $user->role->level;
    }
}