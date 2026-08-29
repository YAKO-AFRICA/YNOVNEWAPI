<?php
namespace App\Services\Api\Ynov;

use App\Mail\Api\Ynov\AccountBlockedMail;
use App\Mail\Api\Ynov\AccountUnblockedMail;
use App\Mail\Api\Ynov\WelcomeMail;
use App\Models\Api\Ynov\parameter\GroupNotif;
use App\Models\Api\Ynov\parameter\Role;
use App\Models\Api\Ynov\parameter\User;
use App\Models\Api\Ynov\parameter\UserDetails;
use App\Models\Api\Ynov\UserContrat;
use App\Services\Api\Ynov\NotificationService;
use App\Services\SMSService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class UserService
{
    public function __construct(
        // private OtpService $otpService,
        private readonly SMSService $SMSService,
        private NotificationService $notificationService,
    ) {}
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

            // Créer une notification pour le nouvel utilisateur
            $this->notificationService->create([
                'user_uuid' => $user->uuid_user,
                'group_notif_uuid' => $this->getWelcomeGroupUuid(),
                'title' => '👋 Bienvenue sur YNOV',
                'body' => 'Votre compte a été créé avec succès. Vous pouvez maintenant vous connecter et gérer vos contrats.',
                'type' => 'account',
                'metadata' => [
                    'created_at' => now()->toISOString(),
                ],
                'channel' => 'database',
                'created_by' => $creatorUuid,
            ]);

            if ($user->email){
                Mail::to($user->email)->queue(new WelcomeMail($user->fresh('details'), $data['password']));
            }

            return $user;
        });
    }

    public function createClient(array $data): array
    {
        $password = Str::random(12);

        $result = DB::transaction(function () use ($data, $password) {

            /*
            * Récupération du rôle client
            */
            $roleClient = Role::where('code', 'client')->first();

            if (!$roleClient) {
                return [
                    'success' => false,
                    'code' => 'NO_DEFAULT_ROLE',
                    'message' => 'Aucun rôle client configuré.',
                ];
            }

            /*
            * Vérification du login
            */
            $login = $data['login'];

            $userExists = User::where('login', $login)->exists();

            if ($userExists) {
                return [
                    'success' => false,
                    'code' => 'LOGIN_ALREADY_EXISTS',
                    'message' => "Le login {$login} est déjà associé à un compte.",
                ];
            }

            /*
            * Vérification des contrats
            */
            $contratExists = [];

            foreach ($data['contrats'] ?? [] as $contratData) {

                $existingContract = UserContrat::where(
                    'contrat_id',
                    $contratData['IdProposition']
                )->first();

                if ($existingContract) {
                    $contratExists[] = $contratData['IdProposition'];
                }
            }

            if (!empty($contratExists)) {

                $message = count($contratExists) > 1
                    ? 'Les contrats suivants sont déjà associés à un compte : '
                        . implode(', ', $contratExists)
                    : 'Le contrat '
                        . $contratExists[0]
                        . ' est déjà associé à un compte.';

                return [
                    'success' => false,
                    'code' => 'CONTRAT_ALREADY_EXISTS',
                    'message' => $message,
                ];
            }

            /*
            * UUID utilisateur
            */
            $uuidUser = (string) Str::uuid();

            /*
            * Adresse complète
            */
            $adresseComplete = implode(', ', array_filter([
                $data['ville'] ?? null,
                $data['code_postal'] ?? null,
                $data['lieu_residence'] ?? null,
                $data['pays'] ?? null,
            ]));

            /*
            * Création utilisateur
            */
            $user = User::create([
                'uuid_user' => $uuidUser,
                'email' => $data['email'] ?? null,
                'login' => $login,
                'password' => Hash::make($password),

                'role_uuid' => $roleClient->uuid_role,
                'user_type' => 'client',
                'status' => 'actif',

                'is_first_login' => true,

                'password_expires_at' => now()->addDays(90),

                'created_by' => $uuidUser,
            ]);

            /*
            * Création détails utilisateur
            */
            UserDetails::create([
                'uuid_user_details' => (string) Str::uuid(),

                'user_uuid' => $uuidUser,

                'numero_client' => $data['numero_client'] ?? null,

                'nom' => $data['nom'],
                'prenoms' => $data['prenoms'],

                'mobile_1' => $data['mobile_1'] ?? null,

                'email_pro' => $data['email'] ?? null,

                'fonction' => $data['fonction'] ?? null,

                'adresse_complete' => $adresseComplete,

                'code_postal' => $data['code_postal'] ?? null,

                'lieu_residence' => $data['lieu_residence'] ?? null,

                'date_naissance' => $data['date_naissance'] ?? null,

                'lieu_naissance' => $data['lieu_naissance'] ?? null,

                'genre' => $data['genre'] ?? null,

                'civilite' => $data['civilite'] ?? null,

                'ville' => $data['ville'] ?? null,

                'pays' => $data['pays'] ?? null,

                'nationalite' => $data['nationalite'] ?? null,

                'created_by' => $uuidUser,
            ]);

            /*
            * Création des contrats
            */
            foreach ($data['contrats'] ?? [] as $contrat) {

                UserContrat::create([
                    'uuid_user_contrat' => (string) Str::uuid(),

                    'user_uuid' => $user->uuid_user,

                    'contrat_id' => $contrat['IdProposition'] ?? null,

                    'client_number' => $data['client_number'] ?? null,

                    'code_produit' => $contrat['codeProduit'] ?? null,

                    'libelle_produit' => $contrat['produit'] ?? null,

                    'code_produit_formule' =>
                        $contrat['CodeProduitFormule'] ?? null,

                    'libelle_produit_formule' =>
                        $contrat['ProduitFormule'] ?? null,
                ]);
            }

             // Créer une notification de bienvenue pour le client
            $this->notificationService->create([
                'user_uuid' => $user->uuid_user,
                'group_notif_uuid' => $this->getWelcomeGroupUuid(),
                'title' => '👋 Bienvenue sur YNOV',
                'body' => 'Votre compte client a été créé avec succès. Vous pouvez maintenant vous connecter et gérer vos contrats.',
                'type' => 'account',
                'metadata' => [
                    'contrat_count' => count($data['contrats'] ?? []),
                    'created_at' => now()->toISOString(),
                ],
                'channel' => 'database',
                'created_by' => $uuidUser,
            ]);

            /*
            * Retour métier
            */
            return [
                'success' => true,
                'code' => 'USER_CREATED',
                'message' => 'Utilisateur créé avec succès.',
                'data' => $user,
            ];
        });

        /*
        * Transaction échouée / validation métier
        */
        if (!$result['success']) {
            return $result;
        }

        /*
        * Récupérer le vrai modèle User
        */
        $user = $result['data'];

        /*
        * Charger les relations nécessaires
        */
        $user->load('details');

        /*
        * Envoi des identifiants UNIQUEMENT après commit.
        */
        DB::afterCommit(function () use ($user, $password) {

            $this->sendWelcomeCredentials(
                $user,
                $password
            );

        });

        return [
            'success' => true,
            'code' => 'USER_CREATED',
            'message' => 'Utilisateur créé avec succès.',
            'data' => $user,
        ];
    }

    /**
     * Envoyer les informations de connexion.
     */
    private function sendWelcomeCredentials(
        User $user,
        string $password
    ): void {
        // Charger les détails
        $userDetails = $user->details;

        if (!$userDetails) {
            return;
        }

        /*
         * EMAIL
         */
        if (!empty($user->email)) {

            Mail::to($user->email)
                ->queue(
                    new WelcomeMail(
                        $user,
                        $password
                    )
                );
        } else {
            
            /*
             * SMS
             */
            if (!empty($userDetails->mobile_1)) {
    
                $message = "Cher {$userDetails->nom},\n\n"
                    . "Merci de recevoir les paramètres de connexion "
                    . "à votre espace client.\n\n"
                    . "Login : {$user->login}\n"
                    . "Mot de passe : {$password}\n"
                    . "Lien de connexion : "
                    . config('app.frontend_url');
    
                $response = $this->SMSService->sendSms(
                    $userDetails->mobile_1,
                    $message
                );
    
                /*
                 * Log en cas d'échec SMS
                 */
                if (!$response['success']) {
                    logger()->error(
                        'Échec envoi SMS de bienvenue',
                        [
                            'user_uuid' => $user->uuid_user,
                            'phone' => $userDetails->mobile_1,
                            'message' => $response['message'],
                        ]
                    );
                }
            }
        }

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

            // Créer une notification pour la mise à jour du profil
            $this->notificationService->create([
                'user_uuid' => $user->uuid_user,
                'group_notif_uuid' => $this->getAccountGroupUuid(),
                'title' => '📝 Profil mis à jour',
                'body' => 'Vos informations de profil ont été mises à jour avec succès.',
                'type' => 'account',
                'metadata' => [
                    'updated_by' => $updaterUuid,
                    'updated_at' => now()->toISOString(),
                ],
                'channel' => 'database',
                'created_by' => $updaterUuid,
            ]);

            return $user->fresh();
        });
    }

    public function delete(User $user, string $deleterUuid): void
    {
        $this->notificationService->create([
            'user_uuid' => $user->uuid_user,
            'group_notif_uuid' => $this->getAccountGroupUuid(),
            'title' => '🗑️ Compte supprimé',
            'body' => 'Votre compte a été supprimé. Vous ne pourrez plus vous connecter.',
            'type' => 'account',
            'metadata' => [
                'deleted_by' => $deleterUuid,
                'deleted_at' => now()->toISOString(),
            ],
            'channel' => 'database',
            'created_by' => $deleterUuid,
        ]);

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

        // Créer une notification pour le blocage
        $this->notificationService->create([
            'user_uuid' => $user->uuid_user,
            'group_notif_uuid' => $this->getSecurityGroupUuid(),
            'title' => '🚫 Compte bloqué',
            'body' => "Votre compte a été bloqué par un administrateur. Motif : {$reason}",
            'type' => 'security',
            'metadata' => [
                'reason' => $reason,
                'blocked_by' => $blockerId,
                'blocked_at' => now()->toISOString(),
            ],
            'channel' => 'database',
            'created_by' => $blockerId,
        ]);

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

        // Créer une notification pour le déblocage
        $this->notificationService->create([
            'user_uuid' => $user->uuid_user,
            'group_notif_uuid' => $this->getSecurityGroupUuid(),
            'title' => '🔓 Compte débloqué',
            'body' => 'Votre compte a été débloqué par un administrateur. Vous pouvez maintenant vous connecter.',
            'type' => 'security',
            'metadata' => [
                'unblocked_by' => $unblockerId,
                'unblocked_at' => now()->toISOString(),
            ],
            'channel' => 'database',
            'created_by' => $unblockerId,
        ]);
        
        if ($user->email) {
            Mail::to($user->email)->queue(new AccountUnblockedMail(
                $user->fresh('details'),
                $unblocker?->details?->full_name,
            ));
        }
    }

    /**
     * Récupérer l'UUID du groupe de bienvenue
     */
    private function getWelcomeGroupUuid(): ?string
    {
        $group = GroupNotif::where('code', 'welcome')->first();
        return $group?->uuid_group_notif;
    }

    /**
     * Récupérer l'UUID du groupe de sécurité
     */
    private function getSecurityGroupUuid(): ?string
    {
        $group = GroupNotif::where('code', 'securite')->first();
        return $group?->uuid_group_notif;
    }

    /**
     * Récupérer l'UUID du groupe de compte
     */
    private function getAccountGroupUuid(): ?string
    {
        $group = GroupNotif::where('code', 'compte')->first();
        return $group?->uuid_group_notif;
    }
}