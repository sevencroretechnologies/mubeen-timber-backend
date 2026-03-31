<?php

namespace App\Http\Controllers\Api\Timber;

use App\Http\Controllers\Controller;
use App\Services\Timber\StockService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TimberStockController extends Controller
{
    use ApiResponse;

    protected StockService $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $data = $this->stockService->getOverview($request->all());

            return response()->json([
                'success' => true,
                'data' => $data['data'] ?? [],
                'message' => 'Stock overview retrieved successfully',
                'meta' => [
                    'current_page' => $data['current_page'] ?? 1,
                    'per_page' => $data['per_page'] ?? 15,
                    'total' => $data['total'] ?? 0,
                    'total_pages' => $data['last_page'] ?? 1,
                ],
            ]);
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve stock overview: ' . $e->getMessage());
        }
    }

    public function show(int $woodTypeId): JsonResponse
    {
        try {
            $data = $this->stockService->getDetail($woodTypeId);

            return $this->success($data, 'Stock detail retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve stock detail: ' . $e->getMessage());
        }
    }

    public function movements(Request $request): JsonResponse
    {
        try {
            $data = $this->stockService->getMovements($request->all());

            return response()->json([
                'success' => true,
                'data' => $data['data'] ?? [],
                'message' => 'Stock movements retrieved successfully',
                'meta' => [
                    'current_page' => $data['current_page'] ?? 1,
                    'per_page' => $data['per_page'] ?? 15,
                    'total' => $data['total'] ?? 0,
                    'total_pages' => $data['last_page'] ?? 1,
                ],
            ]);
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve stock movements: ' . $e->getMessage());
        }
    }

    public function adjust(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'wood_type_id' => 'required|integer',
            'warehouse_id' => 'required|integer',
            'quantity' => 'required|numeric',
            'notes' => 'nullable|string',
            'movement_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $movement = $this->stockService->adjustStock($request->all());

            return $this->created($movement, 'Stock adjustment recorded successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function lowAlerts(Request $request): JsonResponse
    {
        try {
            $alerts = app(\App\Services\Timber\StockAlertService::class)
                ->getUnresolvedAlerts($request->all());

            return $this->collection(collect($alerts), 'Low stock alerts retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve alerts: ' . $e->getMessage());
        }
    }

    public function setThreshold(Request $request, int $woodTypeId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'minimum_threshold' => 'nullable|numeric|min:0',
            'maximum_threshold' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $this->stockService->setThreshold($woodTypeId, $request->all());

            return $this->success(null, 'Stock threshold updated successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to update threshold: ' . $e->getMessage());
        }
    }

    public function valuation(): JsonResponse
    {
        try {
            $data = $this->stockService->getValuation();

            return $this->success($data, 'Stock valuation retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve valuation: ' . $e->getMessage());
        }
    }

    public function checkAvailability(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'wood_type_id' => 'required|integer',
            'quantity' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $data = $this->stockService->checkAvailability(
                $request->wood_type_id,
                $request->quantity
            );

            return $this->success($data, 'Availability check completed');
        } catch (\Exception $e) {
            return $this->serverError('Failed to check availability: ' . $e->getMessage());
        }
    }
}
