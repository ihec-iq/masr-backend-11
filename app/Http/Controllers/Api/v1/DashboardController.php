<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    protected DashboardService $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * Get complete dashboard overview.
     *
     * @return JsonResponse
     */
    public function overview(): JsonResponse
    {
        try {
            // if (!Gate::allows('viewDashboard', auth()->user())) {
            //     return response()->json(['message' => 'Unauthorized'], 403);
            // }

            $data = $this->dashboardService->getOverview();
            return $this->ok($data);
        } catch (\Exception $e) {
            Log::error('DashboardController::overview error: ' . $e->getMessage());
            return $this->error('Failed to load dashboard overview', 500);
        }
    }

    /**
     * Get employee statistics.
     *
     * @return JsonResponse
     */
    public function employeeStats(): JsonResponse
    {
        try {
            // if (!Gate::allows('viewDashboard', auth()->user())) {
            //     return response()->json(['message' => 'Unauthorized'], 403);
            // }

            $data = $this->dashboardService->getEmployeeStats();

            return $this->ok($data);
        } catch (\Exception $e) {
            Log::error('DashboardController::employeeStats error: ' . $e->getMessage());
            return $this->error('Failed to load employee statistics', 500);
        }
    }

    /**
     * Get employees by type.
     *
     * @return JsonResponse
     */
    public function employeesByType(): JsonResponse
    {
        try {
            // if (!Gate::allows('viewDashboard', auth()->user())) {
            //     return response()->json(['message' => 'Unauthorized'], 403);
            // }

            $data = $this->dashboardService->getEmployeesByType();

            return $this->ok($data);
        } catch (\Exception $e) {
            Log::error('DashboardController::employeesByType error: ' . $e->getMessage());
            return $this->error('Failed to load employees by type', 500);
        }
    }

    /**
     * Get employees by section.
     *
     * @return JsonResponse
     */
    public function employeesBySection(): JsonResponse
    {
        try {
            // if (!Gate::allows('viewDashboard', auth()->user())) {
            //     return response()->json(['message' => 'Unauthorized'], 403);
            // }

            $data = $this->dashboardService->getEmployeesBySection();

            return $this->ok($data);
        } catch (\Exception $e) {
            Log::error('DashboardController::employeesBySection error: ' . $e->getMessage());
            return $this->error('Failed to load employees by section', 500);
        }
    }

    /**
     * Get vacation statistics.
     *
     * @return JsonResponse
     */
    public function vacationStats(): JsonResponse
    {
        try {
            // if (!Gate::allows('viewDashboard', auth()->user())) {
            //     return response()->json(['message' => 'Unauthorized'], 403);
            // }

            $data = $this->dashboardService->getVacationStats();

            return $this->ok($data);
        } catch (\Exception $e) {
            Log::error('DashboardController::vacationStats error: ' . $e->getMessage());
            return $this->error('Failed to load vacation statistics', 500);
        }
    }

    /**
     * Get vacation trends.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function vacationTrends(Request $request): JsonResponse
    {
        try {
            // if (!Gate::allows('viewDashboard', auth()->user())) {
            //     return response()->json(['message' => 'Unauthorized'], 403);
            // }

            // Validate input
            $validated = $request->validate([
                'period' => 'sometimes|string|in:week,month,year',
            ]);

            $period = $validated['period'] ?? 'month';
            $data = $this->dashboardService->getVacationTrends($period);

            return $this->ok($data);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->error($e->getMessage(), 422);
        } catch (\Exception $e) {
            Log::error('DashboardController::vacationTrends error: ' . $e->getMessage());
            return $this->error('Failed to load vacation trends', 500);
        }
    }

    /**
     * Get stock statistics.
     *
     * @return JsonResponse
     */
    public function stockStats(): JsonResponse
    {
        try {
            // if (!Gate::allows('viewDashboard', auth()->user())) {
            //     return response()->json(['message' => 'Unauthorized'], 403);
            // }

            $data = $this->dashboardService->getStockStats();

            return $this->ok($data);
        } catch (\Exception $e) {
            Log::error('DashboardController::stockStats error: ' . $e->getMessage());
            return $this->error('Failed to load stock statistics', 500);
        }
    }

    /**
     * Get low stock items.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function lowStockItems(Request $request): JsonResponse
    {
        try {
            // if (!Gate::allows('viewDashboard', auth()->user())) {
            //     return response()->json(['message' => 'Unauthorized'], 403);
            // }

            // Validate input
            $validated = $request->validate([
                'threshold' => 'sometimes|integer|min:0|max:1000',
            ]);

            $threshold = $validated['threshold'] ?? 10;
            $data = $this->dashboardService->getLowStockItems($threshold);

            return $this->ok($data);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->error($e->getMessage(), 422);
        } catch (\Exception $e) {
            Log::error('DashboardController::lowStockItems error: ' . $e->getMessage());
            return $this->error('Failed to load low stock items', 500);
        }
    }

    /**
     * Get system statistics.
     *
     * @return JsonResponse
     */
    public function systemStats(): JsonResponse
    {
        try {
            // if (!Gate::allows('viewDashboard', auth()->user())) {
            //     return response()->json(['message' => 'Unauthorized'], 403);
            // }

            $data = $this->dashboardService->getSystemStats();

            return $this->ok($data);
        } catch (\Exception $e) {
            Log::error('DashboardController::systemStats error: ' . $e->getMessage());
            return $this->error('Failed to load system statistics', 500);
        }
    }

    /**
     * Get activity statistics.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function activityStats(Request $request): JsonResponse
    {
        try {
            // if (!Gate::allows('viewDashboard', auth()->user())) {
            //     return response()->json(['message' => 'Unauthorized'], 403);
            // }

            // Validate input
            $validated = $request->validate([
                'period' => 'sometimes|string|in:today,week,month',
            ]);

            $period = $validated['period'] ?? 'week';
            $data = $this->dashboardService->getActivityStats($period);

            return $this->ok($data);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->error($e->getMessage(), 422);
        } catch (\Exception $e) {
            Log::error('DashboardController::activityStats error: ' . $e->getMessage());
            return $this->error('Failed to load activity statistics', 500);
        }
    }

    /**
     * Invalidate dashboard cache.
     *
     * @return JsonResponse
     */
    public function invalidateCache(): JsonResponse
    {
        try {
            // if (!Gate::allows('viewDashboard', auth()->user())) {
            //     return response()->json(['message' => 'Unauthorized'], 403);
            // }   

            $this->dashboardService->invalidateDashboardCache();

            return $this->ok(['message' => 'Dashboard cache invalidated successfully']);
        } catch (\Exception $e) {
            Log::error('DashboardController::invalidateCache error: ' . $e->getMessage());
            return $this->error('Failed to invalidate cache', 500);
        }
    }
}
