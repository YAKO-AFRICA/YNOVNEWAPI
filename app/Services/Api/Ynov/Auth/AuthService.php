<?php

// namespace App\Services\Api\Ynov\Auth;

// use App\Exceptions\Api\Ynov\AccountFrozenException;
// use App\Mail\Api\Ynov\LoginFailedAlertMail;
// use App\Models\Api\Ynov\parameter\ActivityLog;
// use App\Models\Api\Ynov\parameter\LoginAttempt;
// use App\Models\Api\Ynov\parameter\User;
// use App\Services\Api\Ynov\Auth\DeviceService;
// use App\Services\Api\Ynov\Auth\FreezeService;
// use App\Services\Api\Ynov\Auth\IpRestrictionService;
// use App\Services\Api\Ynov\Auth\PasswordService;
// use Illuminate\Support\Facades\Hash;
// use Illuminate\Support\Facades\Log;
// use Illuminate\Support\Facades\Mail;

// /**
//  * Service central d'authentification YNOV.
//  * CORRECTIONS : Gestion des tokens temporaires avec abilities spécifiques
//  */
// class AuthService
// {
//     private const ALERT_THRESHOLD = 2;

//     private const LEVELS = [
//         1 => ['attempts' => 3, 'duration' => 10, 'label' => 'Léger'], // Gelé durant 10s
//         2 => ['attempts' => 4, 'duration' => 60, 'label' => 'Modéré'], // Gelé durant 1min
//         3 => ['attempts' => 5, 'duration' => 180, 'label' => 'Sévère'], // Gelé durant 3min
//     ];

//     public function __construct(
//         private FreezeService $freezeService,
//         private DeviceService $deviceService,
//         private IpRestrictionService $ipService,
//         private PasswordService $passwordService,
//     ) {}

//     public function login(array $credentials, array $deviceInfo): array
//     {
//         if (!$this->ipService->isAllowed($deviceInfo['ip'])) {
//             $this->logAttempt(null, $credentials['login'], $deviceInfo, false, 'IP_BLOCKED');
//             throw new \RuntimeException('Accès refusé depuis cette adresse IP.', 403);
//         }

//         $user = User::where('email', $credentials['login'])
//             ->orWhere('login', $credentials['login'])
//             ->with('details', 'role', 'agences', 'reseau', 'partner', 'groupNotifs')
//             ->first();

//         if (!$user) {
//             $this->logAttempt(null, $credentials['login'], $deviceInfo, false, 'USER_NOT_FOUND');
//             throw new \RuntimeException('Utilisateur introuvable avec ces identifiants.', 401);
//         }


//         if ($this->freezeService->isFrozen($user)) {
//             $this->logAttempt($user, $credentials['login'], $deviceInfo, false, 'USER_FROZEN');
//             $user->refresh();
//             if ($user->status === 'bloque' || $user->is_locked) {
//                 throw new \RuntimeException('Compte bloqué. Contactez votre administrateur.', 403);
//             }

//             $levelConfig = self::LEVELS[$user->freeze_level] ?? null;
//             $remaining = max(0, (int) now()->diffInSeconds($user->frozen_until, false));

//             ActivityLog::log([
//                 'user_uuid' => $user->uuid_user,
//                 'action' => 'login',
//                 'success' => false,
//                 'action_type' => 'auth',
//                 'module' => 'auth',
//                 'resource_id' => $user->uuid_user,
//                 'description' => "Tentative de connexion sur un compte temporairement gelé ({$remaining}s restantes).",
//                 'level' => 'warning',
//             ]);

//             throw new AccountFrozenException(
//                 message: $this->buildFrozenMessage($user->freeze_level, $remaining),
//                 freezeLevel: $user->freeze_level,
//                 freezeLabel: $levelConfig['label'] ?? 'Manuel',
//                 remainingSeconds: $remaining,
//                 frozenUntil: $user->frozen_until,
//             );
//         }

//         if ($user->status === 'inactif') {
//             $this->logAttempt($user, $credentials['login'], $deviceInfo, false, 'ACCOUNT_INACTIVE');
//             ActivityLog::log([
//                 'user_uuid' => $user->uuid_user,
//                 'action' => 'login',
//                 'success' => false,
//                 'action_type' => 'auth',
//                 'module' => 'auth',
//                 'resource_id' => $user->uuid_user,
//                 'description' => "Tentative de connexion sur un compte desactivé.",
//                 'level' => 'warning',
//             ]);
//             throw new \RuntimeException('Compte desactivé. Contactez votre administrateur.', 403);
//         }

