<?php
namespace App\Http\Resources\Api\Ynov;

use App\Http\Resources\Api\Ynov\UserDetailResource;
use App\Models\Api\Ynov\parameter\Produit;
use App\Models\Api\Ynov\parameter\ProduitFormule;
use App\Models\Api\Ynov\parameter\ReseauProduct;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid_user' => $this->uuid_user,
            'login' => $this->login,
            'email' => $this->email,
            'user_type' => $this->user_type,
            'status' => $this->status,
            'is_first_login' => $this->is_first_login,
            'is_online' => $this->is_online,
            'is_locked' => $this->is_locked,
            'last_login_at' => $this->last_login_at,
            'email_verified_at' => $this->email_verified_at,
            'two_factor_enabled' => $this->two_factor_enabled,
            'role' => $this->whenLoaded('role', fn() => [
                'uuid_role' => $this->role->uuid_role,
                'code' => $this->role->code,
                'libelle' => $this->role->libelle,
                'description' => $this->role->description,
                'is_super_admin' => $this->role->is_super_admin,
            ]),
            'partner' => $this->whenLoaded('partner', fn() => [
                'uuid_partner' => $this->partner->uuid_partner,
                'designation' => $this->partner->designation,
                'code' => $this->partner->code,
                'code_contractant' => $this->partner->code_contractant,
                'code_branche' => $this->partner->code_branche,
            ]),
            'reseau' => $this->whenLoaded('reseau', fn() => [
                'uuid_reseau' => $this->reseau->uuid_reseau,
                'code' => $this->reseau->code,
                'code_branche' => $this->reseau->code_branche,
                'libelle' => $this->reseau->libelle,
            ]),
            'agences' => $this->whenLoaded('agences', fn() => $this->agences->map(fn($a) => [
                'uuid_agence' => $a->uuid_agence,
                'code' => $a->code,
                'libelle' => $a->libelle,
                'is_primary' => $a->pivot->is_primary ?? false,
            ])),
            'group_notifs' => $this->whenLoaded('groupNotifs', fn() => $this->groupNotifs->map(fn($g) => [
                'uuid_group_notif' => $g->uuid_group_notif,
                'code' => $g->code,
                'libelle' => $g->libelle,
            ])),
            'user_contrats' => $this->whenLoaded('userContrats', fn() => $this->userContrats->map(function ($c) {
                $produit = Produit::where('code', $c->code_produit)->first();
                $formule = ProduitFormule::where('code_produit_formule', $c->code_produit_formule)->first();
                $reseauProduct = ReseauProduct::where('product_uuid', $produit?->uuid_produit)
                    ->where('formule_uuid', $formule?->uuid_formule)
                    ->first();

                return [
                    'uuid_user_contrat' => $c->uuid_user_contrat,
                    'contrat_id' => $c->contrat_id,
                    'client_number' => $c->client_number,
                    'code_produit' =>  $c->code_produit,
                    'libelle_produit' => ($reseauProduct && isset($reseauProduct->display_name)) ? $reseauProduct->display_name : $c->libelle_produit,
                    'code_produit_formule' => $c->code_produit_formule,
                    'libelle_produit_formule' => $c->libelle_produit_formule,

                ];
            })),
            'details' => new UserDetailResource($this->whenLoaded('details')),
            'permissions' => $this->when(
                $this->relationLoaded('role') && $this->role,
                fn() => $this->role->is_super_admin ? ['*'] : $this->role->permissions->pluck('code')
            ),
            'group_permissions' => $this->when(
                $this->relationLoaded('role') && $this->role,
                function () {
                    if ($this->role->is_super_admin) {
                        return ['*'];
                    }

                    return $this->role->permissions()
                        ->where('status', 'actif')
                        ->where(function ($query) {
                            $query->whereNull('expires_at')
                                ->orWhere('expires_at', '>', now());
                        })
                        ->with('group')
                        ->get()
                        ->pluck('group')
                        ->unique('uuid_permission_group')
                        ->values()
                        ->map(fn($group) => [
                            'uuid_group' => $group->uuid_permission_group,
                            'code' => $group->code,
                            'libelle' => $group->libelle,
                            'icone' => $group->icone,
                            'color' => $group->color,
                        ])
                        ->toArray();
                }
            ),
        ];
    }
}