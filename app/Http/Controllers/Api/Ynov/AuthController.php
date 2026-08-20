<?php
namespace App\Http\Controllers\Api\Ynov;

use App\Exceptions\Api\Ynov\AccountFrozenException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Ynov\LoginRequest;
use App\Http\Requests\Api\Ynov\RegisterRequest;
use App\Http\Resources\Api\Ynov\UserResource;
use App\Mail\Api\Ynov\SessionRevokedMail;
use App\Models\Api\Ynov\parameter\ActivityLog;
use App\Models\Api\Ynov\parameter\User;
use App\Services\Api\Ynov\Auth\AuthService;
use App\Services\Api\Ynov\Auth\DeviceService;
use App\Services\Api\Ynov\UserService;
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

            // Si 2FA requis
            if ($result['requires_2fa'] ?? false) {
                return response()->json([
                    'success' => true,
                    'code' => '2FA_REQUIRED',
                    'message' => 'Vérification 2FA requise.',
                    'data' => [
                        // 'otp_token' => $result['two_factor_secret'],
                        'two_factor_token' => $result['two_factor_token'],
                        'user' => new UserResource($result['user']),
                    ],
                ]);
            }

            // Si changement de mot de passe requis
            if ($result['must_change_password'] ?? false) {
                return response()->json([
                    'success' => true,
                    'code' => 'PASSWORD_CHANGE_REQUIRED',
                    'message' => 'Vous devez changer votre mot de passe.',
                    'data' => [
                        'change_password_token' => $result['change_password_token'],
                        'user' => new UserResource($result['user']),
                    ],
                ]);
            }

            // Token dans le header Authorization
            return response()->json([
                'success' => true,
                'data' => [
                    'user' => new UserResource($result['user']),
                    'expires_at' => now()->addHours(24), // Duree de durée du token
                    'requires_2fa' => false,
                    'must_change_password' => false,
                    'trusted_device' => $result['trusted_device'],
                    'access_token' => $result['token']
                ]
            ])->header('Authorization', 'Bearer ' . $result['token']);

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
        $user = User::where('email', $login)->orWhere('login', $login)->first();

        if (!$user || !$this->authService->isCurrentlyFrozen($user)) {
            return response()->json(['success' => true, 'data' => ['is_frozen' => false]]);
        }

        $remaining = max(0, (int) now()->diffInSeconds($user->frozen_until, false));

        return response()->json([
            'success' => true,
            'data' => [
                'is_frozen' => true,
                'remaining_seconds' => $remaining,
                'freeze_level' => $user->freeze_level,
            ],
        ]);
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $user = $this->userService->createClient($request->validated());
            
            return response()->json([
                'success' => true,
                'message' => 'Inscription réussie. Veuillez vérifier votre email.',
                'data' => new UserResource($user->load('details')),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
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

        $user->setAttribute('permissions_grouped', $user->getGroupedPermissions());

        return response()->json([
            'success' => true,
            'data' => new UserResource($user),
        ]);
        // return response()->json([
        //     'success' => true,
        //     'data' => new UserResource($request->user()->load([
        //         'role.permissions.group', 'details', 'partner', 'reseau', 'agences', 'groupNotifs', 'permissions.group'
        //     ])),
        // ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $tokenId = $request->user()->currentAccessToken()->id;
        $this->authService->logout($request->user(), $tokenId);
        return response()->json(['success' => true, 'message' => 'Déconnexion réussie.']);
    }

    // Logout all devices
    public function logoutAll(Request $request): JsonResponse
    {
        $user = $request->user();
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
        return response()->json(['success' => true, 'message' => 'Déconnexion de tous les appareils.']);
    }

    public function refresh(Request $request): JsonResponse
    {
        $token = $this->authService->refresh(
            $request->user(),
            $request->user()->currentAccessToken()->id,
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
        ]);
    }
}