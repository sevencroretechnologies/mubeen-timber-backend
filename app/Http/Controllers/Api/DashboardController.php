<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TimberDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        private TimberDashboardService $timberDashboardService
    ) {}

    /**
     * SINGLE UNIFIED API - Get ALL Timber Dashboard data in one response.
     *
     * @query int stock_movements_days Number of days for stock movements (default: 30)
     * @query int recent_pos_limit Number of recent purchase orders (default: 5)
     */
    public function timber(Request $request): JsonResponse
    {
        $stockMovementsDays = min($request->get('stock_movements_days', 30), 365);
        $recentPosLimit = min($request->get('recent_pos_limit', 5), 20);

        $data = $this->timberDashboardService->getCompleteDashboardData(
            $stockMovementsDays,
            $recentPosLimit
        );

        return response()->json([
            'success' => true,
            'message' => 'Timber dashboard data retrieved successfully',
            'data' => $data,
        ]);
    }
}
