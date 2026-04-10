<?php

namespace App\Services\Timber;

use App\Models\Timber\TimberPurchaseOrder;
use App\Models\Timber\TimberPurchaseOrderItem;
use App\Enums\PurchaseOrderStatus;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PurchaseOrderService
{
    protected StockService $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    public function create(array $data): TimberPurchaseOrder
    {
        return DB::transaction(function () use ($data) {
            $po = TimberPurchaseOrder::create([
                'po_code' => TimberPurchaseOrder::generateCode(),
                'supplier_id' => $data['supplier_id'],
                'warehouse_id' => $data['warehouse_id'],
                'order_date' => $data['order_date'],
                'expected_delivery_date' => $data['expected_delivery_date'] ?? null,
                'tax_percentage' => $data['tax_percentage'] ?? 0,
                'discount_amount' => $data['discount_amount'] ?? 0,
                'status' => PurchaseOrderStatus::DRAFT,
                'notes' => $data['notes'] ?? null,
                'terms' => $data['terms'] ?? null,
                'company_id' => Auth::user()->company_id,
                'org_id' => Auth::user()->org_id,
                'created_by' => Auth::id(),
            ]);

            if (! empty($data['items'])) {
                foreach ($data['items'] as $item) {
                    TimberPurchaseOrderItem::create([
                        'purchase_order_id' => $po->id,
                        'wood_type_id' => $item['wood_type_id'],
                        'quantity' => $item['quantity'],
                        'unit' => $item['unit'],
                        'unit_price' => $item['unit_price'],
                        'total_price' => $item['quantity'] * $item['unit_price'],
                    ]);
                }
            }

            $po->calculateTotals();
            $po->load('items.woodType', 'supplier', 'warehouse');

            return $po;
        });
    }

    public function update(TimberPurchaseOrder $po, array $data): TimberPurchaseOrder
    {
        if ($po->status !== PurchaseOrderStatus::DRAFT) {
            throw new \Exception('Only draft purchase orders can be updated.');
        }

        return DB::transaction(function () use ($po, $data) {
            $po->update([
                'supplier_id' => $data['supplier_id'] ?? $po->supplier_id,
                'warehouse_id' => $data['warehouse_id'] ?? $po->warehouse_id,
                'order_date' => $data['order_date'] ?? $po->order_date,
                'expected_delivery_date' => $data['expected_delivery_date'] ?? $po->expected_delivery_date,
                'tax_percentage' => $data['tax_percentage'] ?? $po->tax_percentage,
                'discount_amount' => $data['discount_amount'] ?? $po->discount_amount,
                'notes' => $data['notes'] ?? $po->notes,
                'terms' => $data['terms'] ?? $po->terms,
            ]);

            if (isset($data['items'])) {
                $po->items()->delete();

                foreach ($data['items'] as $item) {
                    TimberPurchaseOrderItem::create([
                        'purchase_order_id' => $po->id,
                        'wood_type_id' => $item['wood_type_id'],
                        'quantity' => $item['quantity'],
                        'unit' => $item['unit'],
                        'unit_price' => $item['unit_price'],
                        'total_price' => $item['quantity'] * $item['unit_price'],
                    ]);
                }
            }

            $po->calculateTotals();
            $po->load('items.woodType', 'supplier', 'warehouse');

            return $po;
        });
    }

    public function markAsOrdered(TimberPurchaseOrder $po): TimberPurchaseOrder
    {
        if ($po->status !== PurchaseOrderStatus::DRAFT) {
            throw new \Exception('Only draft purchase orders can be marked as ordered.');
        }

        $po->update(['status' => PurchaseOrderStatus::ORDERED]);
        $po->load('items.woodType', 'supplier', 'warehouse');

        return $po;
    }

    public function receiveGoods(TimberPurchaseOrder $po, array $receivedItems): TimberPurchaseOrder
    {
        if (! in_array($po->status, [PurchaseOrderStatus::ORDERED, PurchaseOrderStatus::PARTIAL_RECEIVED])) {
            throw new \Exception('Only ordered or partially received purchase orders can receive goods.');
        }

        return DB::transaction(function () use ($po, $receivedItems) {
            foreach ($receivedItems as $item) {
                $poItem = TimberPurchaseOrderItem::findOrFail($item['id']);

                if ($poItem->purchase_order_id !== $po->id) {
                    throw new \Exception('Item does not belong to this purchase order.');
                }

                $receivedQty = (float) $item['received_quantity'];
                
                // Track current received total to prevent over-receiving
                $newReceivedQty = (float) $poItem->received_quantity + $receivedQty;
                
                if ($newReceivedQty > (float) $poItem->quantity) {
                    throw new \Exception("Cannot receive more than ordered for item #{$poItem->id}. Ordered: {$poItem->quantity}, Total Received: {$newReceivedQty}");
                }

                // Update item received quantity
                $poItem->update([
                    'received_quantity' => $newReceivedQty
                ]);

                // Record stock
                $this->stockService->addStock(
                    $poItem->wood_type_id,
                    $po->warehouse_id,
                    $receivedQty,
                    'purchase_order',
                    $po->id,
                    (float) $poItem->unit_price,
                    "Received from PO: {$po->po_code}"
                );
            }

            // ALWAYS update status to PARTIAL_RECEIVED during receipt logic
            // The final 'RECEIVED' status is handled by confirmReceived()
            $po->update(['status' => PurchaseOrderStatus::PARTIAL_RECEIVED]);

            $po->load('items.woodType', 'supplier', 'warehouse');

            return $po;
        });
    }

    public function confirmReceived(TimberPurchaseOrder $po): TimberPurchaseOrder
    {
        // Validate all items: ensure fully received
        foreach ($po->items as $item) {
            if ((float) $item->received_quantity < (float) $item->quantity) {
                throw new \Exception("Cannot confirm completion: Item #{$item->id} ({$item->woodType?->name}) is not fully received. Ordered: {$item->quantity}, Received: {$item->received_quantity}");
            }
        }

        $po->update([
            'status'        => PurchaseOrderStatus::RECEIVED,
            'received_date' => now()->toDateString()
        ]);
        
        $po->load('items.woodType', 'supplier', 'warehouse');

        return $po;
    }

    public function cancel(TimberPurchaseOrder $po): TimberPurchaseOrder
    {
        if (! $po->canBeCancelled()) {
            throw new \Exception('Cannot cancel partially or fully received orders.');
        }

        return DB::transaction(function () use ($po) {
            $po->update(['status' => PurchaseOrderStatus::CANCELLED]);
            $po->delete(); // Soft delete

            return $po;
        });
    }

    public function delete(TimberPurchaseOrder $po): void
    {
        if ($po->status !== PurchaseOrderStatus::DRAFT) {
            throw new \Exception('Only draft purchase orders can be deleted.');
        }

        $po->items()->delete();
        $po->delete();
    }
}
