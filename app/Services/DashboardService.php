<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Vacation;
use App\Models\InputVoucher;
use App\Models\OutputVoucher;
use App\Models\Item;
use App\Models\Archive;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DashboardService
{
    protected CacheService $cacheService;

    public function __construct(CacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * Get complete dashboard overview.
     *
     * @return array
     */
    public function getOverview(): array
    {
        try {
            return $this->cacheService->remember(
                'dashboard.overview',
                CacheService::DURATION_SHORT,
                function () {
                    return [
                        'employees' => $this->getEmployeeStats(),
                        'vacations' => $this->getVacationStats(),
                        'stock' => $this->getStockStats(),
                        'system' => $this->getSystemStats(),
                    ];
                },
                CacheService::CATEGORY_DASHBOARD
            );
        } catch (\Exception $e) {
            Log::error('DashboardService::getOverview failed: ' . $e->getMessage());

            // Fallback to empty structure
            return [
                'employees' => ['total' => 0, 'active' => 0, 'inactive' => 0],
                'vacations' => ['total_employees_with_vacation' => 0, 'total_vacation_days' => 0],
                'stock' => ['total_items' => 0, 'low_stock_items' => 0],
                'system' => ['total_users' => 0, 'active_users' => 0],
            ];
        }
    }

    /**
     * Get employee statistics.
     *
     * @return array
     */
    public function getEmployeeStats(): array
    {
        try {
            return $this->cacheService->remember(
                'dashboard.employees.stats',
                CacheService::DURATION_NORMAL,
                function () {
                    $total = Employee::where('is_person', true)->count();
                    $active = Employee::where('is_person', true)
                        ->whereHas('User', function ($query) {
                            $query->where('active', true);
                        })->count();

                    return [
                        'total' => $total,
                        'active' => $active,
                        'inactive' => $total - $active,
                        'by_type' => $this->getEmployeesByType(),
                        'by_section' => $this->getEmployeesBySection(),
                    ];
                },
                CacheService::CATEGORY_DASHBOARD
            );
        } catch (\Exception $e) {
            Log::error('DashboardService::getEmployeeStats failed: ' . $e->getMessage());
            return ['total' => 0, 'active' => 0, 'inactive' => 0, 'by_type' => [], 'by_section' => []];
        }
    }

    /**
     * Get employees grouped by type.
     *
     * @return array
     */
    public function getEmployeesByType(): array
    {
        try {
            return $this->cacheService->remember(
                'dashboard.employees.by_type',
                CacheService::DURATION_NORMAL,
                function () {
                    return Employee::where('is_person', true)
                        ->select('employee_type_id', DB::raw('count(*) as count'))
                        ->with('EmployeeType:id,name')
                        ->groupBy('employee_type_id')
                        ->get()
                        ->map(function ($item) {
                            return [
                                'label' => $item->EmployeeType->name ?? 'غير محدد',
                                'value' => $item->count,
                            ];
                        })
                        ->toArray();
                },
                CacheService::CATEGORY_DASHBOARD
            );
        } catch (\Exception $e) {
            Log::error('DashboardService::getEmployeesByType failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get employees grouped by section.
     *
     * @return array
     */
    public function getEmployeesBySection(): array
    {
        try {
            return $this->cacheService->remember(
                'dashboard.employees.by_section',
                CacheService::DURATION_NORMAL,
                function () {
                    return Employee::where('is_person', true)
                        ->select('section_id', DB::raw('count(*) as count'))
                        ->with('Section:id,name')
                        ->groupBy('section_id')
                        ->orderBy('count', 'desc')
                        ->limit(10)
                        ->get()
                        ->map(function ($item) {
                            return [
                                'label' => $item->Section->name ?? 'غير محدد',
                                'value' => $item->count,
                            ];
                        })
                        ->toArray();
                },
                CacheService::CATEGORY_DASHBOARD
            );
        } catch (\Exception $e) {
            Log::error('DashboardService::getEmployeesBySection failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get vacation statistics.
     *
     * @return array
     */
    public function getVacationStats(): array
    {
        try {
            return $this->cacheService->remember(
                'dashboard.vacations.stats',
                CacheService::DURATION_SHORT,
                function () {
                    $totalVacations = Vacation::count();

                    // Sum of all vacation records
                    $totalDays = Vacation::sum('record');
                    $totalSickDays = Vacation::sum('record_sick');

                    return [
                        'total_employees_with_vacation' => $totalVacations,
                        'total_vacation_days' => $totalDays,
                        'total_sick_days' => $totalSickDays,
                        'average_per_employee' => $totalVacations > 0 ? round($totalDays / $totalVacations, 2) : 0,
                    ];
                },
                CacheService::CATEGORY_DASHBOARD
            );
        } catch (\Exception $e) {
            Log::error('DashboardService::getVacationStats failed: ' . $e->getMessage());
            return [
                'total_employees_with_vacation' => 0,
                'total_vacation_days' => 0,
                'total_sick_days' => 0,
                'average_per_employee' => 0,
            ];
        }
    }

    /**
     * Get vacation trends for the specified period.
     *
     * @param string $period ('week', 'month', 'year')
     * @return array
     */
    public function getVacationTrends(string $period = 'month'): array
    {
        try {
            // Validate period
            $validPeriods = ['week', 'month', 'year'];
            if (!in_array($period, $validPeriods)) {
                $period = 'month';
            }

            $cacheKey = "dashboard.vacations.trends.{$period}";

            return $this->cacheService->remember(
                $cacheKey,
                CacheService::DURATION_NORMAL,
                function () use ($period) {
                    $dateColumn = 'created_at';
                    $groupFormat = match ($period) {
                        'week' => '%Y-%m-%d',
                        'month' => '%Y-%m',
                        'year' => '%Y',
                        default => '%Y-%m',
                    };

                    $startDate = match ($period) {
                        'week' => now()->subWeek(),
                        'month' => now()->subMonth(),
                        'year' => now()->subYear(),
                        default => now()->subMonth(),
                    };

                    return Vacation::where($dateColumn, '>=', $startDate)
                        ->select(
                            DB::raw("DATE_FORMAT({$dateColumn}, '{$groupFormat}') as date"),
                            DB::raw('count(*) as count'),
                            DB::raw('sum(record) as total_days')
                        )
                        ->groupBy('date')
                        ->orderBy('date')
                        ->get()
                        ->map(function ($item) {
                            return [
                                'date' => $item->date,
                                'count' => $item->count,
                                'total_days' => $item->total_days ?? 0,
                            ];
                        })
                        ->toArray();
                },
                CacheService::CATEGORY_DASHBOARD
            );
        } catch (\Exception $e) {
            Log::error("DashboardService::getVacationTrends failed for period '{$period}': " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get stock statistics.
     *
     * @return array
     */
    public function getStockStats(): array
    {
        try {
            return $this->cacheService->remember(
                'dashboard.stock.stats',
                CacheService::DURATION_SHORT,
                function () {
                    $totalItems = Item::count();
                    $lowStockItems = Item::where('quantity', '<', 10)->count();
                    $outOfStockItems = Item::where('quantity', '<=', 0)->count();

                    $totalInputVouchers = InputVoucher::count();
                    $totalOutputVouchers = OutputVoucher::count();

                    // Total value (approximate from input vouchers)
                    $totalValue = DB::table('input_voucher_items')
                        ->sum(DB::raw('quantity * price'));

                    return [
                        'total_items' => $totalItems,
                        'low_stock_items' => $lowStockItems,
                        'out_of_stock_items' => $outOfStockItems,
                        'total_input_vouchers' => $totalInputVouchers,
                        'total_output_vouchers' => $totalOutputVouchers,
                        'total_stock_value' => round($totalValue, 2),
                    ];
                },
                CacheService::CATEGORY_DASHBOARD
            );
        } catch (\Exception $e) {
            Log::error('DashboardService::getStockStats failed: ' . $e->getMessage());
            return [
                'total_items' => 0,
                'low_stock_items' => 0,
                'out_of_stock_items' => 0,
                'total_input_vouchers' => 0,
                'total_output_vouchers' => 0,
                'total_stock_value' => 0,
            ];
        }
    }

    /**
     * Get low stock items.
     *
     * @param int $threshold
     * @return array
     */
    public function getLowStockItems(int $threshold = 10): array
    {
        try {
            // Validate threshold
            if ($threshold < 0) {
                $threshold = 10;
            }

            return $this->cacheService->remember(
                "dashboard.stock.low_stock.{$threshold}",
                CacheService::DURATION_SHORT,
                function () use ($threshold) {
                    return Item::where('quantity', '<', $threshold)
                        ->orderBy('quantity', 'asc')
                        ->limit(10)
                        ->get(['id', 'name', 'quantity', 'category_item_id'])
                        ->map(function ($item) {
                            return [
                                'id' => $item->id,
                                'name' => $item->name,
                                'quantity' => $item->quantity,
                                'status' => $item->quantity <= 0 ? 'out_of_stock' : 'low_stock',
                            ];
                        })
                        ->toArray();
                },
                CacheService::CATEGORY_DASHBOARD
            );
        } catch (\Exception $e) {
            Log::error("DashboardService::getLowStockItems failed for threshold '{$threshold}': " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get system statistics.
     *
     * @return array
     */
    public function getSystemStats(): array
    {
        try {
            return $this->cacheService->remember(
                'dashboard.system.stats',
                CacheService::DURATION_NORMAL,
                function () {
                    return [
                        'total_users' => User::count(),
                        'active_users' => User::where('active', true)->count(),
                        'total_archives' => Archive::count(),
                        'recent_activity_count' => $this->getRecentActivityCount(),
                    ];
                },
                CacheService::CATEGORY_DASHBOARD
            );
        } catch (\Exception $e) {
            Log::error('DashboardService::getSystemStats failed: ' . $e->getMessage());
            return [
                'total_users' => 0,
                'active_users' => 0,
                'total_archives' => 0,
                'recent_activity_count' => 0,
            ];
        }
    }

    /**
     * Get recent activity count (last 7 days).
     *
     * @return int
     */
    protected function getRecentActivityCount(): int
    {
        try {
            $weekAgo = now()->subWeek();

            $recentEmployees = Employee::where('created_at', '>=', $weekAgo)->count();
            $recentVacations = Vacation::where('created_at', '>=', $weekAgo)->count();
            $recentArchives = Archive::where('created_at', '>=', $weekAgo)->count();

            return $recentEmployees + $recentVacations + $recentArchives;
        } catch (\Exception $e) {
            Log::error('DashboardService::getRecentActivityCount failed: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get activity statistics for a specific period.
     *
     * @param string $period
     * @return array
     */
    public function getActivityStats(string $period = 'week'): array
    {
        try {
            // Validate period
            $validPeriods = ['today', 'week', 'month'];
            if (!in_array($period, $validPeriods)) {
                $period = 'week';
            }

            $cacheKey = "dashboard.activity.{$period}";

            return $this->cacheService->remember(
                $cacheKey,
                CacheService::DURATION_SHORT,
                function () use ($period) {
                    $startDate = match ($period) {
                        'today' => now()->startOfDay(),
                        'week' => now()->subWeek(),
                        'month' => now()->subMonth(),
                        default => now()->subWeek(),
                    };

                    return [
                        'new_employees' => Employee::where('created_at', '>=', $startDate)->count(),
                        'new_vacations' => Vacation::where('created_at', '>=', $startDate)->count(),
                        'new_archives' => Archive::where('created_at', '>=', $startDate)->count(),
                        'new_input_vouchers' => InputVoucher::where('created_at', '>=', $startDate)->count(),
                        'new_output_vouchers' => OutputVoucher::where('created_at', '>=', $startDate)->count(),
                    ];
                },
                CacheService::CATEGORY_DASHBOARD
            );
        } catch (\Exception $e) {
            Log::error("DashboardService::getActivityStats failed for period '{$period}': " . $e->getMessage());
            return [
                'new_employees' => 0,
                'new_vacations' => 0,
                'new_archives' => 0,
                'new_input_vouchers' => 0,
                'new_output_vouchers' => 0,
            ];
        }
    }

    /**
     * Invalidate all dashboard cache.
     *
     * @return void
     */
    public function invalidateDashboardCache(): void
    {
        try {
            $this->cacheService->invalidateDashboardCache();
            Log::info('Dashboard cache invalidated successfully');
        } catch (\Exception $e) {
            Log::error('Failed to invalidate dashboard cache: ' . $e->getMessage());
        }
    }
}
