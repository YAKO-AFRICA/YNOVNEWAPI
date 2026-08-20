<?php

namespace App\Policies;

use App\Models\Api\Ynov\parameter\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AuditLogPolicy
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
     * Vérifier si l'utilisateur peut consulter ses propres logs
     */
    public function viewOwn(User $user): bool
    {
        return true; // Tout utilisateur peut voir ses propres logs
    }

    /**
     * Vérifier si l'utilisateur peut consulter les logs d'un autre utilisateur
     */
    public function viewOther(User $user, User $targetUser): bool
    {
        if (!$user->hasPermission('audit.consulter')) {
            return false;
        }

        // Vérifier le scoping multi-tenant
        return $this->hasAccessToUser($user, $targetUser);
    }

    /**
     * Vérifier si l'utilisateur peut consulter tous les logs (admin)
     */
    public function viewAll(User $user): bool
    {
        return $user->isSuperAdmin() || $user->hasPermission('audit.consulter');
    }

    /**
     * Vérifier si l'utilisateur peut exporter les logs
     */
    public function export(User $user): bool
    {
        return $user->isSuperAdmin() || $user->hasPermission('audit.exporter');
    }

    /**
     * Vérifier si l'utilisateur peut purger les logs anciens
     */
    public function purge(User $user): bool
    {
        return $user->isSuperAdmin() || $user->hasPermission('audit.purger');
    }

    /**
     * Vérifier l'accès à un utilisateur (scoping multi-tenant)
     */
    private function hasAccessToUser(User $authUser, User $targetUser): bool
    {
        if ($authUser->uuid_user === $targetUser->uuid_user) {
            return true;
        }

        if ($authUser->partner_uuid && $authUser->partner_uuid !== $targetUser->partner_uuid) {
            return false;
        }

        if ($authUser->reseau_uuid && $authUser->reseau_uuid !== $targetUser->reseau_uuid) {
            return false;
        }

        $agenceUuids = $authUser->agences()->pluck('agences.uuid_agence');
        if ($agenceUuids->isNotEmpty()) {
            return $targetUser->agences()
                ->whereIn('agences.uuid_agence', $agenceUuids)
                ->exists();
        }

        return false;
    }
}