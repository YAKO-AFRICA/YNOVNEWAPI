<?php
namespace App\Services\Api\Ynov;

use App\Mail\Api\Ynov\AccountBlockedMail;
use App\Mail\Api\Ynov\AccountUnblockedMail;
use App\Mail\Api\Ynov\WelcomeMail;
use App\Models\Api\Ynov\parameter\GroupNotif;
use App\Models\Api\Ynov\parameter\ActivityLog;
use App\Models\Api\Ynov\parameter\Role;
use App\Models\Api\Ynov\parameter\User;
use App\Models\Api\Ynov\parameter\UserDetails;
use App\Models\Api\Ynov\UserContrat;
use App\Services\Api\Ynov\NotificationService;
use App\Services\SMSService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class UserService
{
    public function __construct(
        // private OtpService $otpService,
        private readonly SMSService $SMSService,
        private NotificationService $notificationService,
    ) {}

    /**
     * Créer un utilisateur
     */
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

            // Créer les détails utilisateur
            UserDetails::create([
                'uuid_user_details' => (string) Str::uuid(),
                'user_uuid' => $user->uuid_user,
                'code_agent' => $data['code_agent'] ?? null,
                'matricule' => $data['matricule'] ?? null,
                'numero_client' => $data['numero_client'] ?? null,
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

            // Assigner les agences si fournies
            if (!empty($data['agence_uuids'])) {
                $this->assignAgences($user, $data['agence_uuids'], $creatorUuid);
            }

            // Si une seule agence est fournie (compatibilité)
            if (!empty($data['agence_uuid']) && empty($data['agence_uuids'])) {
                $this->assignAgences($user, [$data['agence_uuid']], $creatorUuid);
            }

            // Notifications
            $this->sendWelcomeNotifications($user, $data['password']);

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

    public function createClientAPI(array $data): array
    {
        $password = Str::random(12);
        Log::info('createClientAPI - Début', ['login' => $data['login'] ?? null, 'numero_client' => $data['numero_client'] ?? null]);

        $result = DB::transaction(function () use ($data, $password) {

            /*
            * Récupération du rôle client
            */
            Log::info('createClientAPI - Recherche rôle client');
            $roleClient = Role::where('code', 'client')->first();

            if (!$roleClient) {
                Log::error('createClientAPI - Rôle client non trouvé');
                return [
                    'success' => false,
                    'code' => 'NO_DEFAULT_ROLE',
                    'message' => 'Aucun rôle client configuré.',
                ];
            }
            Log::info('createClientAPI - Rôle client trouvé', ['uuid_role' => $roleClient->uuid_role]);

            /*
            * Vérification du login avec plusieurs critères
            */
            $login = $data['login'];
            $numeroClient = $data['numero_client'] ?? null;
            Log::info('createClientAPI - Vérification utilisateur', ['login' => $login, 'numero_client' => $numeroClient]);

            $userExists = User::where('login', $login)
                ->orWhereHas('details', function ($query) use ($numeroClient) {
                    $query->where('numero_client', $numeroClient);
                })
                ->first();

            Log::info('createClientAPI - Résultat recherche utilisateur', ['user_exists' => $userExists ? 'oui' : 'non']);

            /*
            * CAS 1: Utilisateur existe déjà
            */
            if ($userExists) {
                Log::info('createClientAPI - CAS 1: Utilisateur existe', ['uuid_user' => $userExists->uuid_user]);

                // Identifier les contrats déjà associés
                $existingContractIds = UserContrat::where('user_uuid', $userExists->uuid_user)
                    ->pluck('contrat_id')
                    ->toArray();
                Log::info('createClientAPI - Contrats existants', ['count' => count($existingContractIds), 'ids' => $existingContractIds]);

                // Identifier les nouveaux contrats à ajouter
                $newContracts = [];
                foreach ($data['contrats'] ?? [] as $contrat) {
                    $contratId = $contrat['IdProposition'] ?? null;
                    if ($contratId && !in_array($contratId, $existingContractIds)) {
                        $newContracts[] = $contrat;
                    }
                }
                Log::info('createClientAPI - Nouveaux contrats à ajouter', ['count' => count($newContracts)]);

                if (empty($newContracts)) {
                    Log::warning('createClientAPI - Aucun nouveau contrat à ajouter');
                    return [
                        'success' => false,
                        'code' => 'NO_NEW_CONTRACTS',
                        'message' => 'Tous les contrats sont déjà associés à ce compte.',
                    ];
                }

                // Ajouter les nouveaux contrats
                foreach ($newContracts as $contrat) {
                    Log::info('createClientAPI - Ajout contrat', ['IdProposition' => $contrat['IdProposition']]);
                    UserContrat::create([
                        'uuid_user_contrat' => (string) Str::uuid(),
                        'user_uuid' => $userExists->uuid_user,
                        'contrat_id' => $contrat['IdProposition'] ?? null,
                        'client_number' => $data['client_number'] ?? null,
                        'code_produit' => $contrat['codeProduit'] ?? null,
                        'libelle_produit' => $contrat['produit'] ?? null,
                        'code_produit_formule' => $contrat['CodeProduitFormule'] ?? null,
                        'libelle_produit_formule' => $contrat['ProduitFormule'] ?? null,
                    ]);
                }

                Log::info('createClientAPI - Contrats ajoutés avec succès');
                return [
                    'success' => true,
                    'code' => 'CONTRACTS_ADDED',
                    'message' => count($newContracts) . ' contrat(s) ajouté(s) au compte existant.',
                    'data' => $userExists,
                    'new_contracts_count' => count($newContracts),
                ];
            }

            /*
            * CAS 2: Utilisateur n'existe pas - création
            */
            Log::info('createClientAPI - CAS 2: Création nouvel utilisateur');

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
                Log::warning('createClientAPI - Contrats déjà associés', ['ids' => $contratExists]);

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
            Log::info('createClientAPI - UUID généré', ['uuid_user' => $uuidUser]);

            /*
            * Adresse complète
            */
            $adresseComplete = implode(', ', array_filter([
                // $data['ville'] ?? null,
                $data['lieu_residence'] ?? null,
                $data['pays'] ?? null,
            ]));

            /*
            * Création utilisateur
            */
            Log::info('createClientAPI - Création utilisateur');
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

                'created_by' => 'system', // Création via API, pas d'utilisateur spécifique
            ]);
            Log::info('createClientAPI - Utilisateur créé', ['uuid_user' => $user->uuid_user]);

            /*
            * Création détails utilisateur
            */
            Log::info('createClientAPI - Création détails utilisateur');
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

                'lieu_residence' => $data['lieu_residence'] ?? null,

                'date_naissance' => $data['date_naissance'] ?? null,

                'lieu_naissance' => $data['lieu_naissance'] ?? null,

                'genre' => $data['genre'] ?? null,

                'civilite' => $data['civilite'] ?? null,

                'ville' => $data['ville'] ?? null,

                'pays' => $data['pays'] ?? null,

                'created_by' => 'system', // Création via API, pas d'utilisateur spécifique
            ]);
            Log::info('createClientAPI - Détails utilisateur créés');

            /*
            * Création des contrats
            */
            Log::info('createClientAPI - Création contrats', ['count' => count($data['contrats'] ?? [])]);
            foreach ($data['contrats'] ?? [] as $contrat) {
                Log::info('createClientAPI - Création contrat', ['IdProposition' => $contrat['IdProposition']]);
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
            Log::info('createClientAPI - Contrats créés');

             // Créer une notification de bienvenue pour le client
            Log::info('createClientAPI - Création notification bienvenue');
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
                'created_by' => 'system',
            ]);

            /*
            * Retour métier
            */
            Log::info('createClientAPI - Transaction réussie', ['code' => 'USER_CREATED']);
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
            Log::error('createClientAPI - Transaction échouée', ['code' => $result['code'], 'message' => $result['message']]);
            return $result;
        }

        /*
        * Récupérer le vrai modèle User
        */
        $user = $result['data'];
        Log::info('createClientAPI - Récupération user', ['uuid_user' => $user->uuid_user]);

        /*
        * Charger les relations nécessaires
        */
        $user->load('details');

        /*
        * Envoi des notifications UNIQUEMENT après commit.
        */
        DB::afterCommit(function () use ($user, $result, $password) {
            Log::info('createClientAPI - After commit', ['code' => $result['code']]);

            if ($result['code'] === 'CONTRACTS_ADDED') {
                // Cas: Contrats ajoutés à un compte existant
                Log::info('createClientAPI - Envoi notification ajout contrat');
                $this->sendContractAddedNotification(
                    $user,
                    $result['new_contracts_count'] ?? 0
                );
            } else {
                // Cas: Nouvel utilisateur créé
                Log::info('createClientAPI - Envoi identifiants bienvenue');
                $this->sendWelcomeCredentials(
                    $user,
                    $password
                );
            }

        });

        Log::info('createClientAPI - Fin', ['code' => $result['code']]);
        return [
            'success' => true,
            'code' => $result['code'],
            'message' => $result['message'],
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
                    . "Lien de connexion : https://espace-client.yakoafricassur.com";
                    // . config('app.frontend_url');

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

    /**
     * Envoyer notification d'ajout de contrat.
     */
    private function sendContractAddedNotification(
        User $user,
        int $contractCount
    ): void {
        Log::info('sendContractAddedNotification - Début', ['uuid_user' => $user->uuid_user, 'contract_count' => $contractCount]);

        // Charger les détails
        $userDetails = $user->details;

        if (!$userDetails) {
            Log::warning('sendContractAddedNotification - Pas de détails utilisateur');
            return;
        }

        /*
         * SMS dynamique selon nombre de contrats
         */
        if (!empty($userDetails->mobile_1)) {
            if ($contractCount === 1) {
                $message = "Votre contrat a ete ajoute a votre espace client. Connectez-vous pour le consulter.";
            } else {
                $message = "Vos {$contractCount} contrats ont ete ajoutes a votre espace client. Connectez-vous pour les consulter.";
            }

            // Vérifier la longueur (max 160 caractères)
            if (strlen($message) > 160) {
                $message = substr($message, 0, 157) . '...';
            }

            Log::info('sendContractAddedNotification - Envoi SMS', ['phone' => $userDetails->mobile_1, 'message_length' => strlen($message)]);
            $response = $this->SMSService->sendSms(
                $userDetails->mobile_1,
                $message
            );

            /*
             * Log en cas d'échec SMS
             */
            if (!$response['success']) {
                Log::error('sendContractAddedNotification - Échec SMS', [
                    'user_uuid' => $user->uuid_user,
                    'phone' => $userDetails->mobile_1,
                    'message' => $response['message'],
                ]);
            } else {
                Log::info('sendContractAddedNotification - SMS envoyé avec succès');
            }
        } else {
            Log::warning('sendContractAddedNotification - Pas de numéro mobile');
        }

        /*
         * Notification database
         */
        Log::info('sendContractAddedNotification - Création notification database');
        $this->notificationService->create([
            'user_uuid' => $user->uuid_user,
            'group_notif_uuid' => $this->getWelcomeGroupUuid(),
            'title' => '📄 Nouveau contrat ajouté',
            'body' => $contractCount === 1
                ? 'Un nouveau contrat a été ajouté à votre espace client.'
                : "{$contractCount} nouveaux contrats ont été ajoutés à votre espace client.",
            'type' => 'contract',
            'metadata' => [
                'contract_count' => $contractCount,
                'added_at' => now()->toISOString(),
            ],
            'channel' => 'database',
            'created_by' => 'system',
        ]);
        Log::info('sendContractAddedNotification - Notification database créée');
    }


    /**
     * Mettre à jour un utilisateur
     */
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

            // Mettre à jour les détails si présents
            if ($user->details) {
                 $user->details->update([
                    'nom' => $data['nom'] ?? $user->details->nom,
                    'prenoms' => $data['prenoms'] ?? $user->details->prenoms,
                    'fonction' => $data['fonction'] ?? $user->details->fonction,
                    'service' => $data['service'] ?? $user->details->service,
                    'departement' => $data['departement'] ?? $user->details->departement,
                    'mobile_1' => $data['mobile_1'] ?? $user->details->mobile_1,
                    'mobile_2' => $data['mobile_2'] ?? $user->details->mobile_2,
                    'email_pro' => $data['email_pro'] ?? $user->details->email_pro,
                    'date_naissance' => $data['date_naissance'] ?? $user->details->date_naissance,
                    'lieu_naissance' => $data['lieu_naissance'] ?? $user->details->lieu_naissance,
                    'genre' => $data['genre'] ?? $user->details->genre,
                    'civilite' => $data['civilite'] ?? $user->details->civilite,
                    'ville' => $data['ville'] ?? $user->details->ville,
                    'pays' => $data['pays'] ?? $user->details->pays,
                    'updated_by' => $updaterUuid,
                ]);
            }

            // Mettre à jour les agences
            // Mise à jour des agences
            if (isset($data['agence_uuids'])) {
                // Si replace est true, on remplace toutes les agences
                if (isset($data['replace']) && $data['replace']) {
                    $this->syncAgences($user, $data['agence_uuids'], $updaterUuid);
                } else {
                    // Sinon, on ajoute les nouvelles agences
                    $this->assignAgences($user, $data['agence_uuids'], $updaterUuid);
                }
            }

            return $user->fresh(['details', 'role', 'agences']);
            // return $user->fresh();
        });
    }

    /**
     * Assigner des agences à un utilisateur
     */
    public function assignAgences(User $user, array $agenceUuids, string $assignerUuid): void
    {
        $syncData = [];
        // $isFirst = $user->agences()->count() === 0;

        $currentAgences = $user->agences()->pluck('uuid_agence')->toArray();
        $newAgences = array_diff($agenceUuids, $currentAgences);

        if (empty($newAgences)) {
            return;
        }

        // foreach ($agenceUuids as $agenceUuid) {
        //     $syncData[$agenceUuid] = [
        //         'uuid_user_agence' => (string) Str::uuid(),
        //         'is_primary' => $isFirst, // La première agence devient principale
        //         'is_active' => true,
        //         'assigned_at' => now(),
        //         'assigned_by' => $assignerUuid,
        //     ];
        //     $isFirst = false;
        // }

        $hasPrimary = $user->agences()->wherePivot('is_primary', true)->exists();

        foreach ($newAgences as $agenceUuid) {
            $syncData[$agenceUuid] = [
                'uuid_user_agence' => (string) Str::uuid(),
                'is_primary' => !$hasPrimary, // La première agence devient principale si aucune n'existe
                'is_active' => true,
                'assigned_at' => now(),
                'assigned_by' => $assignerUuid,
            ];
            $hasPrimary = true;
        }

        $user->agences()->syncWithoutDetaching($syncData);

        ActivityLog::log([
            'user_uuid' => $assignerUuid,
            'action' => 'assign_agences',
            'action_type' => 'security',
            'module' => 'users',
            'description' => "Affectation d'agences à l'utilisateur : {$user->login}",
            'resource_type' => 'user',
            'resource_id' => $user->uuid_user,
            'new_values' => ['agence_uuids' => array_values($newAgences)],
            'level' => 'info',
        ]);
    }

    /**
     * Synchroniser les agences d'un utilisateur
     */
    public function syncAgences(User $user, array $agenceUuids, string $updaterUuid): void
    {
        $currentAgences = $user->agences()->pluck('uuid_agence')->toArray();

        // Agences à supprimer
        $toRemove = array_diff($currentAgences, $agenceUuids);
        if (!empty($toRemove)) {
            $user->agences()->detach($toRemove);
        }

        // Agences à ajouter
        $toAdd = array_diff($agenceUuids, $currentAgences);
        if (!empty($toAdd)) {
            $syncData = [];
            $isFirst = $user->agences()->count() === 0;

            foreach ($toAdd as $agenceUuid) {
                $syncData[$agenceUuid] = [
                    'uuid_user_agence' => (string) Str::uuid(),
                    'is_primary' => $isFirst,
                    'is_active' => true,
                    'assigned_at' => now(),
                    'assigned_by' => $updaterUuid,
                ];
                $isFirst = false;
            }

            $user->agences()->attach($syncData);
        }

        // Si aucune agence n'est assignée, réinitialiser la première comme principale
        if ($user->agences()->count() > 0) {
            $firstAgence = $user->agences()->first();
            $user->agences()->updateExistingPivot($firstAgence->uuid_agence, ['is_primary' => true]);
        }

        if (!empty($toRemove) || !empty($toAdd)) {
            ActivityLog::log([
                'user_uuid' => $updaterUuid,
                'action' => 'sync_agences',
                'action_type' => 'security',
                'module' => 'users',
                'description' => "Synchronisation des agences de l'utilisateur : {$user->login}",
                'resource_type' => 'user',
                'resource_id' => $user->uuid_user,
                'old_values' => ['agence_uuids' => array_values($currentAgences)],
                'new_values' => ['agence_uuids' => array_values($agenceUuids)],
                'level' => 'info',
            ]);
        }
    }

    /**
     * Définir l'agence principale d'un utilisateur
     */
    public function setPrimaryAgence(User $user, string $agenceUuid, string $updaterUuid): bool
    {
        // Vérifier que l'utilisateur appartient à cette agence
        if (!$user->belongsToAgence($agenceUuid)) {
            return false;
        }

        $previousPrimaryAgence = $user->agences()
            ->wherePivot('is_primary', true)
            ->value('uuid_agence');

        // Retirer le statut principal des autres agences
        $user->agences()->updateExistingPivot($user->agences()->pluck('uuid_agence')->toArray(), [
            'is_primary' => false,
        ]);

        // Définir cette agence comme principale
        $user->agences()->updateExistingPivot($agenceUuid, [
            'is_primary' => true,
        ]);

        ActivityLog::log([
            'user_uuid' => $updaterUuid,
            'action' => 'set_primary_agence',
            'action_type' => 'security',
            'module' => 'users',
            'description' => "Définition de l'agence principale de l'utilisateur : {$user->login}",
            'resource_type' => 'user',
            'resource_id' => $user->uuid_user,
            'old_values' => ['primary_agence_uuid' => $previousPrimaryAgence],
            'new_values' => ['primary_agence_uuid' => $agenceUuid],
            'level' => 'info',
        ]);

        return true;
    }

    /**
     * Envoyer les notifications de bienvenue
     */
    private function sendWelcomeNotifications(User $user, string $password): void
    {
        $this->notificationService->create([
            'user_uuid' => $user->uuid_user,
            'group_notif_uuid' => $this->getWelcomeGroupUuid(),
            'title' => '👋 Bienvenue sur YNOV',
            'body' => 'Votre compte a été créé avec succès. Vous pouvez maintenant vous connecter.',
            'type' => 'account',
            'metadata' => [
                'created_at' => now()->toISOString(),
            ],
            'channel' => 'database',
            'created_by' => $user->uuid_user,
        ]);

        if ($user->email) {
            Mail::to($user->email)->queue(new WelcomeMail($user->fresh('details'), $password));
        }
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