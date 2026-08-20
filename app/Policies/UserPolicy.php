<?php

namespace App\Policies;

use App\Models\Api\Ynov\parameter\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
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
     * Vérifier si l'utilisateur authentifié peut voir un autre utilisateur
     */
    public function view(User $authUser, User $targetUser): bool
    {
        return $this->hasAccessToUser($authUser, $targetUser);
    }

    /**
     * Vérifier si l'utilisateur authentifié peut modifier un autre utilisateur
     */
    public function update(User $authUser, User $targetUser): bool
    {
        return $this->hasAccessToUser($authUser, $targetUser);
    }

    /**
     * Vérifier si l'utilisateur authentifié peut supprimer un autre utilisateur
     */
    public function delete(User $authUser, User $targetUser): bool
    {
        return $this->hasAccessToUser($authUser, $targetUser);
    }

    /**
     * Vérifier si l'utilisateur authentifié peut bloquer un autre utilisateur
     */
    public function block(User $authUser, User $targetUser): bool
    {
        return $this->hasAccessToUser($authUser, $targetUser);
    }

    /**
     * Vérifier si l'utilisateur authentifié peut débloquer un autre utilisateur
     */
    public function unblock(User $authUser, User $targetUser): bool
    {
        return $this->hasAccessToUser($authUser, $targetUser);
    }

    /**
     * Logique centralisée d'accès à un utilisateur
     * 
     * Un utilisateur peut accéder à un autre utilisateur si :
     * - Il est Super Admin (déjà géré par before())
     * - Il partage le même partenaire
     * - Il partage le même réseau
     * - Il partage une agence commune
     * - Ou c'est lui-même
     */
    private function hasAccessToUser(User $authUser, User $targetUser): bool
    {
        // Si c'est le même utilisateur
        if ($authUser->uuid_user === $targetUser->uuid_user) {
            return true;
        }

        // Vérification par partenaire
        if ($authUser->partner_uuid && $authUser->partner_uuid !== $targetUser->partner_uuid) {
            return false;
        }

        // Vérification par réseau
        if ($authUser->reseau_uuid && $authUser->reseau_uuid !== $targetUser->reseau_uuid) {
            return false;
        }

        // Vérification par agence
        $agenceUuids = $authUser->agences()->pluck('agences.uuid_agence');
        if ($agenceUuids->isNotEmpty()) {
            return $targetUser->agences()
                ->whereIn('agences.uuid_agence', $agenceUuids)
                ->exists();
        }

        // Si l'utilisateur n'a ni partenaire, ni réseau, ni agence,
        // il ne peut voir que lui-même (déjà vérifié plus haut)
        return false;
    }
}