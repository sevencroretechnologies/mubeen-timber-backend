<?php

namespace App\Http\Controllers\Api\Timber;

use App\Http\Controllers\Controller;
use App\Models\Timber\TimberPurchaseOrder;
use App\Services\Timber\PurchaseOrderService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TimberPurchaseOrderController extends Controller
{
    use ApiResponse;

    protected PurchaseOrderService $purchaseOrderService;

    public function __construct(PurchaseOrderService $purchaseOrderService)
    {
        $this->purchaseOrderService = $purchaseOrderService;
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $query = TimberPurchaseOrder::with(['supplier:id,name,supplier_code', 'warehouse:id,name'])
                ->withSum('items as total_ordered_qty', 'quantity')
                ->withSum('receivedItems as total_received_qty', 'received_quantity')
                ->forCurrentCompany();

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('supplier_id')) {
                $query->where('supplier_id', $request->supplier_id);
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('po_code', 'like', "%{$search}%")
                        ->orWhereHas('supplier', function ($sq) use ($search) {
                            $sq->where('name', 'like', "%{$search}%");
                        });
                });
            }

            $perPage = $request->query('per_page', 15);
            $orders  = $query->latest()->paginate($perPage);

            // Compute is_fully_received flag on each record so the frontend
            // doesn't have to compare potentially null/string withSum values.
            $orders->getCollection()->transform(function ($order) {
                $totalOrdered  = (float) ($order->total_ordered_qty  ?? 0);
                $totalReceived = (float) ($order->total_received_qty ?? 0);
                $order->is_fully_received = $totalOrdered > 0 && $totalReceived >= $totalOrdered;
                return $order;
            });

            return $this->paginated($orders, 'Purchase orders retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve purchase orders: ' . $e->getMessage());
        }
    }


    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'supplier_id' => 'required|integer|exists:timber_suppliers,id',
            'warehouse_id' => 'required|integer|exists:timber_warehouses,id',
            'order_date' => 'required|date',
            'expected_delivery_date' => 'nullable|date|after_or_equal:order_date',
            'tax_percentage' => 'nullable|numeric|min:0|max:100',
            'tax_group_id' => 'nullable|integer|exists:tax_groups,id',
            'discount_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'terms' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.wood_type_id' => 'required|integer',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.unit' => 'required|string|max:20',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $po = $this->purchaseOrderService->create($request->all());

            return $this->created($po, 'Purchase order created successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to create purchase order: ' . $e->getMessage());
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $po = TimberPurchaseOrder::with(['supplier', 'warehouse', 'items.woodType', 'receivedItems', 'createdBy:id,name', 'taxGroup'])
                ->forCurrentCompany()
                ->findOrFail($id);

            // Merge received_quantity from po_items_received onto each item by wood_type_id
            $receivedMap = $po->receivedItems->keyBy('wood_type_id');
            $po->items->each(function ($item) use ($receivedMap) {
                $item->received_quantity = $receivedMap->has($item->wood_type_id)
                    ? (float) $receivedMap[$item->wood_type_id]->received_quantity
                    : 0;
            });

            return $this->success($po, 'Purchase order retrieved successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Purchase order not found');
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve purchase order: ' . $e->getMessage());
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'supplier_id' => 'sometimes|required|integer|exists:timber_suppliers,id',
            'warehouse_id' => 'sometimes|required|integer|exists:timber_warehouses,id',
            'order_date' => 'sometimes|required|date',
            'expected_delivery_date' => 'nullable|date',
            'tax_percentage' => 'nullable|numeric|min:0|max:100',
            'tax_group_id' => 'nullable|integer|exists:tax_groups,id',
            'discount_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'terms' => 'nullable|string',
            'items' => 'nullable|array|min:1',
            'items.*.wood_type_id' => 'required_with:items|integer',
            'items.*.quantity' => 'required_with:items|numeric|min:0.001',
            'items.*.unit' => 'required_with:items|string|max:20',
            'items.*.unit_price' => 'required_with:items|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $po = TimberPurchaseOrder::forCurrentCompany()->findOrFail($id);
            $po = $this->purchaseOrderService->update($po, $request->all());

            return $this->success($po, 'Purchase order updated successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $po = TimberPurchaseOrder::forCurrentCompany()->findOrFail($id);
            $this->purchaseOrderService->delete($po);

            return $this->noContent('Purchase order deleted successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function send(int $id): JsonResponse
    {
        try {
            $po = TimberPurchaseOrder::forCurrentCompany()->findOrFail($id);
            $po = $this->purchaseOrderService->markAsOrdered($po);

            return $this->success($po, 'Purchase order marked as ordered');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function receive(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|integer|exists:timber_purchase_order_items,id',
        
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $po = TimberPurchaseOrder::forCurrentCompany()->findOrFail($id);
            $po = $this->purchaseOrderService->receiveGoods($po, $request->items);

            return $this->success($po, 'Goods received successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function confirmReceived(int $id): JsonResponse
    {
        try {
            $po = TimberPurchaseOrder::forCurrentCompany()->findOrFail($id);
            $po = $this->purchaseOrderService->confirmReceived($po);

            return $this->success($po, 'Purchase order marked as fully received');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function cancel(int $id): JsonResponse
    {
        try {
            $po = TimberPurchaseOrder::forCurrentCompany()->findOrFail($id);
            $po = $this->purchaseOrderService->cancel($po);

            return $this->success($po, 'Purchase order cancelled successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function generateInvoice(int $id)
    {
        try {
            $po = TimberPurchaseOrder::with(['supplier', 'warehouse', 'items.woodType', 'company'])
                ->forCurrentCompany()
                ->findOrFail($id);

            if (in_array($po->status->value, [\App\Enums\PurchaseOrderStatus::DRAFT->value, \App\Enums\PurchaseOrderStatus::CANCELLED->value])) {
                return $this->error('Invoice can only be generated for confirmed orders', 400);
            }

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('invoices.purchase-order', ['order' => $po]);

            return $pdf->download("invoice-{$po->po_code}.pdf");
        } catch (\Exception $e) {
            return $this->error('Failed to generate invoice: ' . $e->getMessage(), 400);
        }
    }
}
