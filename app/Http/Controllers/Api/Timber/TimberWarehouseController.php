<?php

namespace App\Http\Controllers\Api\Timber;

use App\Http\Controllers\Controller;
use App\Models\Timber\TimberWarehouse;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TimberWarehouseController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        try {
            $query = TimberWarehouse::forCurrentCompany();

            if ($request->filled('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            $warehouses = $query->latest()->get();

            return $this->collection($warehouses, 'Warehouses retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve warehouses: ' . $e->getMessage());
        }
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:timber_warehouses,code',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'is_default' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            if ($request->boolean('is_default')) {
                TimberWarehouse::forCurrentCompany()
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }

            $warehouse = TimberWarehouse::create($request->all());

            return $this->created($warehouse, 'Warehouse created successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to create warehouse: ' . $e->getMessage());
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'code' => "sometimes|required|string|max:50|unique:timber_warehouses,code,{$id}",
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'is_default' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $warehouse = TimberWarehouse::forCurrentCompany()->findOrFail($id);

            if ($request->boolean('is_default')) {
                TimberWarehouse::forCurrentCompany()
                    ->where('id', '!=', $id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }

            $warehouse->update($request->all());

            return $this->success($warehouse, 'Warehouse updated successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to update warehouse: ' . $e->getMessage());
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $warehouse = TimberWarehouse::forCurrentCompany()->findOrFail($id);

            if ($warehouse->stockLedger()->where('current_quantity', '>', 0)->exists()) {
                return $this->error('Cannot delete warehouse with existing stock', 400);
            }

            $warehouse->delete();

            return $this->noContent('Warehouse deleted successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to delete warehouse: ' . $e->getMessage());
        }
    }
}
