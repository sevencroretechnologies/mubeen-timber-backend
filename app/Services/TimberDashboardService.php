<?php

namespace App\Services;

use App\Models\Estimation;
use App\Models\EstimationCollection;
use App\Models\Customer;
use App\Models\Project;
use App\Models\Timber\TimberStockLedger;
use App\Models\Timber\TimberWarehouse;
use App\Models\Timber\TimberWoodType;
use App\Models\Timber\TimberPurchaseOrder;
use App\Models\Timber\TimberMaterialRequisition;
use Illuminate\Support\Facades\DB;

class TimberDashboardService
{
    /**
     * SINGLE UNIFIED METHOD - Get ALL Timber Dashboard data in one response.
     * This replaces 6+ individual API calls with one efficient request.
     *
     * @param int $stockMovementsDays Number of days for stock movements history
     * @param int $recentPurchaseOrdersLimit Number of recent POs to return
     * @return array Complete dashboard data
     */
    public function getCompleteDashboardData(int $stockMovementsDays = 30, int $recentPurchaseOrdersLimit = 5): array
    {
        // Get all stock ledgers with relationships (available_quantity is an accessor)
        $allLedgers = TimberStockLedger::with(['woodType', 'warehouse'])->get();

        // Calculate total available stock (current - reserved)
        $totalAvailableStock = $allLedgers->sum(function ($ledger) {
            return (float) $ledger->current_quantity - (float) $ledger->reserved_quantity;
        });

        // Calculate low stock items (using accessor)
        $lowStockLedgers = $allLedgers->filter(function ($ledger) {
            $available = (float) $ledger->current_quantity - (float) $ledger->reserved_quantity;
            return $available < (float) $ledger->minimum_threshold;
        });

        return [
            // =================== KEY PERFORMANCE INDICATORS ===================
            'kpi' => [
                'total_stock_cft' => $totalAvailableStock,
                'stock_value' => (float) $allLedgers->sum(function ($ledger) {
                    $available = (float) $ledger->current_quantity - (float) $ledger->reserved_quantity;
                    // Get default rate from wood type
                    $rate = $ledger->woodType?->default_rate ?? 0;
                    return $available * $rate;
                }),
                'wood_types_count' => TimberWoodType::where('is_active', true)->count(),
                'warehouses_count' => TimberWarehouse::count(),
                'pending_pos' => TimberPurchaseOrder::where('status', 'pending')->count(),
                'pending_pos_value' => (float) TimberPurchaseOrder::where('status', 'pending')->sum('total_amount'),
                'low_stock_items' => $lowStockLedgers->count(),
                'pending_requisitions' => TimberMaterialRequisition::where('status', 'pending')->count(),
                'material_issued_this_month' => (float) EstimationCollection::whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->sum('quantity_cft'),
                'projects_count' => Project::count(),
                'customers_count' => Customer::count(),
            ],

            // =================== STOCK BY WAREHOUSE (for charts) ===================
            'stock_by_warehouse' => $allLedgers
                ->groupBy('warehouse_id')
                ->map(function ($ledgers) {
                    $warehouse = $ledgers->first()?->warehouse;
                    $totalCft = $ledgers->sum(function ($l) {
                        return (float) $l->current_quantity - (float) $l->reserved_quantity;
                    });
                    return [
                        'warehouse_id' => $warehouse?->id,
                        'warehouse_name' => $warehouse?->name ?? 'Unknown',
                        'total_cft' => $totalCft,
                        'wood_types_count' => $ledgers->count(),
                        'value' => (float) $ledgers->sum(function ($l) {
                            $available = (float) $l->current_quantity - (float) $l->reserved_quantity;
                            $avgCost = $l->woodType?->default_rate ?? 0;
                            return $available * $avgCost;
                        }),
                    ];
                })
                ->values()
                ->toArray(),

            // =================== STOCK BY WOOD TYPE (for table/pie chart) ===================
            'stock_by_wood_type' => $allLedgers
                ->groupBy('wood_type_id')
                ->map(function ($ledgers) {
                    $woodType = $ledgers->first()?->woodType;
                    $totalCurrent = (float) $ledgers->sum('current_quantity');
                    $totalReserved = (float) $ledgers->sum('reserved_quantity');
                    $totalAvailable = $totalCurrent - $totalReserved;

                    return [
                        'wood_type_id' => $woodType?->id,
                        'wood_type_name' => $woodType?->name ?? 'Unknown',
                        'wood_type_code' => $woodType?->code ?? null,
                        'total_current_quantity' => $totalCurrent,
                        'total_available_quantity' => $totalAvailable,
                        'total_reserved_quantity' => $totalReserved,
                        'warehouses' => $ledgers->map(function ($l) {
                            $available = (float) $l->current_quantity - (float) $l->reserved_quantity;
                            $avgCost = $l->woodType?->default_rate ?? 0;
                            return [
                                'warehouse_id' => $l->warehouse?->id,
                                'warehouse_name' => $l->warehouse?->name,
                                'available_quantity' => $available,
                                'default_rate' => $avgCost,
                                'value' => $available * $avgCost,
                            ];
                        })->toArray(),
                        'total_value' => (float) $ledgers->sum(function ($l) {
                            $available = (float) $l->current_quantity - (float) $l->reserved_quantity;
                            $avgCost = $l->woodType?->default_rate ?? 0;
                            return $available * $avgCost;
                        }),
                    ];
                })
                ->sortByDesc('total_available_quantity')
                ->values()
                ->toArray(),

            // =================== LOW STOCK ITEMS (alert table) ===================
            'low_stock_items' => $lowStockLedgers
                ->sortBy(function ($ledger) {
                    $available = (float) $ledger->current_quantity - (float) $ledger->reserved_quantity;
                    $min = (float) $ledger->minimum_threshold;
                    return $min > 0 ? $available / $min : $available;
                })
                ->take(10)
                ->map(function ($ledger) {
                    $available = (float) $ledger->current_quantity - (float) $ledger->reserved_quantity;
                    return [
                        'id' => $ledger->id,
                        'wood_type' => [
                            'id' => $ledger->woodType?->id,
                            'name' => $ledger->woodType?->name,
                            'code' => $ledger->woodType?->code,
                        ],
                        'warehouse' => [
                            'id' => $ledger->warehouse?->id,
                            'name' => $ledger->warehouse?->name,
                        ],
                        'available_quantity' => $available,
                        'minimum_threshold' => (float) $ledger->minimum_threshold,
                        'maximum_threshold' => (float) $ledger->maximum_threshold,
                        'default_rate' => (float) ($ledger->woodType?->default_rate ?? 0),
                        'reorder_quantity' => (float) ($ledger->maximum_threshold - $available),
                    ];
                })
                ->values()
                ->toArray(),

            // =================== STOCK MOVEMENTS (history table) ===================
            'stock_movements' => DB::table('timber_stock_movements')
                ->where('created_at', '>=', now()->subDays($stockMovementsDays))
                ->orderBy('created_at', 'desc')
                ->limit(20)
                ->get()
                ->map(function ($movement) {
                    $woodType = TimberWoodType::find($movement->wood_type_id);
                    $warehouse = TimberWarehouse::find($movement->warehouse_id);

                    return [
                        'id' => $movement->id,
                        'date' => $movement->created_at,
                        'wood_type' => $woodType?->name ?? 'N/A',
                        'warehouse' => $warehouse?->name ?? 'N/A',
                        'movement_type' => $movement->movement_type,
                        'quantity' => (float) $movement->quantity,
                        'balance_after' => (float) ($movement->balance_after ?? 0),
                        'reference_type' => $movement->reference_type ?? null,
                        'notes' => $movement->notes ?? null,
                    ];
                })->toArray(),

            // =================== PENDING REQUISITIONS ===================
            'pending_requisitions' => TimberMaterialRequisition::with(['items'])
                ->where('status', 'pending')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($requisition) {
                    return [
                        'id' => $requisition->id,
                        'requisition_number' => $requisition->requisition_code,
                        'required_by' => $requisition->requisition_date?->format('Y-m-d'),
                        'items_count' => $requisition->items?->count() ?? 0,
                        'priority' => $requisition->priority,
                        'created_at' => $requisition->created_at?->format('Y-m-d'),
                    ];
                })
                ->toArray(),

            // =================== RECENT PURCHASE ORDERS ===================
            'recent_purchase_orders' => TimberPurchaseOrder::with(['supplier', 'warehouse'])
                ->orderBy('created_at', 'desc')
                ->limit($recentPurchaseOrdersLimit)
                ->get()
                ->map(function ($po) {
                    return [
                        'id' => $po->id,
                        'po_number' => $po->po_code,
                        'supplier' => $po->supplier?->name ?? 'N/A',
                        'warehouse' => $po->warehouse?->name ?? 'N/A',
                        'total_amount' => (float) $po->total_amount,
                        'status' => $po->status,
                        'expected_date' => $po->expected_delivery_date?->format('Y-m-d'),
                    ];
                })
                ->toArray(),

            // =================== PURCHASE ORDERS SUMMARY ===================
            'purchase_orders_summary' => [
                'total' => TimberPurchaseOrder::count(),
                'pending' => TimberPurchaseOrder::where('status', 'pending')->count(),
                'approved' => TimberPurchaseOrder::where('status', 'approved')->count(),
                'completed' => TimberPurchaseOrder::where('status', 'completed')->count(),
                'pending_value' => (float) TimberPurchaseOrder::where('status', 'pending')->sum('total_amount'),
            ],

            // =================== TOP WOOD TYPES BY VALUE (for pie chart) ===================
            'top_wood_types_by_value' => $allLedgers
                ->groupBy('wood_type_id')
                ->map(function ($ledgers) {
                    $woodType = $ledgers->first()?->woodType;
                    $totalCft = $ledgers->sum(function ($l) {
                        return (float) $l->current_quantity - (float) $l->reserved_quantity;
                    });
                    $avgCost = $woodType?->default_rate ?? 0;
                    return [
                        'wood_type_id' => $woodType?->id,
                        'wood_type_name' => $woodType?->name ?? 'Unknown',
                        'total_cft' => $totalCft,
                        'value' => $totalCft * $avgCost,
                    ];
                })
                ->sortByDesc('value')
                ->take(6)
                ->values()
                ->toArray(),
        ];
    }
}
