<?php
namespace App\Services\Api\Ynov;

use App\Mail\Api\Ynov\AccountBlockedMail;
use App\Mail\Api\Ynov\AccountUnblockedMail;
use App\Mail\Api\Ynov\WelcomeMail;
use App\Models\Api\Ynov\parameter\Role;
use App\Models\Api\Ynov\parameter\User;
use App\Models\Api\Ynov\parameter\UserDetails;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class UserService
{
    public function create(array $data, string $creatorUuid): User
    {
        return DB::transaction(function () use ($data, $creatorUuid) {
            $user = User::create([
                'uuid_user' => (string) Str::uuid(),
                'email' => $data['email'],
                'login' => $data['login'] ?? null,
                'password' => Hash::make($data['password']),
                'role_uuid' => $data['role_uuid'],
                'user_type' => $data['user_type'],
                'partner_uuid' => $data['partner_uuid'] ?? null,
                'reseau_uuid' => $data['reseau_uuid'] ?? null,
                'status' => 'actif',
                'is_first_login' => true,
                'password_expires_at' => now()->addDays(90),
            ]);

            UserDetails::create([
                'uuid_user_details' => (string) Str::uuid(),
                'user_uuid' => $user->uuid_user,
                'code_agent' => $data['code_agent'] ?? null,
                'matricule' => $data['matricule'] ?? null,
                'nom' => $data['nom'],
                'prenoms' => $data['prenoms'],
                'fonction' => $data['fonction'] ?? null,
                'service' => $data['service'] ?? null,
                'departement' => $data['departement'] ?? null,
                'mobile_1' => $data['mobile_1'] ?? null,
                'mobile_2' => $data['mobile_2'] ?? null,
                'email_pro' => $data['email_pro'] ?? null,
                'date_naissance' => $data['date_naissance'] ?? null,
                'lieu_naissance' => $data['lieu_naissance'] ?? null,
                'genre' => $data['genre'] ?? null,
                'civilite' => $data['civilite'] ?? null,
                'ville' => $data['ville'] ?? null,
                'pays' => $data['pays'] ?? null,
                'created_by' => $creatorUuid,
            ]);

            if (!empty($data['agence_uuid'])) {
                $user->agences()->attach($data['agence_uuid'], [
                    'uuid_user_agence' => (string) Str::uuid(),
                    'is_primary' => true,
                    'is_active' => true,
                    'assigned_at' => now(),
                ]);
            }

            Mail::to($user->email)->queue(new WelcomeMail($user->fresh('details'), $data['password']));

            return $user;
        });
    }


    public function createClient(array $data): User
    {
        return DB::transaction(function () use ($data) {
            // Récupérer le rôle client par défaut
            $defaultRole = Role::where('is_default', true)->first();
            if (!$defaultRole) {
                throw new \RuntimeException('Aucun rôle par défaut configuré.');
            }

            // Générer un login unique
            $login = $data['login'] ?? $data['email'];

            $user = User::create([
                'uuid_user' => (string) Str::uuid(),
                'email' => $data['email'],
                'login' => $login,
                'password' => Hash::make($data['password']),
                'role_uuid' => $defaultRole->uuid_role,
                'user_type' => 'client',
                'status' => 'actif',
                'is_first_login' => true,
                'password_expires_at' => now()->addDays(90),
            ]);

            UserDetails::create([
                'uuid_user_details' => (string) Str::uuid(),
                'user_uuid' => $user->uuid_user,
                'nom' => $data['nom'],
                'prenoms' => $data['prenoms'],
                'mobile_1' => $data['mobile_1'] ?? null,
                'email_pro' => $data['email'],
                'created_by' => $user->uuid_user,
            ]);

            // Envoyer l'email de vérification
            // Mail::to($user->email)->queue(new WelcomeMail($user));

            return $user;
        });
    }

    public function update(User $user, array $data, string $updaterUuid): User
    {
        return DB::transaction(function () use ($user, $data, $updaterUuid) {
            $user->update([
                'email' => $data['email'] ?? $user->email,
                'login' => $data['login'] ?? $user->login,
                'role_uuid' => $data['role_uuid'] ?? $user->role_uuid,
                'user_type' => $data['user_type'] ?? $user->user_type,
                'partner_uuid' => $data['partner_uuid'] ?? $user->partner_uuid,
                'reseau_uuid' => $data['reseau_uuid'] ?? $user->reseau_uuid,
                'status' => $data['status'] ?? $user->status,
            ]);

            if ($user->details) {
                $user->details->update([
                    'nom' => $data['nom'] ?? $user->details->nom,
                    'prenoms' => $data['prenoms'] ?? $user->details->prenoms,
                    'fonction' => $data['fonction'] ?? $user->details->fonction,
                    'mobile_1' => $data['mobile_1'] ?? $user->details->mobile_1,
                    'mobile_2' => $data['mobile_2'] ?? $user->details->mobile_2,
                    'ville' => $data['ville'] ?? $user->details->ville,
                    'updated_by' => $updaterUuid,
                ]);
            }

            return $user->fresh();
        });
    }

    public function delete(User $user, string $deleterUuid): void
    {
        $user->update([
            'deleted_by' => $deleterUuid,
            'status' => 'inactif',
            'deleted_at' => now(), // Le softDelete sera appliqué automatiquement
        ]);
        $user->tokens()->delete();
        // $user->delete();
    }

    public function block(User $user, string $reason, string $blockerId): void
    {
        $user->update([
            'status' => 'bloque',
            'blocked_by' => $blockerId,
            'blocked_at' => now(),
            'blocked_reason' => $reason,
            'is_locked' => true,
            'freeze_level' => 0,
            'failed_login_count' => $user->failed_login_count ?? 0,
        ]);
        $user->tokens()->delete();
        $blocker = User::where('uuid_user', $blockerId)->first();
        if ($user->email) {
            Mail::to($user->email)->queue(new AccountBlockedMail(
                $user->fresh('details'),
                $reason,
                $blocker?->details?->full_name,
            ));
        }
    }

    public function unblock(User $user, ?string $unblockerId = null): void
    {
        $user->update([
            'status' => 'actif',
            'blocked_by' => null,
            'blocked_at' => null,
            'blocked_reason' => null,
            'is_locked' => false,
            'freeze_level' => 0,
            'failed_login_count' => 0,
        ]);

        $unblocker = $unblockerId ? User::where('uuid_user', $unblockerId)->first() : null;
        if ($user->email) {
            Mail::to($user->email)->queue(new AccountUnblockedMail(
                $user->fresh('details'),
                $unblocker?->details?->full_name,
            ));
        }
    }
}