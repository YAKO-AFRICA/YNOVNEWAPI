<?php

namespace App\Services\Api\Ynov;

use App\Models\Api\Ynov\parameter\AccountFreeze;
use App\Models\Api\Ynov\parameter\ActivityLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class AuditLogService
{
    private const SORT_COLUMNS = ['created_at', 'action', 'action_type', 'module', 'level'];

    public function getMyActivityLogs(string $userUuid, array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->activityQuery($filters, $userUuid)
            ->with('user:uuid_user,login,email')
            ->orderBy($this->sortColumn($filters), $this->sortDirection($filters))
            ->paginate($perPage)
            ->withQueryString();
    }

    public function getUserActivityLogs(string $userUuid, array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->activityQuery($filters, $userUuid)
            ->with('user:uuid_user,login,email')
            ->orderBy($this->sortColumn($filters), $this->sortDirection($filters))
            ->paginate($perPage)
            ->withQueryString();
    }

    public function getAllActivityLogs(array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->activityQuery($filters)
            ->with('user:uuid_user,login,email')
            ->orderBy($this->sortColumn($filters), $this->sortDirection($filters))
            ->paginate($perPage)
            ->withQueryString();
    }

    public function getFreezeLogs(array $filters, int $perPage): LengthAwarePaginator
    {
        $query = AccountFreeze::query()
            ->with([
                'user:uuid_user,login,email,frozen_until',
                'unfrozenBy:uuid_user,login,email',
            ]);

        if (!empty($filters['user_uuid'])) {
            $query->where('user_uuid', $filters['user_uuid']);
        }

        if (isset($filters['freeze_level'])) {
            $query->where('freeze_level', $filters['freeze_level']);
        }

        if (($filters['status'] ?? null) === 'active') {
            $query->whereNull('unfrozen_at');
        } elseif (($filters['status'] ?? null) === 'released') {
            $query->whereNotNull('unfrozen_at');
        }

        if (!empty($filters['start_date'])) {
            $query->whereDate('frozen_at', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->whereDate('frozen_at', '<=', $filters['end_date']);
        }

        return $query->orderBy('frozen_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function getActivityStats(array $filters = [], ?string $userUuid = null): array
    {
        $baseQuery = $this->activityQuery($filters, $userUuid);
        $today = now()->toDateString();

        return [
            'total' => (clone $baseQuery)->count(),
            'today' => (clone $baseQuery)->whereDate('created_at', $today)->count(),
            'this_week' => (clone $baseQuery)->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'this_month' => (clone $baseQuery)->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->count(),
            'by_action' => (clone $baseQuery)
                ->selectRaw('action, COUNT(*) as count')
                ->groupBy('action')
                ->orderByDesc('count')
                ->limit(20)
                ->get(),
            'by_action_type' => (clone $baseQuery)
                ->selectRaw('action_type, COUNT(*) as count')
                ->groupBy('action_type')
                ->orderByDesc('count')
                ->limit(20)
                ->get(),
            'by_module' => (clone $baseQuery)
                ->selectRaw('module, COUNT(*) as count')
                ->groupBy('module')
                ->orderByDesc('count')
                ->limit(20)
                ->get(),
            'by_level' => (clone $baseQuery)
                ->selectRaw('level, COUNT(*) as count')
                ->groupBy('level')
                ->orderByDesc('count')
                ->get(),
        ];
    }

    public function getFilterOptions(): array
    {
        return [
            'actions' => ActivityLog::query()->whereNotNull('action')->distinct()->orderBy('action')->pluck('action'),
            'action_types' => ActivityLog::query()->whereNotNull('action_type')->distinct()->orderBy('action_type')->pluck('action_type'),
            'modules' => ActivityLog::query()->whereNotNull('module')->distinct()->orderBy('module')->pluck('module'),
            'levels' => ActivityLog::query()->whereNotNull('level')->distinct()->orderBy('level')->pluck('level'),
            'resource_types' => ActivityLog::query()->whereNotNull('resource_type')->distinct()->orderBy('resource_type')->pluck('resource_type'),
            'freeze_statuses' => ['active', 'released'],
        ];
    }

    public function activityQuery(array $filters = [], ?string $userUuid = null): Builder
    {
        $query = ActivityLog::query();

        if ($userUuid !== null) {
            $query->where('user_uuid', $userUuid);
        } elseif (!empty($filters['user_uuid'])) {
            $query->where('user_uuid', $filters['user_uuid']);
        }

        foreach (['action', 'action_type', 'module', 'level', 'resource_type', 'resource_id', 'ip_address'] as $field) {
            if (!empty($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }

        if (!empty($filters['start_date'])) {
            $query->whereDate('created_at', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->whereDate('created_at', '<=', $filters['end_date']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $nested) use ($search): void {
                $nested->where('description', 'LIKE', "%{$search}%")
                    ->orWhere('resource_id', 'LIKE', "%{$search}%")
                    ->orWhere('resource_type', 'LIKE', "%{$search}%")
                    ->orWhereHas('user', function (Builder $userQuery) use ($search): void {
                        $userQuery->where('login', 'LIKE', "%{$search}%")
                            ->orWhere('email', 'LIKE', "%{$search}%");
                    });
            });
        }

        return $query;
    }

    private function sortColumn(array $filters): string
    {
        $sortBy = $filters['sort_by'] ?? 'created_at';

        return in_array($sortBy, self::SORT_COLUMNS, true) ? $sortBy : 'created_at';
    }

    private function sortDirection(array $filters): string
    {
        return ($filters['sort_order'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
    }
}
