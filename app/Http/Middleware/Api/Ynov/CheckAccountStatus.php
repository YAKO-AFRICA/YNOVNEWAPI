<?php
namespace App\Http\Middleware\Api\Ynov;

use Closure;
use Illuminate\Http\Request;

class CheckAccountStatus
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user->status === 'gele' || ($user->frozen_until && now()->lt($user->frozen_until))) {
            return response()->json([
                'success' => false,
                'code' => 'ACCOUNT_FROZEN',
                'message' => 'Compte temporairement gelé.',
            ], 403);
        }

        if ($user->status === 'bloque' || $user->is_locked) {
            return response()->json([
                'success' => false,
                'code' => 'ACCOUNT_BLOCKED',
                'message' => 'Compte bloqué. Contactez votre administrateur.',
            ], 403);
        }

        if ($user->status === 'suspendu') {
            return response()->json([
                'success' => false,
                'code' => 'ACCOUNT_SUSPENDED',
                'message' => 'Compte suspendu. Contactez votre administrateur.',
            ], 403);
        }

        if ($user->status === 'inactif') {
            return response()->json([
                'success' => false,
                'code' => 'ACCOUNT_INACTIVE',
                'message' => 'Compte Desactivé. Contactez votre administrateur.',
            ], 403);
        }

        return $next($request);
    }
}