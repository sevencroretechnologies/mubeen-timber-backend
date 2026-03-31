<?php

namespace App\Services\Timber;

use App\Models\Timber\TimberStockAlert;
use App\Models\Timber\TimberStockLedger;
use Illuminate\Support\Facades\Auth;

class StockAlertService
{
    public function checkThresholds(TimberStockLedger $ledger): void
    {
        $currentQty = (float) $ledger->current_quantity;
        $threshold = (float) $ledger->minimum_threshold;

        if ($threshold <= 0) {
            return;
        }

        if ($currentQty <= 0) {
            $this->createOrUpdateAlert($ledger, 'out_of_stock', $currentQty, $threshold);
        } elseif ($currentQty <= $threshold) {
            $this->createOrUpdateAlert($ledger, 'low_stock', $currentQty, $threshold);
        }
    }

    public function checkAndResolveAlerts(TimberStockLedger $ledger): void
    {
        $currentQty = (float) $ledger->current_quantity;
        $threshold = (float) $ledger->minimum_threshold;

        if ($threshold > 0 && $currentQty > $threshold) {
            TimberStockAlert::where('stock_ledger_id', $ledger->id)
                ->where('is_resolved', false)
                ->update([
                    'is_resolved' => true,
                    'resolved_at' => now(),
                    'resolved_by' => Auth::id(),
                ]);
        } else {
            $this->checkThresholds($ledger);
        }
    }

    public function getUnresolvedAlerts(array $filters = []): array
    {
        $query = TimberStockAlert::with(['woodType', 'warehouse'])
            ->unresolved()
            ->where('company_id', Auth::user()->company_id);

        if (! empty($filters['alert_type'])) {
            $query->where('alert_type', $filters['alert_type']);
        }

        return $query->orderBy('created_at', 'desc')->get()->toArray();
    }

    public function resolveAlert(int $alertId): TimberStockAlert
    {
        $alert = TimberStockAlert::where('company_id', Auth::user()->company_id)
            ->findOrFail($alertId);

        $alert->update([
            'is_resolved' => true,
            'resolved_at' => now(),
            'resolved_by' => Auth::id(),
        ]);

        return $alert;
    }

    private function createOrUpdateAlert(
        TimberStockLedger $ledger,
        string $alertType,
        float $currentQty,
        float $threshold
    ): void {
        $existingAlert = TimberStockAlert::where('stock_ledger_id', $ledger->id)
            ->where('is_resolved', false)
            ->first();

        if ($existingAlert) {
            $existingAlert->update([
                'alert_type' => $alertType,
                'current_quantity' => $currentQty,
                'threshold' => $threshold,
            ]);
        } else {
            TimberStockAlert::create([
                'wood_type_id' => $ledger->wood_type_id,
                'warehouse_id' => $ledger->warehouse_id,
                'stock_ledger_id' => $ledger->id,
                'current_quantity' => $currentQty,
                'threshold' => $threshold,
                'alert_type' => $alertType,
                'company_id' => $ledger->company_id,
                'org_id' => $ledger->org_id,
            ]);
        }
    }
}
