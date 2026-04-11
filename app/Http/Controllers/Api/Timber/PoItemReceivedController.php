<?php

namespace App\Http\Controllers\Api\Timber;

use App\Http\Controllers\Controller;
use App\Models\Timber\PoItemReceived;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PoItemReceivedController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of received PO items.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = PoItemReceived::with(['purchaseOrder:id,po_code,status', 'warehouse:id,name', 'woodType:id,name'])
                ->forCurrentCompany();

            if ($request->filled('purchase_order_id')) {
                $query->where('purchase_order_id', $request->purchase_order_id);
            }

            if ($request->filled('warehouse_id')) {
                $query->where('warehouse_id', $request->warehouse_id);
            }

            if ($request->filled('received_date_from')) {
                $query->whereDate('received_date', '>=', $request->received_date_from);
            }

            if ($request->filled('received_date_to')) {
                $query->whereDate('received_date', '<=', $request->received_date_to);
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->whereHas('purchaseOrder', function ($q) use ($search) {
                    $q->where('po_code', 'like', "%{$search}%");
                });
            }

            $perPage = $request->query('per_page', 15);
            $records = $query->latest()->paginate($perPage);

            return $this->paginated($records, 'PO items received retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve PO items received: ' . $e->getMessage());
        }
    }

    /**
     * Store a newly created received PO item.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'purchase_order_id' => 'required|integer|exists:timber_purchase_orders,id',
            'warehouse_id' => 'required|integer|exists:timber_warehouses,id',
            'wood_type_id' => 'nullable|integer|exists:timber_wood_types,id',
            'received_quantity' => 'required|numeric|min:0.001',
            'received_date' => 'required|date',
            'total_amount' => 'nullable|numeric|min:0',
            'company_id' => 'nullable|integer',
            'org_id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $data = $request->only([
                'purchase_order_id',
                'warehouse_id',
                'wood_type_id',
                'received_quantity',
                'received_date',
                'total_amount',
                'company_id',
                'org_id',
            ]);

            // Find existing record for this Purchase Order
            $record = PoItemReceived::where('purchase_order_id', $data['purchase_order_id'])->first();

            if ($record) {
                // Update existing record
                $record->update([
                    'warehouse_id'      => $data['warehouse_id'],
                    'received_quantity' => $data['received_quantity'], // Should this be summed or overwritten? Overwriting as per user's "update" request.
                    'received_date'     => $data['received_date'],
                    'total_amount'      => $data['total_amount'],
                ]);
            } else {
                // Create new record if not found
                $record = PoItemReceived::create($data);
            }
            
            $record->load(['purchaseOrder:id,po_code,status', 'warehouse:id,name', 'woodType:id,name']);

            return $this->success($record, 'PO item received record processed successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to process PO item received: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified received PO item.
     */
    public function show(int $id): JsonResponse
    {
        try {
            $record = PoItemReceived::with(['purchaseOrder', 'warehouse', 'woodType'])
                ->forCurrentCompany()
                ->findOrFail($id);

            return $this->success($record, 'PO item received retrieved successfully');
        } catch (\Exception $e) {
            return $this->notFound('PO item received not found');
        }
    }

    /**
     * Update the specified received PO item.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'purchase_order_id' => 'sometimes|required|integer|exists:timber_purchase_orders,id',
            'warehouse_id' => 'sometimes|required|integer|exists:timber_warehouses,id',
            'wood_type_id' => 'nullable|integer|exists:timber_wood_types,id',
            'received_quantity' => 'sometimes|required|numeric|min:0.001',
            'received_date' => 'sometimes|required|date',
            'total_amount' => 'nullable|numeric|min:0',
            'company_id' => 'nullable|integer',
            'org_id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $record = PoItemReceived::forCurrentCompany()->findOrFail($id);

            $record->update($request->only([
                'purchase_order_id',
                'warehouse_id',
                'wood_type_id',
                'received_quantity',
                'received_date',
                'total_amount',
                'company_id',
                'org_id',
            ]));

            $record->load(['purchaseOrder:id,po_code,status', 'warehouse:id,name', 'woodType:id,name']);

            return $this->success($record, 'PO item received updated successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Remove the specified received PO item.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $record = PoItemReceived::forCurrentCompany()->findOrFail($id);
            $record->delete();

            return $this->noContent('PO item received deleted successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }
}
