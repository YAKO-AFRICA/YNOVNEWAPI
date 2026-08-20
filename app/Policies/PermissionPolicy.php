<?php

namespace App\Policies;

use App\Models\Api\Ynov\parameter\Permission;
use App\Models\Api\Ynov\parameter\PermissionGroup;
use App\Models\Api\Ynov\parameter\Role;
use App\Models\Api\Ynov\parameter\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PermissionPolicy
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
     * Vérifier si l'utilisateur peut voir les permissions
     */
    public function view(User $user): bool
    {
        return $user->hasPermission('permissions.afficher');
    }

    /**
     * Vérifier si l'utilisateur peut créer une permission
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('permissions.creer');
    }

    /**
     * Vérifier si l'utilisateur peut modifier une permission
     */
    public function update(User $user, Permission $permission): bool
    {
        // Vérifier la permission de base
        if (!$user->hasPermission('permissions.modifier')) {
            return false;
        }

        // Une permission système ne peut être modifiée que par un Super Admin
        if ($permission->is_system && !$user->isSuperAdmin()) {
            return false;
        }

        return true;
    }

    /**
     * Vérifier si l'utilisateur peut supprimer une permission
     */
    public function delete(User $user, Permission $permission): bool
    {
        // Vérifier la permission de base
        if (!$user->hasPermission('permissions.supprimer')) {
            return false;
        }

        // Une permission système ne peut être supprimée que par un Super Admin
        if ($permission->is_system && !$user->isSuperAdmin()) {
            return false;
        }

        // Une permission utilisée par des rôles ne peut pas être supprimée
        if ($permission->roles()->count() > 0) {
            return false;
        }

        return true;
    }

    /**
     * Vérifier si l'utilisateur peut attribuer des permissions à un rôle
     */
    public function assign(User $user, Permission $permission, ? Role $role = null): bool
    {
        // Vérifier la permission de base
        if (!$user->hasPermission('roles.gerer_permissions')) {
            return false;
        }

        // Une permission système ne peut être attribuée que par un Super Admin
        if ($permission->is_system && !$user->isSuperAdmin()) {
            return false;
        }

        // Vérifier la hiérarchie des rôles si un rôle est spécifié
        if ($role && !$user->isSuperAdmin()) {
            $currentUserRole = $user->role;
            if (!$currentUserRole || $role->level > $currentUserRole->level) {
                return false;
            }
        }

        // Les permissions de garde (is_guard) nécessitent une permission supplémentaire
        if ($permission->is_guard && !$user->hasPermission('permissions.gérer_sensibles')) {
            return false;
        }

        return true;
    }

    /**
     * Vérifier si l'utilisateur peut révoquer des permissions d'un rôle
     */
    public function revoke(User $user, Permission $permission, ?Role $role = null): bool
    {
        // Même logique que assign
        return $this->assign($user, $permission, $role);
    }

    /**
     * Vérifier si l'utilisateur peut voir les permissions d'un groupe
     */
    public function viewGroup(User $user, PermissionGroup $group): bool
    {
        return $user->hasPermission('permission_groups.afficher');
    }

    /**
     * Vérifier si l'utilisateur peut créer un groupe de permissions
     */
    public function createGroup(User $user): bool
    {
        return $user->hasPermission('permission_groups.creer');
    }

    /**
     * Vérifier si l'utilisateur peut modifier un groupe de permissions
     */
    public function updateGroup(User $user, PermissionGroup $group): bool
    {
        if (!$user->hasPermission('permission_groups.modifier')) {
            return false;
        }

        // Un groupe système ne peut être modifié que par un Super Admin
        if ($group->is_system && !$user->isSuperAdmin()) {
            return false;
        }

        return true;
    }

    /**
     * Vérifier si l'utilisateur peut supprimer un groupe de permissions
     */
    public function deleteGroup(User $user, PermissionGroup $group): bool
    {
        if (!$user->hasPermission('permission_groups.supprimer')) {
            return false;
        }

        // Un groupe système ne peut être supprimé que par un Super Admin
        if ($group->is_system && !$user->isSuperAdmin()) {
            return false;
        }

        // Un groupe contenant des permissions ne peut pas être supprimé
        if ($group->permissions()->count() > 0) {
            return false;
        }

        return true;
    }

    /**
     * Vérifier si l'utilisateur peut gérer les permissions sensibles (is_guard)
     */
    public function manageGuardPermissions(User $user): bool
    {
        return $user->isSuperAdmin() || $user->hasPermission('permissions.gérer_sensibles');
    }
}