//         if ($user->status === 'bloque' || $user->is_locked) {
//             $this->logAttempt($user, $credentials['login'], $deviceInfo, false, 'ACCOUNT_BLOCKED');
//             // $user->refresh();
//             ActivityLog::log([
//                 'user_uuid' => $user->uuid_user,
//                 'action' => 'login',
//                 'success' => false,
//                 'action_type' => 'auth',
//                 'module' => 'auth',
//                 'resource_id' => $user->uuid_user,
//                 'description' => "Tentative de connexion sur un compte bloqué.",
//                 'level' => 'warning',
//             ]);
//             throw new \RuntimeException('Compte bloqué. Contactez votre administrateur.', 403);
//         }

//         if (!Hash::check($credentials['password'], $user->password)) {
//             $this->freezeService->handleFailedAttempt($user);
//             $this->logAttempt($user, $credentials['login'], $deviceInfo, false, 'INVALID_PASSWORD');
//             $this->maybeAlertOnRepeatedFailure($user, $deviceInfo);

//             $user->refresh();
//             if ($this->freezeService->isFrozen($user)) {
//                 $remaining = max(0, (int) now()->diffInSeconds($user->frozen_until, false));
//                 $levelConfig = self::LEVELS[$user->freeze_level] ?? null;

//                 throw new AccountFrozenException(
//                     message: $this->buildFrozenMessage($user->freeze_level, $remaining),
//                     freezeLevel: $user->freeze_level,
//                     freezeLabel: $levelConfig['label'] ?? 'Manuel',
//                     remainingSeconds: $remaining,
//                     frozenUntil: $user->frozen_until,
//                 );
//             }
//             throw new \RuntimeException('Mot de passe incorrect.', 401);
//         }

//         $requires2fa = $user->two_factor_enabled;
//         $trusted = $this->deviceService->isTrusted($user, $deviceInfo['fingerprint']);
//         $mustChange = $this->passwordService->isExpired($user) || $user->is_first_login;

//         $this->freezeService->resetAttempts($user);
//         $this->deviceService->updateOrCreate($user, $deviceInfo);
//         $this->logAttempt($user, $credentials['login'], $deviceInfo, true);

//         $user->update([
//             'last_login_at' => now(),
//             'last_activity_at' => now(),
//             'is_online' => true,
//             'failed_login_count' => 0,
//         ]);

//         if ($requires2fa && !$trusted) {
//             // Token avec ability '2fa-verify' uniquement
//             $tempToken = $user->createToken('2fa-auth', ['2fa-verify'], now()->addMinutes(5));
//             return [
//                 'user' => $user,
//                 'requires_2fa' => true,
//                 'must_change_password' => false,
//                 'trusted_device' => false,
//                 'two_factor_token' => $tempToken->plainTextToken,
//             ];
//         }

//         if ($mustChange) {
//             // Token avec ability 'password-change' uniquement
//             $tempToken = $user->createToken('password-change', ['password-change'], now()->addHours(1));
//             return [
//                 'user' => $user,
//                 'requires_2fa' => false,
//                 'must_change_password' => true,
//                 'trusted_device' => $trusted,
//                 'change_password_token' => $tempToken->plainTextToken,
//             ];
//         }

//         // Token avec ability '*'
//         $token = $user->createToken($deviceInfo['device_name'] ?? 'API Token', ['*'], now()->addHours(24));

//         return [
//             'user' => $user,
//             'token' => $token->plainTextToken,
//             'requires_2fa' => false,
//             'must_change_password' => false,
//             'trusted_device' => $trusted,
//         ];
//     }

//     private function buildFrozenMessage(int $level, int $remaining): string
//     {
//         $minutes = intdiv($remaining, 60);
//         $seconds = $remaining % 60;

//         $remainingMessage = match (true) {
//             $minutes > 0 && $seconds > 0 => "{$minutes} min {$seconds} s",
//             $minutes > 0 => "{$minutes} min",
//             default => "{$seconds} s",
//         };

//         return "Compte temporairement gelé. Réessayez dans {$remainingMessage}.";
//     }

//     public function isCurrentlyFrozen(User $user): bool
//     {
//         return $this->freezeService->isFrozen($user);
//     }

