<?php
namespace App\Http\Controllers\Api\Ynov;

use App\Exceptions\Api\Ynov\AccountFrozenException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Ynov\LoginRequest;
use App\Http\Resources\Api\Ynov\UserResource;
use App\Mail\Api\Ynov\SessionRevokedMail;
use App\Models\Api\Ynov\parameter\ActivityLog;
use App\Models\Api\Ynov\parameter\GroupNotif;
use App\Models\Api\Ynov\parameter\User;
use App\Services\Api\Ynov\Auth\AuthService;
use App\Services\Api\Ynov\Auth\DeviceService;
use App\Services\Api\Ynov\NotificationService;
use App\Services\Api\Ynov\UserService;
use App\Services\EncaissementBisService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    public function __construct(
        private AuthService $authService,
        private DeviceService $deviceService,
        private UserService $userService,
        private EncaissementBisService $encaissementBisService,
        private NotificationService $notificationService,
    ) {}

    
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $deviceInfo = [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'fingerprint' => $this->deviceService->fingerprint($request),
                'device_name' => $request->device_name ?? 'Appareil inconnu',
                'location' => $request->header('X-Geo-Location'),
            ];

            $result = $this->authService->login($request->only(['login', 'password']), $deviceInfo);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                    'code' => $result['code'],
                ], 422);
            }

            // Si 2FA requis
            if ($result['requires_2fa'] ?? false) {
                // Créer une notification pour la 2FA
                if ($result['user'] ?? null) {
                    $this->notificationService->create([
                        'user_uuid' => $result['user']->uuid_user,
                        'group_notif_uuid' => $this->getSecurityGroupUuid(),
                        'title' => '🔐 Code 2FA requis',
                        'body' => 'Une vérification 2FA est requise pour finaliser votre connexion. Veuillez vérifier votre code.',
                        'type' => 'security',
                        'metadata' => [
                            'ip_address' => $deviceInfo['ip'],
                            'device_name' => $deviceInfo['device_name'],
                            'trusted_device' => $result['trusted_device'] ?? false,
                        ],
                        'channel' => 'database',
                        'created_by' => null,
                    ]);
                }
                return response()->json([
                    'success' => true,
                    'code' => '2FA_REQUIRED',
                    'message' => 'Vérification 2FA requise.',
                    'data' => [
                        // 'otp_token' => $result['two_factor_secret'],
                        'two_factor_token' => $result['two_factor_token'],
                        'user' => new UserResource($result['user']),
                    ],
                ], 200);
            }

            // Si changement de mot de passe requis
            if ($result['must_change_password'] ?? false) {
                if ($result['user'] ?? null) {
                    $this->notificationService->create([
                        'user_uuid' => $result['user']->uuid_user,
                        'group_notif_uuid' => $this->getSecurityGroupUuid(),
                        'title' => '🔑 Changement de mot de passe requis',
                        'body' => 'Vous devez changer votre mot de passe avant de continuer. Il s\'agit soit de votre première connexion, soit de l\'expiration de votre mot de passe.',
                        'type' => 'security',
                        'metadata' => [
                            'is_first_login' => $result['user']->is_first_login ?? false,
                            'password_expired' => $result['user']->isPasswordExpired() ?? false,
                        ],
                        'channel' => 'database',
                        'created_by' => null,
                    ]);
                }
                return response()->json([
                    'success' => true,
                    'code' => 'PASSWORD_CHANGE_REQUIRED',
                    'message' => 'Vous devez changer votre mot de passe.',
                    'data' => [
                        'change_password_token' => $result['change_password_token'],
                        'user' => new UserResource($result['user']),
                    ],
                ], 201);
            }

            // Token dans le header Authorization
            return response()->json([
                'success' => true,
                'code' => 'AUTH_SUCCESS',
                'message' => 'Connexion réussie.',
                'data' => [
                    'user' => new UserResource($result['user']),
                    'expires_at' => now()->addHours(24), // Duree de durée du token
                    'requires_2fa' => false,
                    'must_change_password' => false,
                    'trusted_device' => $result['trusted_device'],
                    'access_token' => $result['token']
                ]
            ], 201)->header('Authorization', 'Bearer ' . $result['token']);

        } catch (AccountFrozenException $e) {
            return response()->json($e->toArray(), 423);
        } catch (\RuntimeException $e) {
            // $code = ($e->getCode() >= 400 && $e->getCode() < 600) ? $e->getCode() : 500;
            return response()->json([
                'success' => false, 
                'code' => 'AUTH_ERROR', 
                'message' => $e->getMessage()
            ], 422);
        } catch (\Throwable $e) {
            // ✅ AJOUTER CETTE PARTIE POUR CAPTURER TOUTES LES ERREURS
            Log::error('Erreur login: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            return response()->json([
                'success' => false,
                'code' => 'SERVER_ERROR',
                'message' => 'Une erreur interne est survenue. Veuillez réessayer.'
            ], 500);
  
        }
    }

    public function freezeCheck(string $login): JsonResponse
    {
        $user = User::where('login', $login)->first();

        if (!$user || !$this->authService->isCurrentlyFrozen($user)) {
            return response()->json(['success' => true, 'data' => ['is_frozen' => false]], 200);
        }

        $remaining = max(0, (int) now()->diffInSeconds($user->frozen_until, false));

        return response()->json([
            'success' => true,
            'data' => [
                'is_frozen' => true,
                'remaining_seconds' => $remaining,
                'freeze_level' => $user->freeze_level,
            ],
        ], 200);
    }

    public function getRegisterData(Request $request): JsonResponse
    {
        try {
            $result = $this->encaissementBisService->getContrat($request->idcontrat);
            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                ], 422);
            }

            $data = $result['data'];
            $dataRequired = [
                'details' => $data['details'][0],
                'autreContrat' => $data['autreContrat'],
                'contactsPersonne' => $data['contactsPersonne'],
                'InfoPiecePersonson' => $data['InfoPiecePersonson'],
            ];

            if ($data['details'][0]['DateNaissance'] != $request->datenaissance) {
                return response()->json([
                    'success' => false,
                    'code' => 'DATE_OF_BIRTH_MISMATCH',
                    'message' => 'La date de naissance saisie ne correspond pas à celle enregistrée dans le contrat.',
                ], 422);
            }

            if ($data['details'][0]['OnStdbyOff'] == "3") {
                return response()->json([
                    'success' => false,
                    'code' => 'CONTRACT_FROZEN',
                    'message' => 'Ce contrat est arreté.',
                ], 422);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Contrat trouvé.',
                'data' => $dataRequired

            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function register(Request $request): JsonResponse
    {
        try {

            $result = $this->userService->createClient(
                $request->all()
            );

            /*
            * Échec métier
            */
            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'code' => $result['code'],
                    'message' => $result['message'],
                ], 422);
            }

            /*
            * Utilisateur créé
            */
            $user = $result['data'];

            /*
            * Réponse API
            */
            return response()->json([
                'success' => true,
                'code' => $result['code'],
                'message' => 'Inscription réussie. '
                    . 'Vos paramètres de connexion ont été envoyés.',
                'data' => new UserResource(
                    $user->load('details')
                ),
            ], 201);

        } catch (\Throwable $e) {

            logger()->error(
                'Erreur création client',
                [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'login' => $request->input('login'),
                ]
            );

            return response()->json([
                'success' => false,
                'code' => 'USER_CREATION_ERROR',
                'message' => 'Une erreur est survenue lors de la création du compte.',
            ], 500);
        }
    }


    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load([
            'role.permissions.group',
            'details',
            'partner',
            'reseau',
            'agences',
            'groupNotifs',
            'userContrats'
        ]);

        $userContrat = $user->userContrats()->first();

        if ($userContrat) {
            
            $result = $this->encaissementBisService->getContrat($userContrat->contrat_id);
    
            if ($result['success']) {
                $data = $result['data'];
            }
            $anciennete = $data['anciennete'] ?? [];
        }

        $user->setAttribute('permissions_grouped', $user->getGroupedPermissions());

        return response()->json([
            'success' => true,
            'anciennete' => $anciennete ?? [],
            'data' => new UserResource($user),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $tokenId = $request->user()->currentAccessToken()->id;
        $this->authService->logout($request->user(), $tokenId);
        return response()->json(['success' => true, 'message' => 'Déconnexion réussie.'], 200);
    }

    // Logout all devices
    public function logoutAll(Request $request): JsonResponse
    {
        $user = $request->user();
        $this->notificationService->create([
            'user_uuid' => $user->uuid_user,
            'group_notif_uuid' => $this->getSecurityGroupUuid(),
            'title' => '🔒 Déconnexion de tous les appareils',
            'body' => 'Vous avez été déconnecté de tous vos appareils. Un email vous a été envoyé pour confirmation.',
            'type' => 'security',
            'metadata' => [
                'reason' => 'Logout all devices',
                'ip_address' => $request->ip(),
            ],
            'channel' => 'database',
            'created_by' => null,
        ]);

        $this->authService->logoutAll($user);
        if ($user->email) {
            Mail::to($user->email)->queue(new SessionRevokedMail($user->fresh('details'), '', true));
        }
        ActivityLog::log([
            'user_uuid' => $user->uuid_user,
            'action' => 'logout',
            'action_type' => 'logout',
            'module' => 'auth',
            'description' => "Deconnexion de tous les appareils",
            'resource_type' => 'user',
            'resource_id' => $user->uuid_user,
            'level' => 'info',
        ]);
        return response()->json(['success' => true, 'message' => 'Déconnexion de tous les appareils.'], 200);
    }

    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Créer une notification pour le rafraîchissement du token
        $this->notificationService->create([
            'user_uuid' => $user->uuid_user,
            'group_notif_uuid' => $this->getSecurityGroupUuid(),
            'title' => '🔄 Token rafraîchi',
            'body' => 'Votre token d\'authentification a été rafraîchi avec succès.',
            'type' => 'security',
            'metadata' => [
                'ip_address' => $request->ip(),
            ],
            'channel' => 'database',
            'created_by' => null,
        ]);

        $token = $this->authService->refresh(
            $user,
            $user->currentAccessToken()->id,
            $request->header('X-Device-Name', 'API Token')
        );
        ActivityLog::log([
            'user_uuid' => $request->user()->uuid_user,
            'action' => 'refresh',
            'action_type' => 'refresh',
            'module' => 'auth',
            'description' => "Refresh token",
            'resource_type' => 'user',
            'resource_id' => $request->user()->uuid_user,
            'level' => 'info',
        ]);
        return response()->json([
            'success' => true,
            'data' => ['access_token' => $token, 'token_type' => 'Bearer', 'expires_at' => now()->addHours(24)],
        ], 200);
    }

    /**
     * Récupérer l'UUID du groupe de sécurité
     */
    private function getSecurityGroupUuid(): ?string
    {
        $group = GroupNotif::where('code', 'securite')->first();
        return $group?->uuid_group_notif;
    }
}