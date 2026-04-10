<?php

namespace Database\Seeders;

use App\Enums\PurchaseOrderStatus;
use App\Models\Company;
use App\Models\Organization;
use App\Models\Timber\TimberMaterialRequisition;
use App\Models\Timber\TimberMaterialRequisitionItem;
use App\Models\Timber\TimberPurchaseOrder;
use App\Models\Timber\TimberPurchaseOrderItem;
use App\Models\Timber\TimberStockAlert;
use App\Models\Timber\TimberStockLedger;
use App\Models\Timber\TimberStockMovement;
use App\Models\Timber\TimberSupplier;
use App\Models\Timber\TimberWarehouse;
use App\Models\Timber\TimberWoodType;
use App\Models\User;
use Illuminate\Database\Seeder;

class TimberDemoSeeder extends Seeder
{
    public function run(): void
    {
        $org = Organization::first();
        $company = Company::first();

        if (!$org || !$company) {
            $this->command->warn('No organization or company found. Skipping Timber demo data.');
            return;
        }

        $orgId = $org->id;
        $companyId = $company->id;
        $user = User::first();
        $userId = $user ? $user->id : 1;

        // ============================================
        // Wood Types
        // ============================================
        $woodTypes = [
            ['name' => 'Teak', 'code' => 'TEAK-001', 'category' => 'Hardwood', 'default_rate' => 2500.00, 'unit' => 'CFT', 'description' => 'Premium quality teak wood'],
            ['name' => 'Sal', 'code' => 'SAL-002', 'category' => 'Hardwood', 'default_rate' => 1800.00, 'unit' => 'CFT', 'description' => 'Durable sal wood for construction'],
            ['name' => 'Sheesham', 'code' => 'SHSM-003', 'category' => 'Hardwood', 'default_rate' => 2200.00, 'unit' => 'CFT', 'description' => 'Indian rosewood for furniture'],
            ['name' => 'Deodar', 'code' => 'DEOD-004', 'category' => 'Softwood', 'default_rate' => 1500.00, 'unit' => 'CFT', 'description' => 'Himalayan cedar wood'],
            ['name' => 'Pine', 'code' => 'PINE-005', 'category' => 'Softwood', 'default_rate' => 800.00, 'unit' => 'CFT', 'description' => 'Standard pine for packaging and construction'],
            ['name' => 'Mango', 'code' => 'MNGO-006', 'category' => 'Hardwood', 'default_rate' => 1200.00, 'unit' => 'CFT', 'description' => 'Mango wood for budget furniture'],
            ['name' => 'Neem', 'code' => 'NEEM-007', 'category' => 'Hardwood', 'default_rate' => 1000.00, 'unit' => 'CFT', 'description' => 'Neem wood - pest resistant'],
            ['name' => 'Rubber Wood', 'code' => 'RUBR-008', 'category' => 'Hardwood', 'default_rate' => 900.00, 'unit' => 'CFT', 'description' => 'Eco-friendly rubber wood'],
            ['name' => 'Plywood (Commercial)', 'code' => 'PLYW-009', 'category' => 'Engineered', 'default_rate' => 45.00, 'unit' => 'SQF', 'description' => 'Commercial grade plywood sheets'],
            ['name' => 'MDF Board', 'code' => 'MDF-010', 'category' => 'Engineered', 'default_rate' => 35.00, 'unit' => 'SQF', 'description' => 'Medium density fibreboard'],
        ];

        $createdWoodTypes = [];
        foreach ($woodTypes as $wt) {
            $wt['org_id'] = $orgId;
            $wt['company_id'] = $companyId;
            $createdWoodTypes[] = TimberWoodType::firstOrCreate(
                ['code' => $wt['code'], 'company_id' => $companyId],
                $wt
            );
        }

        // ============================================
        // Suppliers
        // ============================================
        $suppliers = [
            ['name' => 'Rajesh Timber Traders', 'contact_person' => 'Rajesh Kumar', 'phone' => '+91-9876543210', 'email' => 'rajesh@timbertraders.com', 'address' => '45 Industrial Area, Phase-2', 'city' => 'Chandigarh', 'state' => 'Punjab', 'pincode' => '160002', 'gst_number' => '03AABCT1234F1ZP'],
            ['name' => 'Himalayan Wood Supplies', 'contact_person' => 'Vikram Singh', 'phone' => '+91-9812345678', 'email' => 'vikram@himalayanwood.in', 'address' => '12 Mall Road', 'city' => 'Shimla', 'state' => 'Himachal Pradesh', 'pincode' => '171001', 'gst_number' => '02BBDCS5678G2ZQ'],
            ['name' => 'Southern Timber Corp', 'contact_person' => 'Anand Menon', 'phone' => '+91-9445567890', 'email' => 'anand@southerntimber.co.in', 'address' => '78 Timber Yard, Peenya', 'city' => 'Bangalore', 'state' => 'Karnataka', 'pincode' => '560058', 'gst_number' => '29CCDST9012H3ZR'],
            ['name' => 'Bengal Plywood Industries', 'contact_person' => 'Subrata Das', 'phone' => '+91-9831234567', 'email' => 'subrata@bengalplywood.com', 'address' => '23 Jessore Road', 'city' => 'Kolkata', 'state' => 'West Bengal', 'pincode' => '700055', 'gst_number' => '19DDEPB3456I4ZS'],
            ['name' => 'Gujarat Timber House', 'contact_person' => 'Bharat Patel', 'phone' => '+91-9898765432', 'email' => 'bharat@gujarattimber.in', 'address' => '56 GIDC Estate', 'city' => 'Ahmedabad', 'state' => 'Gujarat', 'pincode' => '382445', 'gst_number' => '24EEFGT7890J5ZT'],
            ['name' => 'MP Forest Products', 'contact_person' => 'Arun Sharma', 'phone' => '+91-9425123456', 'email' => 'arun@mpforest.com', 'address' => '90 Vijay Nagar', 'city' => 'Indore', 'state' => 'Madhya Pradesh', 'pincode' => '452010', 'gst_number' => '23FFGMP2345K6ZU'],
        ];

        $createdSuppliers = [];
        $codeCounter = 1;
        foreach ($suppliers as $s) {
            $s['org_id'] = $orgId;
            $s['company_id'] = $companyId;
            $s['supplier_code'] = 'SUP-' . str_pad($codeCounter++, 4, '0', STR_PAD_LEFT);
            $s['created_by'] = $userId;
            $createdSuppliers[] = TimberSupplier::firstOrCreate(
                ['supplier_code' => $s['supplier_code'], 'company_id' => $companyId],
                $s
            );
        }

        // ============================================
        // Warehouses
        // ============================================
        $warehouses = [
            ['name' => 'Main Warehouse', 'code' => 'WH-MAIN', 'address' => 'Plot 45, Industrial Area Phase-2', 'city' => 'Chandigarh', 'is_default' => true],
            ['name' => 'Timber Yard - North', 'code' => 'WH-NORTH', 'address' => 'NH-21, Near Pinjore', 'city' => 'Haryana', 'is_default' => false],
            ['name' => 'Finished Goods Store', 'code' => 'WH-FGS', 'address' => 'Sector 26', 'city' => 'Chandigarh', 'is_default' => false],
            ['name' => 'Raw Material Depot', 'code' => 'WH-RMD', 'address' => 'Barwala Road', 'city' => 'Panchkula', 'is_default' => false],
        ];

        $createdWarehouses = [];
        foreach ($warehouses as $w) {
            $w['org_id'] = $orgId;
            $w['company_id'] = $companyId;
            $createdWarehouses[] = TimberWarehouse::firstOrCreate(
                ['name' => $w['name'], 'company_id' => $companyId],
                $w
            );
        }

        // ============================================
        // Stock Ledger & Movements (initial stock)
        // ============================================
        $stockData = [
            ['wood_type_idx' => 0, 'warehouse_idx' => 0, 'qty' => 150.00, 'rate' => 2500.00, 'min_threshold' => 50, 'max_threshold' => 500],
            ['wood_type_idx' => 1, 'warehouse_idx' => 0, 'qty' => 200.00, 'rate' => 1800.00, 'min_threshold' => 80, 'max_threshold' => 600],
            ['wood_type_idx' => 2, 'warehouse_idx' => 0, 'qty' => 80.00, 'rate' => 2200.00, 'min_threshold' => 30, 'max_threshold' => 300],
            ['wood_type_idx' => 3, 'warehouse_idx' => 1, 'qty' => 300.00, 'rate' => 1500.00, 'min_threshold' => 100, 'max_threshold' => 800],
            ['wood_type_idx' => 4, 'warehouse_idx' => 1, 'qty' => 500.00, 'rate' => 800.00, 'min_threshold' => 200, 'max_threshold' => 1000],
            ['wood_type_idx' => 5, 'warehouse_idx' => 0, 'qty' => 120.00, 'rate' => 1200.00, 'min_threshold' => 40, 'max_threshold' => 400],
            ['wood_type_idx' => 6, 'warehouse_idx' => 2, 'qty' => 25.00, 'rate' => 1000.00, 'min_threshold' => 50, 'max_threshold' => 300],
            ['wood_type_idx' => 7, 'warehouse_idx' => 2, 'qty' => 60.00, 'rate' => 900.00, 'min_threshold' => 30, 'max_threshold' => 200],
            ['wood_type_idx' => 8, 'warehouse_idx' => 3, 'qty' => 400.00, 'rate' => 45.00, 'min_threshold' => 150, 'max_threshold' => 1000],
            ['wood_type_idx' => 9, 'warehouse_idx' => 3, 'qty' => 250.00, 'rate' => 35.00, 'min_threshold' => 100, 'max_threshold' => 800],
        ];

        $createdLedgers = [];
        foreach ($stockData as $sd) {
            $woodType = $createdWoodTypes[$sd['wood_type_idx']];
            $warehouse = $createdWarehouses[$sd['warehouse_idx']];

            $ledger = TimberStockLedger::firstOrCreate(
                ['wood_type_id' => $woodType->id, 'warehouse_id' => $warehouse->id, 'company_id' => $companyId],
                [
                    'org_id' => $orgId,
                    'company_id' => $companyId,
                    'wood_type_id' => $woodType->id,
                    'warehouse_id' => $warehouse->id,
                    'current_quantity' => $sd['qty'],
                    'reserved_quantity' => 0,
                    'minimum_threshold' => $sd['min_threshold'],
                    'maximum_threshold' => $sd['max_threshold'],
                    'last_restocked_at' => now()->subDays(rand(1, 10)),
                ]
            );
            $createdLedgers[$sd['wood_type_idx'] . '-' . $sd['warehouse_idx']] = $ledger;

            TimberStockMovement::firstOrCreate(
                ['stock_ledger_id' => $ledger->id, 'reference_type' => 'manual', 'notes' => 'Opening stock balance'],
                [
                    'org_id' => $orgId,
                    'company_id' => $companyId,
                    'stock_ledger_id' => $ledger->id,
                    'wood_type_id' => $woodType->id,
                    'warehouse_id' => $warehouse->id,
                    'movement_type' => 'in',
                    'quantity' => $sd['qty'],
                    'unit' => $woodType->unit,
                    'reference_type' => 'manual',
                    'reference_id' => null,
                    'unit_cost' => $sd['rate'],
                    'total_cost' => $sd['qty'] * $sd['rate'],
                    'before_quantity' => 0,
                    'after_quantity' => $sd['qty'],
                    'notes' => 'Opening stock balance',
                    'movement_date' => now()->subDays(30),
                    'created_by' => $userId,
                ]
            );
        }

        // ============================================
        // Purchase Orders (various statuses)
        // ============================================
        $purchaseOrders = [
            [
                'supplier_idx' => 0, 'warehouse_idx' => 0, 'status' => PurchaseOrderStatus::RECEIVED, 'po_code' => 'PO-2026-001',
                'items' => [
                    ['wood_type_idx' => 0, 'qty' => 50, 'rate' => 2450],
                    ['wood_type_idx' => 2, 'qty' => 30, 'rate' => 2150],
                ],
            ],
            [
                'supplier_idx' => 1, 'warehouse_idx' => 1, 'status' => PurchaseOrderStatus::ORDERED, 'po_code' => 'PO-2026-002',
                'items' => [
                    ['wood_type_idx' => 3, 'qty' => 100, 'rate' => 1480],
                    ['wood_type_idx' => 4, 'qty' => 200, 'rate' => 780],
                ],
            ],
            [
                'supplier_idx' => 2, 'warehouse_idx' => 0, 'status' => PurchaseOrderStatus::DRAFT, 'po_code' => 'PO-2026-003',
                'items' => [
                    ['wood_type_idx' => 1, 'qty' => 75, 'rate' => 1780],
                ],
            ],
            [
                'supplier_idx' => 3, 'warehouse_idx' => 3, 'status' => PurchaseOrderStatus::RECEIVED, 'po_code' => 'PO-2026-004',
                'items' => [
                    ['wood_type_idx' => 8, 'qty' => 200, 'rate' => 43],
                    ['wood_type_idx' => 9, 'qty' => 150, 'rate' => 33],
                ],
            ],
            [
                'supplier_idx' => 4, 'warehouse_idx' => 0, 'status' => PurchaseOrderStatus::ORDERED, 'po_code' => 'PO-2026-005',
                'items' => [
                    ['wood_type_idx' => 5, 'qty' => 60, 'rate' => 1180],
                    ['wood_type_idx' => 6, 'qty' => 40, 'rate' => 980],
                ],
            ],
            [
                'supplier_idx' => 5, 'warehouse_idx' => 0, 'status' => PurchaseOrderStatus::PARTIAL_RECEIVED, 'po_code' => 'PO-2026-006',
                'items' => [
                    ['wood_type_idx' => 0, 'qty' => 80, 'rate' => 2480],
                    ['wood_type_idx' => 7, 'qty' => 50, 'rate' => 880],
                ],
            ],
        ];

        foreach ($purchaseOrders as $poData) {
            $supplier = $createdSuppliers[$poData['supplier_idx']];
            $warehouse = $createdWarehouses[$poData['warehouse_idx']];
            $subtotal = 0;
            foreach ($poData['items'] as $item) {
                $subtotal += $item['qty'] * $item['rate'];
            }
            $taxPercentage = 18.00;
            $taxAmount = $subtotal * ($taxPercentage / 100);
            $totalAmount = $subtotal + $taxAmount;

            $po = TimberPurchaseOrder::firstOrCreate(
                ['po_code' => $poData['po_code'], 'company_id' => $companyId],
                [
                    'org_id' => $orgId,
                    'company_id' => $companyId,
                    'supplier_id' => $supplier->id,
                    'warehouse_id' => $warehouse->id,
                    'po_code' => $poData['po_code'],
                    'status' => $poData['status'],
                    'order_date' => now()->subDays(rand(5, 30)),
                    'expected_delivery_date' => now()->addDays(rand(5, 15)),
                    'subtotal' => $subtotal,
                    'tax_percentage' => $taxPercentage,
                    'tax_amount' => $taxAmount,
                    'total_amount' => $totalAmount,
                    'notes' => 'Demo purchase order',
                    'created_by' => $userId,
                ]
            );

            foreach ($poData['items'] as $item) {
                $woodType = $createdWoodTypes[$item['wood_type_idx']];
                $receivedQty = 0;
                if ($poData['status'] === PurchaseOrderStatus::RECEIVED) {
                    $receivedQty = $item['qty'];
                } elseif ($poData['status'] === PurchaseOrderStatus::PARTIAL_RECEIVED) {
                    $receivedQty = intval($item['qty'] * 0.5);
                }

                TimberPurchaseOrderItem::firstOrCreate(
                    ['purchase_order_id' => $po->id, 'wood_type_id' => $woodType->id],
                    [
                        'purchase_order_id' => $po->id,
                        'wood_type_id' => $woodType->id,
                        'quantity' => $item['qty'],
                        // 'received_quantity' => $receivedQty,
                        'unit' => $woodType->unit,
                        'unit_price' => $item['rate'],
                        'total_price' => $item['qty'] * $item['rate'],
                    ]
                );
            }
        }

        // ============================================
        // Material Requisitions
        // ============================================
        $requisitions = [
            ['status' => 'approved', 'ref' => 'MR-2026-001', 'priority' => 'high', 'notes' => 'Furniture manufacturing - Client order #A105', 'items' => [['wood_type_idx' => 0, 'qty' => 20], ['wood_type_idx' => 2, 'qty' => 15]]],
            ['status' => 'pending', 'ref' => 'MR-2026-002', 'priority' => 'normal', 'notes' => 'Door frames production batch #12', 'items' => [['wood_type_idx' => 1, 'qty' => 40]]],
            ['status' => 'approved', 'ref' => 'MR-2026-003', 'priority' => 'normal', 'notes' => 'Packaging material for export order', 'items' => [['wood_type_idx' => 4, 'qty' => 100], ['wood_type_idx' => 8, 'qty' => 50]]],
            ['status' => 'rejected', 'ref' => 'MR-2026-004', 'priority' => 'low', 'notes' => 'Excessive request - needs revision', 'rejection_reason' => 'Quantity exceeds available stock. Please revise.', 'items' => [['wood_type_idx' => 0, 'qty' => 200]]],
            ['status' => 'issued', 'ref' => 'MR-2026-005', 'priority' => 'urgent', 'notes' => 'Interior woodwork - Commercial project', 'items' => [['wood_type_idx' => 5, 'qty' => 30], ['wood_type_idx' => 9, 'qty' => 80]]],
        ];

        foreach ($requisitions as $mrData) {
            $mr = TimberMaterialRequisition::firstOrCreate(
                ['requisition_code' => $mrData['ref'], 'company_id' => $companyId],
                [
                    'org_id' => $orgId,
                    'company_id' => $companyId,
                    'requisition_code' => $mrData['ref'],
                    'status' => $mrData['status'],
                    'priority' => $mrData['priority'],
                    'notes' => $mrData['notes'],
                    'rejection_reason' => $mrData['rejection_reason'] ?? null,
                    'requisition_date' => now()->subDays(rand(1, 15)),
                    'requested_by' => $userId,
                    'approved_by' => in_array($mrData['status'], ['approved', 'issued']) ? $userId : null,
                    'approved_at' => in_array($mrData['status'], ['approved', 'issued']) ? now()->subDays(rand(1, 5)) : null,
                    'issued_at' => $mrData['status'] === 'issued' ? now()->subDays(rand(1, 3)) : null,
                ]
            );

            foreach ($mrData['items'] as $item) {
                $woodType = $createdWoodTypes[$item['wood_type_idx']];
                TimberMaterialRequisitionItem::firstOrCreate(
                    ['requisition_id' => $mr->id, 'wood_type_id' => $woodType->id],
                    [
                        'requisition_id' => $mr->id,
                        'wood_type_id' => $woodType->id,
                        'requested_quantity' => $item['qty'],
                        'approved_quantity' => in_array($mrData['status'], ['approved', 'issued']) ? $item['qty'] : null,
                        'issued_quantity' => $mrData['status'] === 'issued' ? $item['qty'] : 0,
                        'unit' => $woodType->unit,
                    ]
                );
            }
        }

        // ============================================
        // Stock Alerts (low stock items)
        // ============================================
        $alertLedgerNeem = $createdLedgers['6-2'] ?? null;
        $alertLedgerRubber = $createdLedgers['7-2'] ?? null;

        if ($alertLedgerNeem) {
            TimberStockAlert::firstOrCreate(
                ['wood_type_id' => $createdWoodTypes[6]->id, 'warehouse_id' => $createdWarehouses[2]->id, 'company_id' => $companyId, 'is_resolved' => false],
                [
                    'org_id' => $orgId,
                    'company_id' => $companyId,
                    'wood_type_id' => $createdWoodTypes[6]->id,
                    'warehouse_id' => $createdWarehouses[2]->id,
                    'stock_ledger_id' => $alertLedgerNeem->id,
                    'current_quantity' => 25.00,
                    'threshold' => 50.00,
                    'alert_type' => 'low_stock',
                    'is_resolved' => false,
                ]
            );
        }

        if ($alertLedgerRubber) {
            TimberStockAlert::firstOrCreate(
                ['wood_type_id' => $createdWoodTypes[7]->id, 'warehouse_id' => $createdWarehouses[2]->id, 'company_id' => $companyId, 'is_resolved' => false],
                [
                    'org_id' => $orgId,
                    'company_id' => $companyId,
                    'wood_type_id' => $createdWoodTypes[7]->id,
                    'warehouse_id' => $createdWarehouses[2]->id,
                    'stock_ledger_id' => $alertLedgerRubber->id,
                    'current_quantity' => 60.00,
                    'threshold' => 30.00,
                    'alert_type' => 'low_stock',
                    'is_resolved' => false,
                ]
            );
        }

        // Additional stock movement (OUT type for issued requisition MR-2026-005)
        $ledgerMango = $createdLedgers['5-0'] ?? null;
        if ($ledgerMango) {
            TimberStockMovement::firstOrCreate(
                ['stock_ledger_id' => $ledgerMango->id, 'reference_type' => 'material_requisition', 'notes' => 'Issued for MR-2026-005'],
                [
                    'org_id' => $orgId,
                    'company_id' => $companyId,
                    'stock_ledger_id' => $ledgerMango->id,
                    'wood_type_id' => $createdWoodTypes[5]->id,
                    'warehouse_id' => $createdWarehouses[0]->id,
                    'movement_type' => 'out',
                    'quantity' => 30,
                    'unit' => 'CFT',
                    'reference_type' => 'material_requisition',
                    'reference_id' => null,
                    'unit_cost' => 1200.00,
                    'total_cost' => 36000.00,
                    'before_quantity' => 120.00,
                    'after_quantity' => 90.00,
                    'notes' => 'Issued for MR-2026-005',
                    'movement_date' => now()->subDays(2),
                    'created_by' => $userId,
                ]
            );
        }

        $this->command->info('Timber demo data seeded successfully!');
        $this->command->info('  - ' . count($createdWoodTypes) . ' wood types');
        $this->command->info('  - ' . count($createdSuppliers) . ' suppliers');
        $this->command->info('  - ' . count($createdWarehouses) . ' warehouses');
        $this->command->info('  - ' . count($stockData) . ' stock ledger entries');
        $this->command->info('  - ' . count($purchaseOrders) . ' purchase orders');
        $this->command->info('  - ' . count($requisitions) . ' material requisitions');
        $this->command->info('  - 2 stock alerts');
    }
}
