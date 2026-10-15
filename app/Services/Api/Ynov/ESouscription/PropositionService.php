<?php

namespace App\Services\Api\Ynov\ESouscription;

use App\Models\Api\Ynov\Esouscription\Contrat;
use App\Models\Api\Ynov\parameter\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PropositionService
{
    public function getForUser(string $userUuid, int $perPage = 15): LengthAwarePaginator
    {
        $user = User::query()
            ->with([
                'role',
                'agences' => fn ($query) => $query->wherePivot('is_active', true),
            ])
            ->where('uuid_user', $userUuid)
            ->firstOrFail();

        $roleCode = $user->role?->code;

        if ($roleCode === 'admin_souscription' || $roleCode === 'superviseur_souscription') {
            $query = Contrat::query();
        } elseif ($roleCode === 'manager_souscription') {
            $agenceUuids = $user->agences->pluck('uuid_agence');
            $query = Contrat::query()
                ->where(function ($query) use ($user, $agenceUuids) {
                    $query->where('created_by', $user->uuid_user);

                    if ($agenceUuids->isNotEmpty()) {
                        $query->orWhereIn('agence_uuid', $agenceUuids);
                    }
                });
        } elseif ($roleCode === 'conseiller_souscription') {
            $query = Contrat::query()->where('created_by', $user->uuid_user);
        } else {
            throw new AuthorizationException('Le rôle de cet utilisateur ne permet pas de consulter les propositions.');
        }

        return $query
            ->with(['agence', 'contratActeurs.acteur'])
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }
}
