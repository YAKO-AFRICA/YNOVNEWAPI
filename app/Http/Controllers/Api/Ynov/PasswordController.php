<?php

// namespace App\Http\Controllers\Api\Ynov;

// use App\Http\Controllers\Controller;
// use App\Http\Requests\Api\Ynov\ChangePasswordRequest;
// use App\Http\Requests\Api\Ynov\FirstLoginPasswordRequest;
// use App\Http\Requests\Api\Ynov\ForgotPasswordRequest;
// use App\Http\Requests\Api\Ynov\ResetPasswordRequest;
// use App\Http\Resources\Api\Ynov\UserResource;
// use App\Mail\Api\Ynov\PasswordChangedMail;
// use App\Mail\Api\Ynov\PasswordResetMail;
// use App\Models\Api\Ynov\parameter\ActivityLog;
// use App\Models\Api\Ynov\parameter\User;
// use App\Services\Api\Ynov\Auth\PasswordService;
// use Illuminate\Http\JsonResponse;
// use Illuminate\Support\Facades\DB;
// use Illuminate\Support\Facades\Hash;
// use Illuminate\Support\Facades\Mail;
// use Illuminate\Support\Str;

// class PasswordController extends Controller
// {
//     public function __construct(private PasswordService $passwordService) {}

//     public function forgot(ForgotPasswordRequest $request): JsonResponse
//     {
//         $user = User::where('email', $request->email)->first();

//         if (!$user) {
//             return response()->json(['success' => true, 'message' => 'Si cet email existe, un lien a été envoyé.']);
//         }

//         $token = Str::random(64);
//         DB::table('password_reset_tokens')->updateOrInsert(
//             ['email' => $user->email],
//             [
//                 'token' => Hash::make($token),
//                 'created_at' => now(),
//                 'expires_at' => now()->addMinutes(60),
//                 'ip_address' => $request->ip(),
//                 'user_agent' => $request->userAgent(),
//             ]
//         );

//         Mail::to($user->email)->queue(new PasswordResetMail($user->fresh('details'), $token, 60));

//         return response()->json(['success' => true, 'message' => 'Si cet email existe, un lien a été envoyé.']);
//     }

//     public function reset(ResetPasswordRequest $request): JsonResponse
//     {
//         $record = DB::table('password_reset_tokens')->where('email', $request->email)->first();
//         if (!$record || !Hash::check($request->token, $record->token) || now()->subHour()->gt($record->created_at)) {
//             return response()->json(['success' => false, 'message' => 'Token invalide ou expiré.'], 422);
//         }

//         $user = User::where('email', $request->email)->firstOrFail();
//         if (!$this->passwordService->validateHistory($user, $request->password)) {
//             return response()->json(['success' => false, 'message' => 'Vous ne pouvez pas réutiliser un ancien mot de passe.'], 422);
//         }

//         $user->update([
//             'password' => Hash::make($request->password),
//             'password_changed_at' => now(),
//             'password_expires_at' => now()->addDays(90),
//             'is_first_login' => false,
//         ]);

//         $this->passwordService->addToHistory($user, request()->ip(), request()->userAgent());
//         Mail::to($user->email)->queue(new PasswordChangedMail($user->fresh('details'), request()->ip()));
//         DB::table('password_reset_tokens')->where('email', $request->email)->delete();

//         return response()->json(['success' => true, 'message' => 'Mot de passe réinitialisé.']);
//     }

//     public function change(ChangePasswordRequest $request): JsonResponse
//     {
//         $user = $request->user();
//         if (!Hash::check($request->current_password, $user->password)) {
//             return response()->json(['success' => false, 'message' => 'Mot de passe actuel incorrect.'], 422);
//         }
//         if (!$this->passwordService->validateHistory($user, $request->password)) {
//             return response()->json(['success' => false, 'message' => 'Vous ne pouvez pas réutiliser un ancien mot de passe.'], 422);
//         }

//         $user->update([
//             'password' => Hash::make($request->password),
//             'password_changed_at' => now(),
//             'password_expires_at' => now()->addDays(90),
//             'is_first_login' => false,
//         ]);
//         $this->passwordService->addToHistory($user, request()->ip(), request()->userAgent());
//         Mail::to($user->email)->queue(new PasswordChangedMail($user->fresh('details'), request()->ip()));

//         ActivityLog::log([
//             'user_uuid' => $user->uuid_user,
//             'action' => 'password_change',
//             'action_type' => 'password_change',
//             'module' => 'auth',
//             'resource_type' => 'user',
//             'description' => "Changement de mot de passe par {$user->uuid_user}",
//             'resource_id' => $user->uuid_user,
//             'level' => 'info',
//         ]);

//         return response()->json(['success' => true, 'message' => 'Mot de passe changé avec succès.']);
//     }

//     /**
//      * Initialisation du mot de passe lors de la première connexion.
//      * Protégé par le middleware ability:password-change (voir routes).
//      * Garde-fou supplémentaire : refuse si l'utilisateur n'a en réalité
//      * pas besoin de changer son mot de passe (évite tout contournement
//      * si un token d'ability différente venait à être accepté).
//      */

