<?php
namespace App\Http\Middleware\Api\Ynov;

use App\Models\Api\Ynov\parameter\ActivityLog;
use Closure;
use Illuminate\Http\Request;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permissionCode)
    {
        $user = $request->user();

        if (!$user || !$user->role) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé.',
                'code' => 'PERMISSION_DENIED',
            ], 403);
        }

        if (!$user->hasPermission($permissionCode)) {
            ActivityLog::log([
                'user_uuid' => $user->uuid_user,
                'action' => 'permission_denied',
                'action_type' => 'security',
                'module' => 'auth',
                'description' => "Tentative sans permission : {$permissionCode}",
                'level' => 'warning',
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Permission requise manquante.',
                'code' => 'PERMISSION_DENIED',
            ], 403);
        }

        return $next($request);
    }
}