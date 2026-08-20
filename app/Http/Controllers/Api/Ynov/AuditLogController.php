<?php

namespace App\Http\Controllers\Api\Ynov;

use App\Http\Controllers\Controller;
use App\Models\Api\Ynov\parameter\ActivityLog;
use App\Models\Api\Ynov\parameter\AccountFreeze;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    /**
     * Récupérer les logs d'activité de l'utilisateur connecté
     */
    public function getMyActivityLogs(Request $request): JsonResponse
    {
        $user = $request->user();

        $logs = ActivityLog::where('user_uuid', $user->uuid_user)
            // ->
            ->orderBy('created_at', 'desc')
            ->paginate($request->integer('per_page', 10));

        return response()->json([
            'success' => true,
            'data' => $logs,
        ]);
    }

    /**
     * Admin : Récupérer les logs d'activité d'un utilisateur spécifique
     */
    public function getUserActivityLogs(Request $request, string $uuid_user): JsonResponse
    {

        // Vérification d'autorisation
        if (!$request->user()->can('view-activity-logs', $request->user())) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'avez pas le droit de consulter ces logs.',
            ], 403);
        }

        $logs = ActivityLog::where('user_uuid', $uuid_user)
            ->orderBy('created_at', 'desc')
            ->paginate($request->integer('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $logs,
        ]);
    }

    /**
     * Admin : Récupérer tous les logs d'activité (filtrés)
     */
    public function getAllActivityLogs(Request $request): JsonResponse
    {
        $query = ActivityLog::with('user')
            ->orderBy('created_at', 'desc');

        // Filtres
        if ($request->action) {
            $query->action($request->action);
        }

        if ($request->level) {
            $query->level($request->level);
        }

        if ($request->module) {
            $query->module($request->module);
        }

        if ($request->user_uuid) {
            $query->where('user_uuid', $request->user_uuid);
        }

        if ($request->start_date) {
            $query->where('created_at', '>=', $request->start_date);
        }

        if ($request->end_date) {
            $query->where('created_at', '<=', $request->end_date);
        }

        $logs = $query->paginate($request->integer('per_page', 50));

        return response()->json([
            'success' => true,
            'data' => $logs,
        ]);
    }

    /**
     * Admin : Récupérer les logs de gel/dégel
     */
    public function getFreezeLogs(Request $request): JsonResponse
    {
        $logs = AccountFreeze::with(['user', 'unfrozenBy'])
            ->orderBy('frozen_at', 'desc')
            ->paginate($request->integer('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $logs,
        ]);
    }

    /**
     * Récupérer les statistiques d'activité
     */
    public function getActivityStats(Request $request): JsonResponse
    {
        $stats = [
            'today' => ActivityLog::whereDate('created_at', now()->toDateString())->count(),
            'this_week' => ActivityLog::whereBetween('created_at', [
                now()->startOfWeek(),
                now()->endOfWeek(),
            ])->count(),
            'this_month' => ActivityLog::whereBetween('created_at', [
                now()->startOfMonth(),
                now()->endOfMonth(),
            ])->count(),
            'by_action' => ActivityLog::selectRaw('action, COUNT(*) as count')
                ->groupBy('action')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->get(),
            'by_level' => ActivityLog::selectRaw('level, COUNT(*) as count')
                ->groupBy('level')
                ->get(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}