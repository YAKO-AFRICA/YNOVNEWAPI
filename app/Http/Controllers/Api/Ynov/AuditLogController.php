<?php

namespace App\Http\Controllers\Api\Ynov;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Ynov\AccountFreezeResource;
use App\Http\Resources\Api\Ynov\ActivityLogResource;
use App\Models\Api\Ynov\parameter\User;
use App\Services\Api\Ynov\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditLogController extends Controller
{
    public function __construct(private readonly AuditLogService $auditLogService)
    {
    }

    public function getMyActivityLogs(Request $request)
    {
        $filters = $this->validateActivityFilters($request);
        $user = $request->user();
        $logs = $this->auditLogService->getMyActivityLogs(
            $user->uuid_user,
            $filters,
            $filters['per_page'] ?? 20
        );

        return ActivityLogResource::collection($logs);
    }

    public function getUserActivityLogs(Request $request, string $uuid_user)
    {
        $targetUser = User::withTrashed()->find($uuid_user);

        if ($targetUser) {
            Gate::authorize('view-activity-logs', $targetUser);
        } else {
            Gate::authorize('view-all-activity-logs');
        }

        $filters = $this->validateActivityFilters($request);
        $logs = $this->auditLogService->getUserActivityLogs(
            $uuid_user,
            $filters,
            $filters['per_page'] ?? 20
        );

        return ActivityLogResource::collection($logs);
    }

    public function getAllActivityLogs(Request $request)
    {
        Gate::authorize('view-all-activity-logs');

        $filters = $this->validateActivityFilters($request, true);
        $logs = $this->auditLogService->getAllActivityLogs(
            $filters,
            $filters['per_page'] ?? 50
        );

        return ActivityLogResource::collection($logs);
    }

    public function getFreezeLogs(Request $request)
    {
        Gate::authorize('view-all-activity-logs');

        $filters = $request->validate([
            'user_uuid' => ['sometimes', 'uuid', 'exists:users,uuid_user'],
            'freeze_level' => ['sometimes', 'integer', 'min:0', 'max:10'],
            'status' => ['sometimes', 'string', 'in:active,released'],
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['sometimes', 'date', 'after_or_equal:start_date'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ]);

        $logs = $this->auditLogService->getFreezeLogs($filters, $filters['per_page'] ?? 20);

        return AccountFreezeResource::collection($logs);
    }

    public function getMyActivityStats(Request $request): JsonResponse
    {
        $filters = $this->validateActivityFilters($request);
        $stats = $this->auditLogService->getActivityStats($filters, $request->user()->uuid_user);

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    public function getActivityStats(Request $request): JsonResponse
    {
        Gate::authorize('view-all-activity-logs');

        $filters = $this->validateActivityFilters($request, true);
        $stats = $this->auditLogService->getActivityStats($filters);

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    public function getFilterOptions(Request $request): JsonResponse
    {
        Gate::authorize('view-all-activity-logs');

        return response()->json([
            'success' => true,
            'data' => $this->auditLogService->getFilterOptions(),
        ]);
    }

    public function exportActivityLogs(Request $request): StreamedResponse
    {
        Gate::authorize('export-audit-logs');
        $filters = $this->validateActivityFilters($request, true);
        $query = $this->auditLogService->activityQuery($filters)->with('user:uuid_user,login,email');

        return response()->streamDownload(function () use ($query): void {
            $output = fopen('php://output', 'w');
            fputcsv($output, [
                'uuid_activity_log',
                'created_at',
                'user_uuid',
                'user_login',
                'user_email',
                'action',
                'action_type',
                'module',
                'level',
                'description',
                'resource_type',
                'resource_id',
                'ip_address',
            ]);

            $query->orderByDesc('created_at')->chunk(500, function ($logs) use ($output): void {
                foreach ($logs as $log) {
                    fputcsv($output, [
                        $log->uuid_activity_log,
                        $log->created_at?->toISOString(),
                        $log->user_uuid,
                        $log->user?->login,
                        $log->user?->email,
                        $log->action,
                        $log->action_type,
                        $log->module,
                        $log->level,
                        $log->description,
                        $log->resource_type,
                        $log->resource_id,
                        $log->ip_address,
                    ]);
                }
            });

            fclose($output);
        }, 'activity-logs-' . now()->format('Ymd-His') . '.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function validateActivityFilters(Request $request, bool $includeUser = false): array
    {
        $rules = [
            'action' => ['sometimes', 'string', 'max:100'],
            'action_type' => ['sometimes', 'string', 'max:100'],
            'module' => ['sometimes', 'string', 'max:100'],
            'level' => ['sometimes', 'string', 'max:30'],
            'resource_type' => ['sometimes', 'string', 'max:100'],
            'resource_id' => ['sometimes', 'string', 'max:255'],
            'ip_address' => ['sometimes', 'ip'],
            'search' => ['sometimes', 'string', 'max:255'],
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['sometimes', 'date', 'after_or_equal:start_date'],
            'sort_by' => ['sometimes', 'string', 'in:created_at,action,action_type,module,level'],
            'sort_order' => ['sometimes', 'string', 'in:asc,desc'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];

        if ($includeUser) {
            $rules['user_uuid'] = ['sometimes', 'uuid', 'exists:users,uuid_user'];
        }

        return $request->validate($rules);
    }
}