//     public function firstLogin(FirstLoginPasswordRequest $request): JsonResponse
//     {
//         $user = $request->user();

//         if (!$user->is_first_login && !$this->passwordService->isExpired($user)) {
//             return response()->json([
//                 'success' => false,
//                 'code' => 'PASSWORD_CHANGE_NOT_REQUIRED',
//                 'message' => 'Le changement de mot de passe n\'est pas requis pour ce compte.',
//             ], 422);
//         }

//         if (!$this->passwordService->validateHistory($user, $request->password)) {
//             return response()->json(['success' => false, 'message' => 'Vous ne pouvez pas réutiliser un ancien mot de passe.'], 422);
//         }

//         $user->update([
//             'password' => Hash::make($request->password),
//             'password_changed_at' => now(),
//             'password_expires_at' => now()->addDays(90),
//             'is_first_login' => false,
//         ]);
//         $this->passwordService->addToHistory($user, request()->ip(), request()->userAgent());
//         Mail::to($user->email)->queue(new PasswordChangedMail($user->fresh('details'), request()->ip()));

//         // Défensif : currentAccessToken() peut être null selon le contexte du guard
//         $currentToken = $user->currentAccessToken();
//         if ($currentToken) {
//             $currentToken->delete();
//         }

//         $token = $user->createToken('API Token', ['*'], now()->addHours(24));

//         ActivityLog::log([
//             'user_uuid' => $user->uuid_user,
//             'action' => 'first_login',
//             'action_type' => 'first_login',
//             'module' => 'auth',
//             'description' => "Première connexion, mot de passe initialisé par {$user->uuid_user}",
//             'resource_type' => 'user',
//             'resource_id' => $user->uuid_user,
//             'level' => 'info',
//         ]);
//         return response()->json([
//             'success' => true,
//             'message' => 'Mot de passe initialisé.',
//             'data' => [
//                 'access_token' => $token->plainTextToken,
//                 'token_type' => 'Bearer',
//                 'expires_at' => now()->addHours(24),
//                 'user' => new UserResource($user->fresh('details')),
//             ],
//         ]);
//     }
// }

