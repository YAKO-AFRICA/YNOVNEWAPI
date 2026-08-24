<?php

namespace App\Http\Controllers\Api\Ynov;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Ynov\ChangePasswordRequest;
use App\Http\Requests\Api\Ynov\FirstLoginPasswordRequest;
use App\Http\Requests\Api\Ynov\ForgotPasswordRequest;
use App\Http\Requests\Api\Ynov\ResetPasswordRequest;
use App\Http\Resources\Api\Ynov\UserResource;
use App\Mail\Api\Ynov\PasswordChangedMail;
// use App\Mail\Api\Ynov\PasswordResetMail;
use App\Models\Api\Ynov\parameter\ActivityLog;
use App\Models\Api\Ynov\parameter\User;
use App\Services\Api\Ynov\Auth\OtpService;
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
        private OtpService $otpService
    ) {}

    public function forgot(ForgotPasswordRequest $request): JsonResponse
    {
        $login = $request->input('login');
        $option = $request->input('option');

        /*
        * Recherche de l'utilisateur.
        */
        $user = User::where('login', $login)->first();

        /*
        * Si l'utilisateur n'existe pas :
        * on retourne exactement la même réponse.
        */
        if (!$user) {

            // Petit travail cryptographique pour limiter
            // la différence de temps de réponse.
            Hash::make(
                Str::random(64) . Str::random(32)
            );

            return response()->json(
                [
                    'success' => false,
                    'message' => 'Utilisateur introuvable.',
                ]);
        }

        /*
        * Génération du token de réinitialisation.
        */
        $token = Str::random(64);

        /*
        * Stockage du token dans password_reset_tokens.
        *
        * Le token est hashé en base.
        */
        DB::table('password_reset_tokens')->updateOrInsert(
            [
                'login' => $user->login,
            ],
            [
                'token' => Hash::make($token),
                'created_at' => now(),
                'expires_at' => now()->addMinutes(60),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]
        );

        /*
        * CAS 1 : QUESTIONS SECRÈTES
        */
        if (
            $user->user_type === 'client'
            && $option === 'question_secrete'
        ) {

            $hasConfiguredQuestions =
                $this->securityQuestionService
                    ->hasConfiguredQuestions($user);

            $questions =
                $this->securityQuestionService
                    ->getQuestionsForUser($user);

            return response()->json([
                'success' => true,

                'message' =>
                    'Veuillez répondre aux questions de sécurité.',

                'data' => [
                    'token' => $token,
                    'user_uuid' => $user->uuid_user,

                    'method' => 'question_secrete',

                    'has_configured' => $hasConfiguredQuestions,

                    'questions' => $questions,
                ],
            ], 200);
        }

        /*
        * CAS 2 : OTP
        */
        $result = $this->otpService->sendOtp(
            user: $user,
            channel: $option,
            purpose: 'reset',
            ip: $request->ip(),
            ua: $request->userAgent(),
            expiryMinutes: 5,
            data: $request->all()
        );

        /*
        * Échec de l'envoi.
        */
        if (!$result['success']) {

            // Log::warning(
            //     'Échec envoi OTP réinitialisation',
            //     [
            //         'user_uuid' => $user->uuid_user,
            //         'channel' => $option,
            //         'code' => $result['code'],
            //     ]
            // );

            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'code' => $result['code'],
            ], 422);
        }

        /*
        * Succès.
        */
        return response()->json([
            'success' => true,

            'message' =>
                'Un code de vérification a été envoyé '
                . 'via le canal sélectionné.',

            'data' => [
                'token' => $token,
                'user_uuid' => $user->uuid_user,

                'method' => $option,

                'expires_in' => 5,
            ],
        ], 200);
    }

    // if ($user->email) {
                
            //     Mail::to($user->email)->queue(new PasswordResetMail(
            //         $user->fresh('details'),
            //         $token,
            //         60
            //     ));
            // }

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
            ->where('login', $request->login)
            ->first();

        // Vérification du token avec Hash::check ET vérification de l'expiration
        if (!$record || !Hash::check($request->token, $record->token) || now()->gt($record->expires_at)) {
            return response()->json([
                'success' => false,
                'message' => 'Token invalide ou expiré.'
            ], 422);
        }

        $user = User::where('login', $request->login)->firstOrFail();

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
        DB::table('password_reset_tokens')->where('login', $request->login)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Mot de passe réinitialisé avec succès.'
        ], 200);
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
        ], 200);
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
        ], 200);
    }
}