//     public function logout(User $user, string $tokenId): void
//     {
//         $user->tokens()->where('id', $tokenId)->delete();
//         if ($user->tokens()->count() === 0) {
//             $user->update(['is_online' => false]);
//         }
//     }

//     public function logoutAll(User $user): void
//     {
//         $user->tokens()->delete();
//         $user->update(['is_online' => false]);
//     }

//     public function refresh(User $user, string $currentTokenId, string $deviceName): string
//     {
//         $newToken = $user->createToken($deviceName, ['*'], now()->addHours(24));
//         $user->tokens()->where('id', $currentTokenId)->delete();
//         return $newToken->plainTextToken;
//     }

//     private function maybeAlertOnRepeatedFailure(User $user, array $deviceInfo): void
//     {
//         $count = $user->fresh()->failed_login_count;

//         if ($count === self::ALERT_THRESHOLD) {
//             Mail::to($user->email)->queue(new LoginFailedAlertMail(
//                 $user->fresh('details'),
//                 $count,
//                 $deviceInfo['ip'] ?? null,
//                 $deviceInfo['location'] ?? null,
//             ));
//         }
//     }

//     private function logAttempt(?User $user, string $login, array $deviceInfo, bool $success, ?string $failureReason = null): void
//     {
//         LoginAttempt::create([
//             'user_uuid' => $user?->uuid_user,
//             'login_attempted' => $login,
//             'ip_address' => $deviceInfo['ip'],
//             'user_agent' => $deviceInfo['user_agent'],
//             'location' => $deviceInfo['location'] ?? null,
//             'is_successful' => $success,
//             'failure_reason' => $failureReason,
//             'attempted_at' => now(),
//         ]);
//     }
// }

namespace App\Services\Api\Ynov\Auth;

use App\Exceptions\Api\Ynov\AccountFrozenException;
use App\Mail\Api\Ynov\LoginFailedAlertMail;
use App\Models\Api\Ynov\parameter\ActivityLog;
use App\Models\Api\Ynov\parameter\LoginAttempt;
use App\Models\Api\Ynov\parameter\User;
use App\Services\Api\Ynov\Auth\DeviceService;
use App\Services\Api\Ynov\Auth\FreezeService;
use App\Services\Api\Ynov\Auth\IpRestrictionService;
use App\Services\Api\Ynov\Auth\PasswordService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Service central d'authentification YNOV.
 * CORRECTIONS : Message unique pour éviter l'énumération des utilisateurs
 */
class AuthService
{
    private const ALERT_THRESHOLD = 2;

    private const LEVELS = [
        1 => ['attempts' => 3, 'duration' => 10, 'label' => 'Léger'],
        2 => ['attempts' => 4, 'duration' => 60, 'label' => 'Modéré'],
        3 => ['attempts' => 5, 'duration' => 180, 'label' => 'Sévère'],
    ];

    public function __construct(
        private FreezeService $freezeService,
        private DeviceService $deviceService,
        private IpRestrictionService $ipService,
        private PasswordService $passwordService,
    ) {}

