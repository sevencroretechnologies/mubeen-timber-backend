<?php

namespace App\Services\Timber;

use App\Models\Timber\TimberStockLedger;
use App\Models\Timber\TimberStockMovement;
use App\Models\Timber\TimberWoodType;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockService
{
    public function getOverview(array $filters = []): array
    {
        $query = TimberStockLedger::with(['woodType', 'warehouse'])
            ->forCurrentCompany();

        if (! empty($filters['warehouse_id'])) {
            $query->where('warehouse_id', $filters['warehouse_id']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('woodType', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $perPage = $filters['per_page'] ?? 15;

        return $query->latest()->paginate($perPage)->toArray();
    }

    public function getDetail(int $woodTypeId): array
    {
        $ledger = TimberStockLedger::with(['woodType', 'warehouse'])
            ->forCurrentCompany()
            ->where('wood_type_id', $woodTypeId)
            ->get();

        $movements = TimberStockMovement::with(['woodType', 'warehouse', 'createdBy'])
            ->forCurrentCompany()
            ->where('wood_type_id', $woodTypeId)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return [
            'ledger' => $ledger,
            'movements' => $movements,
        ];
    }

    public function getMovements(array $filters = []): array
    {
        $query = TimberStockMovement::with(['woodType', 'warehouse', 'createdBy'])
            ->forCurrentCompany();

        if (! empty($filters['movement_type'])) {
            $query->where('movement_type', $filters['movement_type']);
        }

        if (! empty($filters['wood_type_id'])) {
            $query->where('wood_type_id', $filters['wood_type_id']);
        }

        if (! empty($filters['warehouse_id'])) {
            $query->where('warehouse_id', $filters['warehouse_id']);
        }

        if (! empty($filters['date_from'])) {
            $query->where('movement_date', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->where('movement_date', '<=', $filters['date_to']);
        }

        if (! empty($filters['reference_type'])) {
            $query->where('reference_type', $filters['reference_type']);
        }

        $perPage = $filters['per_page'] ?? 15;

        return $query->orderBy('created_at', 'desc')->paginate($perPage)->toArray();
    }

    public function addStock(
        int $woodTypeId,
        int $warehouseId,
        float $quantity,
        string $referenceType,
        ?int $referenceId = null,
        ?float $unitCost = null,
        ?string $notes = null,
        ?string $movementDate = null
    ): TimberStockMovement {
        return DB::transaction(function () use ($woodTypeId, $warehouseId, $quantity, $referenceType, $referenceId, $unitCost, $notes, $movementDate) {
            $ledger = $this->getOrCreateLedger($woodTypeId, $warehouseId);

            $beforeQuantity = (float) $ledger->current_quantity;
            $afterQuantity = $beforeQuantity + $quantity;

            $ledger->update([
                'current_quantity' => $afterQuantity,
                'last_restocked_at' => now(),
            ]);

            $movement = TimberStockMovement::create([
                'stock_ledger_id' => $ledger->id,
                'wood_type_id' => $woodTypeId,
                'warehouse_id' => $warehouseId,
                'movement_type' => 'in',
                'quantity' => $quantity,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'unit_cost' => $unitCost,
                'total_cost' => $unitCost ? $unitCost * $quantity : null,
                'before_quantity' => $beforeQuantity,
                'after_quantity' => $afterQuantity,
                'notes' => $notes,
                'movement_date' => $movementDate ?? now()->toDateString(),
                'company_id' => Auth::user()->company_id,
                'org_id' => Auth::user()->org_id,
                'created_by' => Auth::id(),
            ]);

            app(StockAlertService::class)->checkAndResolveAlerts($ledger);

            return $movement;
        });
    }

    public function deductStock(
        int $woodTypeId,
        int $warehouseId,
        float $quantity,
        string $referenceType,
        ?int $referenceId = null,
        ?string $notes = null,
        ?string $movementDate = null
    ): TimberStockMovement {
        return DB::transaction(function () use ($woodTypeId, $warehouseId, $quantity, $referenceType, $referenceId, $notes, $movementDate) {
            $ledger = $this->getOrCreateLedger($woodTypeId, $warehouseId);

            $beforeQuantity = (float) $ledger->current_quantity;
            $afterQuantity = $beforeQuantity - $quantity;

            if ($afterQuantity < 0) {
                throw new \Exception("Insufficient stock. Available: {$beforeQuantity}, Requested: {$quantity}");
            }

            $ledger->update([
                'current_quantity' => $afterQuantity,
            ]);

            $movement = TimberStockMovement::create([
                'stock_ledger_id' => $ledger->id,
                'wood_type_id' => $woodTypeId,
                'warehouse_id' => $warehouseId,
                'movement_type' => 'out',
                'quantity' => $quantity,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'before_quantity' => $beforeQuantity,
                'after_quantity' => $afterQuantity,
                'notes' => $notes,
                'movement_date' => $movementDate ?? now()->toDateString(),
                'company_id' => Auth::user()->company_id,
                'org_id' => Auth::user()->org_id,
                'created_by' => Auth::id(),
            ]);

            app(StockAlertService::class)->checkThresholds($ledger);

            return $movement;
        });
    }

    public function adjustStock(array $data): TimberStockMovement
    {
        return DB::transaction(function () use ($data) {
            $ledger = $this->getOrCreateLedger($data['wood_type_id'], $data['warehouse_id']);

            $beforeQuantity = (float) $ledger->current_quantity;
            $adjustmentQty = (float) $data['quantity'];
            $afterQuantity = $beforeQuantity + $adjustmentQty;

            if ($afterQuantity < 0) {
                throw new \Exception("Adjustment would result in negative stock: {$afterQuantity}");
            }

            $ledger->update([
                'current_quantity' => $afterQuantity,
            ]);

            $movement = TimberStockMovement::create([
                'stock_ledger_id' => $ledger->id,
                'wood_type_id' => $data['wood_type_id'],
                'warehouse_id' => $data['warehouse_id'],
                'movement_type' => 'adjustment',
                'quantity' => abs($adjustmentQty),
                'reference_type' => 'manual',
                'before_quantity' => $beforeQuantity,
                'after_quantity' => $afterQuantity,
                'notes' => $data['notes'] ?? null,
                'movement_date' => $data['movement_date'] ?? now()->toDateString(),
                'company_id' => Auth::user()->company_id,
                'org_id' => Auth::user()->org_id,
                'created_by' => Auth::id(),
            ]);

            app(StockAlertService::class)->checkThresholds($ledger);

            return $movement;
        });
    }

    public function returnStock(
        int $woodTypeId,
        int $warehouseId,
        float $quantity,
        string $referenceType,
        ?int $referenceId = null,
        ?string $notes = null
    ): TimberStockMovement {
        return DB::transaction(function () use ($woodTypeId, $warehouseId, $quantity, $referenceType, $referenceId, $notes) {
            $ledger = $this->getOrCreateLedger($woodTypeId, $warehouseId);

            $beforeQuantity = (float) $ledger->current_quantity;
            $afterQuantity = $beforeQuantity + $quantity;

            $ledger->update([
                'current_quantity' => $afterQuantity,
            ]);

            $movement = TimberStockMovement::create([
                'stock_ledger_id' => $ledger->id,
                'wood_type_id' => $woodTypeId,
                'warehouse_id' => $warehouseId,
                'movement_type' => 'return',
                'quantity' => $quantity,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'before_quantity' => $beforeQuantity,
                'after_quantity' => $afterQuantity,
                'notes' => $notes,
                'movement_date' => now()->toDateString(),
                'company_id' => Auth::user()->company_id,
                'org_id' => Auth::user()->org_id,
                'created_by' => Auth::id(),
            ]);

            app(StockAlertService::class)->checkAndResolveAlerts($ledger);

            return $movement;
        });
    }

    public function checkAvailability(int $woodTypeId, float $requiredQuantity): array
    {
        $totalAvailable = TimberStockLedger::forCurrentCompany()
            ->where('wood_type_id', $woodTypeId)
            ->sum(DB::raw('current_quantity - reserved_quantity'));

        $totalAvailable = (float) $totalAvailable;

        return [
            'wood_type_id' => $woodTypeId,
            'available_quantity' => $totalAvailable,
            'required_quantity' => $requiredQuantity,
            'is_sufficient' => $totalAvailable >= $requiredQuantity,
            'shortage' => max(0, $requiredQuantity - $totalAvailable),
        ];
    }

    public function setThreshold(int $woodTypeId, array $data): void
    {
        $ledgers = TimberStockLedger::forCurrentCompany()
            ->where('wood_type_id', $woodTypeId)
            ->get();

        foreach ($ledgers as $ledger) {
            $ledger->update([
                'minimum_threshold' => $data['minimum_threshold'] ?? $ledger->minimum_threshold,
                'maximum_threshold' => $data['maximum_threshold'] ?? $ledger->maximum_threshold,
            ]);

            app(StockAlertService::class)->checkThresholds($ledger);
        }
    }

    public function getValuation(): array
    {
        $ledgers = TimberStockLedger::with('woodType')
            ->forCurrentCompany()
            ->where('current_quantity', '>', 0)
            ->get();

        $totalValue = 0;
        $items = [];

        foreach ($ledgers as $ledger) {
            $value = (float) $ledger->current_quantity * (float) ($ledger->woodType->default_rate ?? 0);
            $totalValue += $value;
            $items[] = [
                'wood_type' => $ledger->woodType->name ?? 'Unknown',
                'quantity' => $ledger->current_quantity,
                'rate' => $ledger->woodType->default_rate ?? 0,
                'value' => round($value, 2),
            ];
        }

        return [
            'total_value' => round($totalValue, 2),
            'items' => $items,
        ];
    }

    private function getOrCreateLedger(int $woodTypeId, int $warehouseId): TimberStockLedger
    {
        return TimberStockLedger::firstOrCreate(
            [
                'wood_type_id' => $woodTypeId,
                'warehouse_id' => $warehouseId,
                'company_id' => Auth::user()->company_id,
            ],
            [
                'org_id' => Auth::user()->org_id,
                'current_quantity' => 0,
                'reserved_quantity' => 0,
                'minimum_threshold' => 0,
                'maximum_threshold' => 0,
            ]
        );
    }
}
