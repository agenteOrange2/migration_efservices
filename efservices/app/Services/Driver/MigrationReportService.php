<?php

namespace App\Services\Driver;

use App\Models\MigrationRecord;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Carbon\Carbon;

/**
 * Service for generating migration reports and statistics.
 */
class MigrationReportService
{
    /**
     * Get migrations with filters.
     */
    public function getMigrations(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = MigrationRecord::with([
            'sourceCarrier',
            'targetCarrier',
            'driverUser',
            'migratedByUser',
        ]);

        $this->applyFilters($query, $filters);

        return $query->orderBy('migrated_at', 'desc')->paginate($perPage);
    }

    /**
     * Get all migrations for export (no pagination).
     */
    public function getMigrationsForExport(array $filters = []): Collection
    {
        $query = MigrationRecord::with([
            'sourceCarrier',
            'targetCarrier',
            'driverUser',
            'migratedByUser',
            'rolledBackByUser',
        ]);

        $this->applyFilters($query, $filters);

        return $query->orderBy('migrated_at', 'desc')->get();
    }

    /**
     * Apply filters to the query.
     */
    protected function applyFilters(Builder $query, array $filters): void
    {
        // Date range filter
        if (!empty($filters['date_from'])) {
            $query->where('migrated_at', '>=', Carbon::parse($filters['date_from'])->startOfDay());
        }

        if (!empty($filters['date_to'])) {
            $query->where('migrated_at', '<=', Carbon::parse($filters['date_to'])->endOfDay());
        }

        // Source carrier filter
        if (!empty($filters['source_carrier_id'])) {
            $query->where('source_carrier_id', $filters['source_carrier_id']);
        }

        // Target carrier filter
        if (!empty($filters['target_carrier_id'])) {
            $query->where('target_carrier_id', $filters['target_carrier_id']);
        }

        // Driver filter
        if (!empty($filters['driver_user_id'])) {
            $query->where('driver_user_id', $filters['driver_user_id']);
        }

        // Status filter
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Search by driver name
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('driverUser', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }
    }

    /**
     * Get migration statistics.
     */
    public function getMigrationStatistics(array $filters = []): array
    {
        $baseQuery = MigrationRecord::query();
        $this->applyFilters($baseQuery, $filters);

        $total = (clone $baseQuery)->count();
        $completed = (clone $baseQuery)->where('status', 'completed')->count();
        $rolledBack = (clone $baseQuery)->where('status', 'rolled_back')->count();

        // Migrations by month (last 12 months)
        $monthlyData = $this->getMonthlyMigrations($filters);

        // Top source carriers
        $topSourceCarriers = (clone $baseQuery)
            ->selectRaw('source_carrier_id, COUNT(*) as count')
            ->groupBy('source_carrier_id')
            ->orderByDesc('count')
            ->limit(5)
            ->with('sourceCarrier')
            ->get()
            ->map(fn($item) => [
                'carrier' => $item->sourceCarrier->name ?? 'Unknown',
                'count' => $item->count,
            ]);

        // Top target carriers
        $topTargetCarriers = (clone $baseQuery)
            ->selectRaw('target_carrier_id, COUNT(*) as count')
            ->groupBy('target_carrier_id')
            ->orderByDesc('count')
            ->limit(5)
            ->with('targetCarrier')
            ->get()
            ->map(fn($item) => [
                'carrier' => $item->targetCarrier->name ?? 'Unknown',
                'count' => $item->count,
            ]);

        // Average time to rollback (for rolled back migrations)
        $avgRollbackHours = MigrationRecord::where('status', 'rolled_back')
            ->whereNotNull('rolled_back_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, migrated_at, rolled_back_at)) as avg_hours')
            ->value('avg_hours');

        return [
            'total_migrations' => $total,
            'completed_migrations' => $completed,
            'rolled_back_migrations' => $rolledBack,
            'rollback_rate' => $total > 0 ? round(($rolledBack / $total) * 100, 1) : 0,
            'monthly_data' => $monthlyData,
            'top_source_carriers' => $topSourceCarriers,
            'top_target_carriers' => $topTargetCarriers,
            'avg_rollback_hours' => $avgRollbackHours ? round($avgRollbackHours, 1) : null,
        ];
    }

    /**
     * Get monthly migration counts for the last 12 months.
     */
    protected function getMonthlyMigrations(array $filters = []): array
    {
        $months = [];
        $startDate = Carbon::now()->subMonths(11)->startOfMonth();

        for ($i = 0; $i < 12; $i++) {
            $monthStart = $startDate->copy()->addMonths($i);
            $monthEnd = $monthStart->copy()->endOfMonth();

            $query = MigrationRecord::whereBetween('migrated_at', [$monthStart, $monthEnd]);
            
            // Apply carrier filters if present
            if (!empty($filters['source_carrier_id'])) {
                $query->where('source_carrier_id', $filters['source_carrier_id']);
            }
            if (!empty($filters['target_carrier_id'])) {
                $query->where('target_carrier_id', $filters['target_carrier_id']);
            }

            $months[] = [
                'month' => $monthStart->format('M Y'),
                'count' => $query->count(),
            ];
        }

        return $months;
    }

    /**
     * Get migration reasons breakdown.
     */
    public function getMigrationReasons(array $filters = []): Collection
    {
        $query = MigrationRecord::query();
        $this->applyFilters($query, $filters);

        return $query
            ->selectRaw('reason, COUNT(*) as count')
            ->groupBy('reason')
            ->orderByDesc('count')
            ->get()
            ->map(fn($item) => [
                'reason' => $item->reason ?? 'Not specified',
                'count' => $item->count,
            ]);
    }
}
