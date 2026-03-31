<?php

namespace App\Services\Timber;

use App\Models\Timber\TimberMaterialRequisition;
use App\Models\Timber\TimberMaterialRequisitionItem;
use App\Models\Timber\TimberWarehouse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MaterialRequisitionService
{
    protected StockService $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    public function create(array $data): TimberMaterialRequisition
    {
        return DB::transaction(function () use ($data) {
            $requisition = TimberMaterialRequisition::create([
                'requisition_code' => TimberMaterialRequisition::generateCode(),
                'job_card_id' => $data['job_card_id'] ?? null,
                'project_id' => $data['project_id'] ?? null,
                'requested_by' => Auth::id(),
                'requisition_date' => $data['requisition_date'] ?? now()->toDateString(),
                'priority' => $data['priority'] ?? 'normal',
                'notes' => $data['notes'] ?? null,
                'status' => 'pending',
                'company_id' => Auth::user()->company_id,
                'org_id' => Auth::user()->org_id,
            ]);

            if (! empty($data['items'])) {
                foreach ($data['items'] as $item) {
                    TimberMaterialRequisitionItem::create([
                        'requisition_id' => $requisition->id,
                        'wood_type_id' => $item['wood_type_id'],
                        'requested_quantity' => $item['requested_quantity'],
                        'unit' => $item['unit'],
                        'notes' => $item['notes'] ?? null,
                    ]);
                }
            }

            $requisition->load('items.woodType', 'requestedByUser');

            return $requisition;
        });
    }

    public function approve(TimberMaterialRequisition $requisition, array $data = []): TimberMaterialRequisition
    {
        if ($requisition->status !== 'pending') {
            throw new \Exception('Only pending requisitions can be approved.');
        }

        return DB::transaction(function () use ($requisition, $data) {
            $defaultWarehouse = TimberWarehouse::forCurrentCompany()
                ->where('is_default', true)
                ->first();

            if (! $defaultWarehouse) {
                $defaultWarehouse = TimberWarehouse::forCurrentCompany()->first();
            }

            if (! $defaultWarehouse) {
                throw new \Exception('No warehouse found. Please create a warehouse first.');
            }

            $allIssued = true;

            foreach ($requisition->items as $item) {
                $approvedQty = $data['items'][$item->id]['approved_quantity']
                    ?? (float) $item->requested_quantity;

                $item->update(['approved_quantity' => $approvedQty]);

                try {
                    $this->stockService->deductStock(
                        $item->wood_type_id,
                        $defaultWarehouse->id,
                        $approvedQty,
                        'material_requisition',
                        $requisition->id,
                        "Issued for MR: {$requisition->requisition_code}"
                    );

                    $item->update(['issued_quantity' => $approvedQty]);
                } catch (\Exception $e) {
                    $allIssued = false;
                }
            }

            $requisition->update([
                'status' => $allIssued ? 'issued' : 'partial_issued',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'issued_at' => $allIssued ? now() : null,
            ]);

            $requisition->load('items.woodType', 'requestedByUser', 'approvedByUser');

            return $requisition;
        });
    }

    public function reject(TimberMaterialRequisition $requisition, array $data): TimberMaterialRequisition
    {
        if ($requisition->status !== 'pending') {
            throw new \Exception('Only pending requisitions can be rejected.');
        }

        $requisition->update([
            'status' => 'rejected',
            'rejection_reason' => $data['rejection_reason'] ?? null,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        $requisition->load('items.woodType', 'requestedByUser');

        return $requisition;
    }

    public function returnMaterials(TimberMaterialRequisition $requisition, array $data): TimberMaterialRequisition
    {
        if (! in_array($requisition->status, ['issued', 'partial_issued'])) {
            throw new \Exception('Only issued requisitions can have materials returned.');
        }

        return DB::transaction(function () use ($requisition, $data) {
            $defaultWarehouse = TimberWarehouse::forCurrentCompany()
                ->where('is_default', true)
                ->first()
                ?? TimberWarehouse::forCurrentCompany()->first();

            foreach ($data['items'] as $returnItem) {
                $item = TimberMaterialRequisitionItem::findOrFail($returnItem['item_id']);

                if ($item->requisition_id !== $requisition->id) {
                    throw new \Exception('Item does not belong to this requisition.');
                }

                $returnQty = (float) $returnItem['quantity'];
                $maxReturn = (float) $item->issued_quantity - (float) $item->returned_quantity;

                if ($returnQty > $maxReturn) {
                    throw new \Exception("Cannot return more than issued for item #{$item->id}.");
                }

                $item->update([
                    'returned_quantity' => (float) $item->returned_quantity + $returnQty,
                ]);

                $this->stockService->returnStock(
                    $item->wood_type_id,
                    $defaultWarehouse->id,
                    $returnQty,
                    'material_requisition',
                    $requisition->id,
                    "Return from MR: {$requisition->requisition_code}"
                );
            }

            $allReturned = $requisition->items()->get()->every(function ($item) {
                return (float) $item->returned_quantity >= (float) $item->issued_quantity;
            });

            if ($allReturned) {
                $requisition->update(['status' => 'returned']);
            }

            $requisition->load('items.woodType', 'requestedByUser');

            return $requisition;
        });
    }
}
