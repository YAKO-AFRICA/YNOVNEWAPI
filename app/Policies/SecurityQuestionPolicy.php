<?php

namespace App\Policies;

use App\Models\Api\Ynov\parameter\SecurityQuestion;
use App\Models\Api\Ynov\parameter\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SecurityQuestionPolicy
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
     * Vérifier si l'utilisateur peut voir les questions de sécurité
     */
    public function view(User $user): bool
    {
        return true; // Les questions sont publiques
    }

    /**
     * Vérifier si l'utilisateur peut configurer ses propres questions
     */
    public function configure(User $user): bool
    {
        return true; // Tout utilisateur authentifié peut configurer ses questions
    }

    /**
     * Vérifier si l'utilisateur peut vérifier ses propres questions
     */
    public function verify(User $user): bool
    {
        return true; // Tout utilisateur (même non authentifié) peut vérifier ses questions
    }

    /**
     * Vérifier si l'utilisateur peut créer une question de sécurité (admin)
     */
    public function createQuestion(User $user): bool
    {
        return $user->hasPermission('security_questions.gerer');
    }

    /**
     * Vérifier si l'utilisateur peut modifier une question de sécurité (admin)
     */
    public function updateQuestion(User $user, SecurityQuestion $question): bool
    {
        if (!$user->hasPermission('security_questions.gerer')) {
            return false;
        }

        // Une question système ne peut être modifiée que par un Super Admin
        if ($question->is_system && !$user->isSuperAdmin()) {
            return false;
        }

        return true;
    }

    /**
     * Vérifier si l'utilisateur peut supprimer une question de sécurité (admin)
     */
    public function deleteQuestion(User $user, SecurityQuestion $question): bool
    {
        if (!$user->hasPermission('security_questions.gerer')) {
            return false;
        }

        // Une question système ne peut être supprimée que par un Super Admin
        if ($question->is_system && !$user->isSuperAdmin()) {
            return false;
        }

        // Une question utilisée par des utilisateurs ne peut pas être supprimée
        if ($question->userAnswers()->count() > 0) {
            return false;
        }

        return true;
    }

    /**
     * Vérifier si l'utilisateur peut réinitialiser les questions d'un autre utilisateur (admin)
     */
    public function resetUserQuestions(User $user, User $targetUser): bool
    {
        if (!$user->hasPermission('security_questions.reinitialiser')) {
            return false;
        }

        // Un Super Admin peut tout faire
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Vérifier le scoping multi-tenant
        return $this->hasAccessToUser($user, $targetUser);
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