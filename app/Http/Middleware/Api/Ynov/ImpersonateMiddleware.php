<?php

namespace App\Http\Middleware\Api\Ynov;

use App\Models\Api\Ynov\parameter\ActivityLog;
use App\Models\Api\Ynov\parameter\User;
use Closure;
use Illuminate\Http\Request;

class ImpersonateMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $impersonatorId = $request->header('X-Impersonator-Id');
        $targetUserId = $request->header('X-Target-User-Id');

        if ($impersonatorId && $targetUserId) {
            // Vérifier que l'impersonateur a le droit
            $impersonator = User::where('uuid_user', $impersonatorId)->first();
            
            if (!$impersonator || !$impersonator->can('impersonate')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous n\'avez pas le droit d\'impersonner cet utilisateur.',
                ], 403);
            }

            // Charger l'utilisateur cible
            $targetUser = User::where('uuid_user', $targetUserId)->first();
            
            if (!$targetUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur cible non trouvé.',
                ], 404);
            }

            // Authentifier l'utilisateur cible
            auth()->setUser($targetUser);

            // Journaliser l'impersonnation
            ActivityLog::log([
                'user_uuid' => $targetUser->uuid_user,
                'action' => 'impersonate_start',
                'action_type' => 'security',
                'module' => 'auth',
                'description' => "Impersonnation de {$targetUser->email} par {$impersonator->email}",
                'level' => 'warning',
                'metadata' => [
                    'impersonator_uuid' => $impersonatorId,
                    'target_uuid' => $targetUserId,
                ],
            ]);
        }

        return $next($request);
    }
}