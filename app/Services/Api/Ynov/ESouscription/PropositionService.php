<?php

namespace App\Services\Api\Ynov\ESouscription;

use App\Models\Api\Ynov\Esouscription\Contrat;
use App\Models\Api\Ynov\parameter\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class PropositionService
{
    public function getForUser(
        string $userUuid,
        int $perPage = 15,
        array $filters = []
    ): LengthAwarePaginator {
        $query = $this->accessibleQuery($userUuid);

        foreach (['uuid_contrat', 'id_contrat', 'id_proposition'] as $field) {
            if (isset($filters[$field]) && $filters[$field] !== '') {
                $query->where($field, $filters[$field]);
            }
        }

        foreach (['code_proposition', 'numero_police', 'numero_compte'] as $field) {
            if (! empty($filters[$field])) {
                $query->where($field, 'like', '%'.$filters[$field].'%');
            }
        }

        if (! empty($filters['date_debut'])) {
            $query->whereDate('created_at', '>=', $filters['date_debut']);
        }

        if (! empty($filters['date_fin'])) {
            $query->whereDate('created_at', '<=', $filters['date_fin']);
        }

        $actorFilters = ['idClient', 'numero_piece', 'nom', 'prenoms'];
        $hasActorFilters = false;
        foreach ($actorFilters as $field) {
            if (isset($filters[$field]) && $filters[$field] !== '') {
                $hasActorFilters = true;
                break;
            }
        }

        if ($hasActorFilters) {
            $query->whereHas('contratActeurs', function (Builder $relationQuery) use ($filters): void {
                $relationQuery
                    ->where('type_acteur', 'ADH')
                    ->whereHas('acteur', function (Builder $actorQuery) use ($filters): void {
                        foreach (['idClient', 'numero_piece', 'nom', 'prenoms'] as $field) {
                            if (isset($filters[$field]) && $filters[$field] !== '') {
                                $actorQuery->where($field, 'like', '%'.$filters[$field].'%');
                            }
                        }
                    });
            });
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $searchQuery) use ($search): void {
                $searchQuery
                    ->where('uuid_contrat', 'like', '%'.$search.'%')
                    ->orWhere('id_contrat', 'like', '%'.$search.'%')
                    ->orWhere('id_proposition', 'like', '%'.$search.'%')
                    ->orWhere('code_proposition', 'like', '%'.$search.'%')
                    ->orWhere('numero_police', 'like', '%'.$search.'%')
                    ->orWhere('numero_compte', 'like', '%'.$search.'%')
                    ->orWhereHas('contratActeurs', function (Builder $relationQuery) use ($search): void {
                        $relationQuery
                            ->where('type_acteur', 'ADH')
                            ->whereHas('acteur', function (Builder $actorQuery) use ($search): void {
                                $actorQuery
                                    ->where('nom', 'like', '%'.$search.'%')
                                    ->orWhere('prenoms', 'like', '%'.$search.'%')
                                    ->orWhere('idClient', 'like', '%'.$search.'%')
                                    ->orWhere('numero_piece', 'like', '%'.$search.'%');
                            });
                    });
            });
        }

        return $query
            ->with(['agence', 'partner', 'contratActeurs.acteur', 'contratActeurs.filiation', 'documents'])
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function findAccessibleByUuid(string $userUuid, string $contratUuid): Contrat
    {
        return $this->accessibleQuery($userUuid)
            ->where('uuid_contrat', $contratUuid)
            ->with(['agence', 'partner', 'contratActeurs.acteur', 'contratActeurs.filiation', 'documents'])
            ->firstOrFail();
    }

    private function accessibleQuery(string $userUuid): Builder
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

        return $query;
    }
}