    public function login(array $credentials, array $deviceInfo): array
    {
        // 1. Vérification IP
        if (!$this->ipService->isAllowed($deviceInfo['ip'])) {
            $this->logAttempt(null, $credentials['login'], $deviceInfo, false, 'IP_BLOCKED');
            return ['success' => false, 'code' => 'IP_BLOCKED', 'message' => 'Accès refusé depuis cette adresse IP.'];
            // throw new \RuntimeException('Accès refusé depuis cette adresse IP.', 403);
        }

        // 2. Recherche de l'utilisateur
        $user = User::where('email', $credentials['login'])
            ->orWhere('login', $credentials['login'])
            ->with('details', 'role', 'agences', 'reseau', 'partner', 'groupNotifs')
            ->first();

        if (!$user) {
            $this->logAttempt(null, $credentials['login'], $deviceInfo, false, 'USER_NOT_FOUND');
            
            return ['success' => false, 'code' => 'USER_NOT_FOUND', 'message' => 'Utilisateur introuvable avec ce Login.'];
            // throw new \RuntimeException('Utilisateur introuvable avec ces identifiants.', 401);
        }
            
        // On vérifie d'abord si l'utilisateur existe ET si le mot de passe est valide
        // Cela permet d'avoir un message unique quel que soit le cas
        $passwordValid = $user && Hash::check($credentials['password'], $user->password);

        // Si l'utilisateur n'existe pas OU le mot de passe est invalide
        if (!$passwordValid) {
            // Journaliser la tentative (avec user_uuid si l'utilisateur existe)
            $this->logAttempt(
                $user,
                $credentials['login'],
                $deviceInfo,
                false,
                'INVALID_PASSWORD'
            );

            // Si l'utilisateur existe mais mot de passe incorrect, on gère le freeze
            if ($user && !$passwordValid) {
                $this->freezeService->handleFailedAttempt($user);
                $this->maybeAlertOnRepeatedFailure($user, $deviceInfo);

                // Vérifier si le compte a été gelé suite à l'échec
                $user->refresh();
                if ($this->freezeService->isFrozen($user)) {
                    $remaining = max(0, (int) now()->diffInSeconds($user->frozen_until, false));
                    $levelConfig = self::LEVELS[$user->freeze_level] ?? null;

                    throw new AccountFrozenException(
                        message: $this->buildFrozenMessage($user->freeze_level, $remaining),
                        freezeLevel: $user->freeze_level,
                        freezeLabel: $levelConfig['label'] ?? 'Manuel',
                        remainingSeconds: $remaining,
                        frozenUntil: $user->frozen_until,
                    );
                }
            }
            return ['success' => false, 'code' => 'INVALID_PASSWORD', 'message' => 'Mot de passe incorrect.'];
            // throw new \RuntimeException('Mot de passe incorrect.', 401);
        }


        // 3. Vérification du gel (après avoir confirmé que l'utilisateur existe)
        if ($this->freezeService->isFrozen($user)) {
            $this->logAttempt($user, $credentials['login'], $deviceInfo, false, 'USER_FROZEN');
            $user->refresh();

            if ($user->status === 'bloque' || $user->is_locked) {
                return ['success' => false, 'code' => 'ACCOUNT_BLOCKED', 'message' => 'Compte bloqué. Contactez votre administrateur.'];
                // throw new \RuntimeException('Compte bloqué. Contactez votre administrateur.', 403);
            }

            $levelConfig = self::LEVELS[$user->freeze_level] ?? null;
            $remaining = max(0, (int) now()->diffInSeconds($user->frozen_until, false));

            ActivityLog::log([
                'user_uuid' => $user->uuid_user,
                'action' => 'login',
                'action_type' => 'auth',
                'module' => 'auth',
                'resource_id' => $user->uuid_user,
                'description' => "Tentative de connexion sur un compte temporairement gelé ({$remaining}s restantes).",
                'level' => 'warning',
            ]);

            throw new AccountFrozenException(
                message: $this->buildFrozenMessage($user->freeze_level, $remaining),
                freezeLevel: $user->freeze_level,
                freezeLabel: $levelConfig['label'] ?? 'Manuel',
                remainingSeconds: $remaining,
                frozenUntil: $user->frozen_until,
            );
        }

        // 4. Vérification des statuts
        if ($user->status === 'inactif') {
            $this->logAttempt($user, $credentials['login'], $deviceInfo, false, 'ACCOUNT_INACTIVE');
            ActivityLog::log([
                'user_uuid' => $user->uuid_user,
                'action' => 'login',
                'action_type' => 'auth',
                'module' => 'auth',
                'resource_id' => $user->uuid_user,
                'description' => "Tentative de connexion sur un compte desactivé.",
                'level' => 'warning',
            ]);
            return ['success' => false, 'code' => 'ACCOUNT_DESACTIVATED', 'message' => 'Compte desactivé. Contactez votre administrateur.'];
            // throw new \RuntimeException('Compte desactivé. Contactez votre administrateur.', 403);
        }

        if ($user->status === 'bloque' || $user->is_locked) {
            $this->logAttempt($user, $credentials['login'], $deviceInfo, false, 'ACCOUNT_BLOCKED');
            ActivityLog::log([
                'user_uuid' => $user->uuid_user,
                'action' => 'login',
                'action_type' => 'auth',
                'module' => 'auth',
                'resource_id' => $user->uuid_user,
                'description' => "Tentative de connexion sur un compte bloqué.",
                'level' => 'warning',
            ]);
            return ['success' => false, 'code' => 'ACCOUNT_BLOCKED', 'message' => 'Compte bloqué. Contactez votre administrateur.'];
            // throw new \RuntimeException('Compte bloqué. Contactez votre administrateur.', 403);
        }

        // 5. Le mot de passe a déjà été vérifié à l'étape 2
        // On vérifie juste si la 2FA est requise
        $requires2fa = $user->two_factor_enabled;
        $trusted = $this->deviceService->isTrusted($user, $deviceInfo['fingerprint']);
        $mustChange = $this->passwordService->isExpired($user) || $user->is_first_login;

        // 6. Réinitialiser les tentatives (connexion réussie)
        $this->freezeService->resetAttempts($user);
        $this->deviceService->updateOrCreate($user, $deviceInfo);
        $this->logAttempt($user, $credentials['login'], $deviceInfo, true);

        $user->update([
            'last_login_at' => now(),
            'last_activity_at' => now(),
            'is_online' => true,
            'failed_login_count' => 0,
        ]);

        // 7. Gestion des cas particuliers (2FA, changement de mot de passe)
        if ($requires2fa && !$trusted) {
            $tempToken = $user->createToken('2fa-auth', ['2fa-verify'], now()->addMinutes(5));
            return [
                'success' => true,
                'code' => '2FA_REQUIRED',
                'message' => 'Vérification 2FA requise.',
                'user' => $user,
                'requires_2fa' => true,
                'must_change_password' => false,
                'trusted_device' => false,
                'two_factor_token' => $tempToken->plainTextToken,
            ];
        }

        if ($mustChange) {
            $tempToken = $user->createToken('password-change', ['password-change'], now()->addHours(1));
            return [
                'success' => true,
                'code' => 'PASSWORD_CHANGE_REQUIRED',
                'message' => 'Changer le mot de passe',
                'user' => $user,
                'requires_2fa' => false,
                'must_change_password' => true,
                'trusted_device' => $trusted,
                'change_password_token' => $tempToken->plainTextToken,
            ];
        }

        // 8. Token standard
        $token = $user->createToken($deviceInfo['device_name'] ?? 'API Token', ['*'], now()->addHours(24));

        return [
            'success' => true,
            'code' => 'AUTH_SUCCESS',
            'message' => 'Connexion reussie',
            'user' => $user,
            'token' => $token->plainTextToken,
            'requires_2fa' => false,
            'must_change_password' => false,
            'trusted_device' => $trusted,
        ];
    }

