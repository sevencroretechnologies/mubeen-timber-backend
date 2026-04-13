<?php

namespace App\Http\Controllers\Api\Timber;

use App\Http\Controllers\Controller;
use App\Models\Timber\PoItemReceived;
use App\Services\Timber\StockService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PoItemReceivedController extends Controller
{
    use ApiResponse;

    protected StockService $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

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
     * Matches by purchase_order_id + wood_type_id.
     * Accumulates received_quantity (does NOT overwrite).
     * Prevents receiving more than ordered.
     * Updates stock ledger and creates stock movement.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'purchase_order_id' => 'required|integer|exists:timber_purchase_orders,id',
            'warehouse_id'      => 'required|integer|exists:timber_warehouses,id',
            'wood_type_id'      => 'required|integer|exists:timber_wood_types,id',
            'received_quantity' => 'required|numeric|min:0.001',
            'received_date'     => 'required|date',
            'total_amount'      => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        return DB::transaction(function () use ($request) {
            try {
                $poId       = (int) $request->purchase_order_id;
                $woodTypeId = $request->filled('wood_type_id') ? (int) $request->wood_type_id : null;
                $warehouseId = (int) $request->warehouse_id;
                $newQty     = (float) $request->received_quantity;
                $receivedDate = $request->received_date;
                $totalAmount = $request->total_amount ?? 0;

                // If wood_type_id is provided, validate against ordered quantity
                if ($woodTypeId) {
                    $orderedQty = \App\Models\Timber\TimberPurchaseOrderItem::where('purchase_order_id', $poId)
                        ->where('wood_type_id', $woodTypeId)
                        ->sum('quantity');

                    if ($orderedQty <= 0) {
                        return $this->error('This wood type is not part of the purchase order.', 422);
                    }

                    // Find existing record for this PO + wood type combination
                    $existing = PoItemReceived::where('purchase_order_id', $poId)
                        ->where('wood_type_id', $woodTypeId)
                        ->first();

                    $alreadyReceived = $existing ? (float) $existing->received_quantity : 0;
                    $totalAfter      = $alreadyReceived + $newQty;

                    if ($totalAfter > $orderedQty) {
                        return $this->error(
                            "Cannot receive {$newQty}. Already received: {$alreadyReceived}, Ordered: {$orderedQty}. Max receivable now: " . ($orderedQty - $alreadyReceived),
                            422
                        );
                    }

                    // Calculate unit cost
                    $unitCost = $totalAmount > 0 && $newQty > 0
                        ? $totalAmount / $newQty
                        : null;

                    // Add stock using StockService (updates ledger + creates movement)
                    $stockMovement = $this->stockService->addStock(
                        woodTypeId: $woodTypeId,
                        warehouseId: $warehouseId,
                        quantity: $newQty,
                        referenceType: \App\Enums\StockMovementReferenceType::PurchaseOrder->value,
                        referenceId: $poId,
                        unitCost: $unitCost,
                        notes: 'Goods received from PO#' . $poId,
                        movementDate: $receivedDate
                    );
                }

                // Find existing record for this PO + wood type combination (or PO only if no wood_type)
                $existingQuery = PoItemReceived::where('purchase_order_id', $poId);
                if ($woodTypeId) {
                    $existingQuery->where('wood_type_id', $woodTypeId);
                } else {
                    $existingQuery->whereNull('wood_type_id');
                }
                $existing = $existingQuery->first();

                if ($existing) {
                    // Accumulate into existing record
                    $existing->update([
                        'warehouse_id'      => $warehouseId,
                        'received_quantity' => (float) $existing->received_quantity + $newQty,
                        'received_date'     => $receivedDate,
                        'total_amount'      => (float) ($existing->total_amount ?? 0) + (float) $totalAmount,
                    ]);
                    $record = $existing;
                } else {
                    $record = PoItemReceived::create([
                        'purchase_order_id' => $poId,
                        'warehouse_id'      => $warehouseId,
                        'wood_type_id'      => $woodTypeId,
                        'received_quantity' => $newQty,
                        'received_date'     => $receivedDate,
                        'total_amount'      => $totalAmount,
                        'company_id'        => $request->company_id ?? auth()->user()->company_id,
                        'org_id'            => $request->org_id ?? auth()->user()->org_id,
                    ]);
                }

                $record->load(['purchaseOrder:id,po_code,status', 'warehouse:id,name', 'woodType:id,name']);

                $response = [
                    'record' => $record,
                ];

                if (isset($stockMovement)) {
                    $response['stock_movement'] = [
                        'id' => $stockMovement->id,
                        'before_quantity' => $stockMovement->before_quantity,
                        'after_quantity' => $stockMovement->after_quantity,
                        'quantity_added' => $stockMovement->quantity,
                    ];
                }

                return $this->success($response, 'PO items received processed successfully');
            } catch (\Exception $e) {
                return $this->serverError('Failed to process PO item received: ' . $e->getMessage());
            }
        });
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
