<?php

namespace App\Http\Controllers\Api\Timber;

use App\Http\Controllers\Controller;
use App\Services\Timber\StockAlertService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TimberStockAlertController extends Controller
{
    use ApiResponse;

    protected StockAlertService $alertService;

    public function __construct(StockAlertService $alertService)
    {
        $this->alertService = $alertService;
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $alerts = $this->alertService->getUnresolvedAlerts($request->all());

            return $this->collection(collect($alerts), 'Stock alerts retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve stock alerts: ' . $e->getMessage());
        }
    }

    public function resolve(int $id): JsonResponse
    {
        try {
            $alert = $this->alertService->resolveAlert($id);

            return $this->success($alert, 'Alert resolved successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }
}