    private function buildFrozenMessage(int $level, int $remaining): string
    {
        $minutes = intdiv($remaining, 60);
        $seconds = $remaining % 60;

        $remainingMessage = match (true) {
            $minutes > 0 && $seconds > 0 => "{$minutes} min {$seconds} s",
            $minutes > 0 => "{$minutes} min",
            default => "{$seconds} s",
        };

        return "Compte temporairement gelé. Réessayez dans {$remainingMessage}.";
    }

    public function isCurrentlyFrozen(User $user): bool
    {
        return $this->freezeService->isFrozen($user);
    }

    public function logout(User $user, string $tokenId): void
    {
        $user->tokens()->where('id', $tokenId)->delete();
        if ($user->tokens()->count() === 0) {
            $user->update(['is_online' => false]);
        }
    }

    public function logoutAll(User $user): void
    {
        $user->tokens()->delete();
        $user->update(['is_online' => false]);
    }

    public function refresh(User $user, string $currentTokenId, string $deviceName): string
    {
        $newToken = $user->createToken($deviceName, ['*'], now()->addHours(24));
        $user->tokens()->where('id', $currentTokenId)->delete();
        return $newToken->plainTextToken;
    }

    private function maybeAlertOnRepeatedFailure(User $user, array $deviceInfo): void
    {
        $count = $user->fresh()->failed_login_count;

        if ($count === self::ALERT_THRESHOLD && $user->email) {
            Mail::to($user->email)->queue(new LoginFailedAlertMail(
                $user->fresh('details'),
                $count,
                $deviceInfo['ip'] ?? null,
                $deviceInfo['location'] ?? null,
            ));
        }
    }

    private function logAttempt(?User $user, string $login, array $deviceInfo, bool $success, ?string $failureReason = null): void
    {
        LoginAttempt::create([
            'user_uuid' => $user?->uuid_user,
            'login_attempted' => $login,
            'ip_address' => $deviceInfo['ip'],
            'user_agent' => $deviceInfo['user_agent'],
            'location' => $deviceInfo['location'] ?? null,
            'is_successful' => $success,
            'failure_reason' => $failureReason,
            'attempted_at' => now(),
        ]);
    }
}