namespace App\Http\Controllers\Api\Ynov;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Ynov\ChangePasswordRequest;
use App\Http\Requests\Api\Ynov\FirstLoginPasswordRequest;
use App\Http\Requests\Api\Ynov\ForgotPasswordRequest;
use App\Http\Requests\Api\Ynov\ResetPasswordRequest;
use App\Http\Resources\Api\Ynov\UserResource;
use App\Mail\Api\Ynov\PasswordChangedMail;
use App\Mail\Api\Ynov\PasswordResetMail;
use App\Models\Api\Ynov\parameter\ActivityLog;
use App\Models\Api\Ynov\parameter\User;
use App\Services\Api\Ynov\Auth\PasswordService;
use App\Services\Api\Ynov\Auth\ThrottleService;
use App\Services\Api\Ynov\SecurityQuestionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class PasswordController extends Controller
{
    // public function __construct(private PasswordService $passwordService) {}
    public function __construct(
        private PasswordService $passwordService,
        private SecurityQuestionService $securityQuestionService,
        private ThrottleService $throttleService,
    ) {}

    /**
     * Demande de réinitialisation de mot de passe
     * 
     * CORRECTION #2 : Protection contre le timing attack
     * - On exécute toujours une opération coûteuse même si l'utilisateur n'existe pas
     * - Le message de retour est toujours le même
     */
    public function forgot(ForgotPasswordRequest $request): JsonResponse
    {
        // $user = User::where('email', $request->email)->first();
        $user = User::where('email', $request->login)->orwhere('login', $request->login)->first();

        
        // Générer un token même si l'utilisateur n'existe pas
        // pour uniformiser le temps de réponse
        $token = Str::random(64);
        
        if ($user) {
            Log::info('forgotddddd', [$user]);
            // L'utilisateur existe : on stocke le token
            DB::table('password_reset_tokens')->updateOrInsert(
                ['login' => $user->email ?? $user->login],
                [
                    'token' => Hash::make($token),
                    'created_at' => now(),
                    'expires_at' => now()->addMinutes(60),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]
            );



            if ($user->user_type == 'client') {
                $questions = $this->securityQuestionService->getQuestionsForUser($user);

                return response()->json([
                    'success' => true,
                    'data' => [
                        'token' => $token,
                        'user_uuid' => $user->uuid_user,
                        'has_configured' => $this->securityQuestionService->hasConfiguredQuestions($user),
                        'questions' => $questions,
                    ],
                ]);
            }

            Mail::to($user->email)->queue(new PasswordResetMail(
                $user->fresh('details'),
                $token,
                60
            ));
        } else {
            // ================================================================
            // CORRECTION #2 : Opération coûteuse factice pour uniformiser le temps
            // ================================================================
            // On simule un hash pour que le temps de réponse soit identique
            // à celui du cas où l'utilisateur existe
            Hash::make($token . Str::random(64));
            
            // On ne stocke rien en base, on n'envoie pas d'email
        }

        // ================================================================
        // MESSAGE UNIQUE : toujours le même, que l'utilisateur existe ou non
        // ================================================================
        return response()->json([
            'success' => true,
            'message' => 'Un lien a été envoyé vers votre adresse email pour réinitialiser votre mot de passe.'
        ]);
    }


    /**
     * Réinitialisation du mot de passe avec le token
     * 
     * CORRECTION #3 : Utilisation de expires_at au lieu de subHour()
     */
    public function reset(ResetPasswordRequest $request): JsonResponse
    {
        // ================================================================
        // CORRECTION #3 : Utilisation de expires_at
        // ================================================================
        $record = DB::table('password_reset_tokens')
            ->where('login', $request->email ?? $request->login)
            ->first();

        // Vérification du token avec Hash::check ET vérification de l'expiration
        if (!$record || !Hash::check($request->token, $record->token) || now()->gt($record->expires_at)) {
            return response()->json([
                'success' => false,
                'message' => 'Token invalide ou expiré.'
            ], 422);
        }

        $user = User::where('email', $request->email)->orWhere('login', $request->login)->firstOrFail();

        // Vérification de l'historique des mots de passe
        if (!$this->passwordService->validateHistory($user, $request->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez pas réutiliser un ancien mot de passe.'
            ], 422);
        }

        // Mise à jour du mot de passe
        $user->update([
            'password' => Hash::make($request->password),
            'password_changed_at' => now(),
            'password_expires_at' => now()->addDays(90),
            'is_first_login' => false,
        ]);

        // Ajout à l'historique
        $this->passwordService->addToHistory($user, request()->ip(), request()->userAgent());

        if ($user->email ) {
            // Email de confirmation
            Mail::to($user->email)->queue(new PasswordChangedMail(
                $user->fresh('details'),
                request()->ip()
            ));
        }

        // Suppression du token utilisé
        DB::table('password_reset_tokens')->where('login', $request->email ?? $request->login)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Mot de passe réinitialisé avec succès.'
        ]);
    }

    public function change(ChangePasswordRequest $request): JsonResponse
    {
        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Mot de passe actuel incorrect.'
            ], 422);
        }

        if (!$this->passwordService->validateHistory($user, $request->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez pas réutiliser un ancien mot de passe.'
            ], 422);
        }

        $user->update([
            'password' => Hash::make($request->password),
            'password_changed_at' => now(),
            'password_expires_at' => now()->addDays(90),
            'is_first_login' => false,
        ]);

        $this->passwordService->addToHistory($user, request()->ip(), request()->userAgent());
        if ($user->email) {
            Mail::to($user->email)->queue(new PasswordChangedMail($user->fresh('details'), request()->ip()));
        }
        // Mail::to($user->email)->queue(new PasswordChangedMail($user->fresh('details'), request()->ip()));

        ActivityLog::log([
            'user_uuid' => $user->uuid_user,
            'action' => 'password_change',
            'action_type' => 'password_change',
            'module' => 'auth',
            'resource_type' => 'user',
            'description' => "Changement de mot de passe par {$user->uuid_user}",
            'resource_id' => $user->uuid_user,
            'level' => 'info',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Mot de passe changé avec succès.'
        ]);
    }


    public function firstLogin(FirstLoginPasswordRequest $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->is_first_login && !$this->passwordService->isExpired($user)) {
            return response()->json([
                'success' => false,
                'code' => 'PASSWORD_CHANGE_NOT_REQUIRED',
                'message' => 'Le changement de mot de passe n\'est pas requis pour ce compte.',
            ], 422);
        }

        if (!$this->passwordService->validateHistory($user, $request->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez pas réutiliser un ancien mot de passe.'
            ], 422);
        }

        $user->update([
            'password' => Hash::make($request->password),
            'password_changed_at' => now(),
            'password_expires_at' => now()->addDays(90),
            'is_first_login' => false,
        ]);

        $this->passwordService->addToHistory($user, request()->ip(), request()->userAgent());
        if ($user->email) {
            Mail::to($user->email)->queue(new PasswordChangedMail($user->fresh('details'), request()->ip()));
        }

        // Supprimer le token temporaire
        $currentToken = $user->currentAccessToken();
        if ($currentToken) {
            $currentToken->delete();
        }

        // Créer un nouveau token permanent
        $token = $user->createToken('API Token', ['*'], now()->addHours(24));

        ActivityLog::log([
            'user_uuid' => $user->uuid_user,
            'action' => 'first_login',
            'action_type' => 'first_login',
            'module' => 'auth',
            'description' => "Première connexion, mot de passe initialisé par {$user->uuid_user}",
            'resource_type' => 'user',
            'resource_id' => $user->uuid_user,
            'level' => 'info',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Mot de passe initialisé.',
            'data' => [
                'access_token' => $token->plainTextToken,
                'token_type' => 'Bearer',
                'expires_at' => now()->addHours(24),
                'user' => new UserResource($user->fresh('details')),
            ],
        ]);
    }
}
