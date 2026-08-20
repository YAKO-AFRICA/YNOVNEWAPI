<?php

namespace App\Http\Middleware\Api\Ynov;

use App\Services\Api\Ynov\Auth\PasswordService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPasswordExpiration
{
    public function __construct(
        private readonly PasswordService $passwordService
    ) {
    }

    public function handle(
        Request $request,
        Closure $next
    ): Response {
        /**
         * Routes accessibles pour permettre à l'utilisateur
         * de résoudre son problème de mot de passe.
         */
        if ($request->is(
            'api/v1/auth/change-password',
            'api/v1/auth/first-login'
        )) {
            return $next($request);
        }

        /**
         * L'utilisateur doit normalement déjà avoir été chargé
         * par auth:sanctum.
         */
        $user = $request->user();

        /**
         * Aucun utilisateur : laisser auth:sanctum gérer le 401
         * si cette route nécessite une authentification.
         */
        if (!$user) {
            return $next($request);
        }

        /**
         * Première connexion : le changement du mot de passe
         * doit être effectué avant d'accéder aux autres ressources.
         */
        if ($user->is_first_login) {
            return response()->json([
                'success' => false,
                'code' => 'FIRST_LOGIN_PASSWORD_CHANGE_REQUIRED',
                'message' => 'Vous devez définir un nouveau mot de passe avant de continuer.',
            ], 403);
        }

        /**
         * Mot de passe expiré.
         */
        if ($this->passwordService->isExpired($user)) {
            return response()->json([
                'success' => false,
                'code' => 'PASSWORD_EXPIRED',
                'message' => 'Votre mot de passe a expiré. Veuillez le changer.',
            ], 403);
        }

        return $next($request);
    }
}