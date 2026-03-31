<?php

namespace App\Services\Timber;

use App\Models\Timber\TimberPurchaseOrder;
use App\Models\Timber\TimberPurchaseOrderItem;
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
                'status' => 'draft',
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
        if ($po->status !== 'draft') {
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
        if ($po->status !== 'draft') {
            throw new \Exception('Only draft purchase orders can be marked as ordered.');
        }

        $po->update(['status' => 'ordered']);
        $po->load('items.woodType', 'supplier', 'warehouse');

        return $po;
    }

    public function receiveGoods(TimberPurchaseOrder $po, array $receivedItems): TimberPurchaseOrder
    {
        if (! in_array($po->status, ['ordered', 'partial_received'])) {
            throw new \Exception('Only ordered or partially received purchase orders can receive goods.');
        }

        return DB::transaction(function () use ($po, $receivedItems) {
            foreach ($receivedItems as $item) {
                $poItem = TimberPurchaseOrderItem::findOrFail($item['item_id']);

                if ($poItem->purchase_order_id !== $po->id) {
                    throw new \Exception('Item does not belong to this purchase order.');
                }

                $newReceivedQty = (float) $poItem->received_quantity + (float) $item['quantity'];
                if ($newReceivedQty > (float) $poItem->quantity) {
                    throw new \Exception("Cannot receive more than ordered for item #{$poItem->id}.");
                }

                $poItem->update(['received_quantity' => $newReceivedQty]);

                $this->stockService->addStock(
                    $poItem->wood_type_id,
                    $po->warehouse_id,
                    (float) $item['quantity'],
                    'purchase_order',
                    $po->id,
                    (float) $poItem->unit_price,
                    "Received from PO: {$po->po_code}"
                );
            }

            $allReceived = $po->items()->get()->every(fn ($i) => $i->isFullyReceived());
            $anyReceived = $po->items()->where('received_quantity', '>', 0)->exists();

            if ($allReceived) {
                $po->update([
                    'status' => 'received',
                    'received_date' => now()->toDateString(),
                ]);
            } elseif ($anyReceived) {
                $po->update(['status' => 'partial_received']);
            }

            $po->load('items.woodType', 'supplier', 'warehouse');

            return $po;
        });
    }

    public function delete(TimberPurchaseOrder $po): void
    {
        if ($po->status !== 'draft') {
            throw new \Exception('Only draft purchase orders can be deleted.');
        }

        $po->items()->delete();
        $po->delete();
    }
}
