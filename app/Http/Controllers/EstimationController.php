<?php

namespace App\Http\Controllers;

use App\Enums\EstimationStatus;
use App\Models\EstimationCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EstimationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = \App\Models\Estimation::with(['product', 'customer', 'project'])
            ->withCount('collections');

        // Filter by org_id if provided
        if ($request->has('org_id')) {
            $query->where('org_id', $request->org_id);
        }

        // Filter by company_id if provided
        if ($request->has('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        // Filter by project_id if provided
        if ($request->has('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        // Filter by customer_id if provided
        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        // Filter by product_id if provided
        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        // Filter by status if provided
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Order by latest first
        $query->orderBy('created_at', 'desc');

        $estimations = $query->get();

        // Append total_collected_cft to each estimation
        $estimations->each(function ($estimation) {
            $estimation->total_collected_cft = $estimation->total_collected_cft ?? 0;
            $estimation->remaining_cft = $estimation->remaining_cft ?? 0;
        });

        return response()->json($estimations);
    }

    /**
     * Store a newly created resource in storage.
     * Supports single, bulk, and multi-product (items[]) estimation creation.
     */
    public function store(Request $request)
    {
        // Handle bulk estimations (legacy format)
        if ($request->has('estimations') && is_array($request->estimations)) {
            return $this->storeBulk($request);
        }

        // Handle multi-product items[] format (new CRM-style)
        if ($request->has('items') && is_array($request->items)) {
            return $this->storeWithItems($request);
        }

        $validated = $request->validate([
            'org_id' => 'nullable|integer',
            'company_id' => 'nullable|integer',
            'customer_id' => 'required|exists:customers,id',
            'project_id' => 'nullable|exists:projects,id',
            'product_id' => 'nullable|integer|exists:products,id',
            'product_name' => 'nullable|string|required_if:product_id,null|max:255',
            'description' => 'nullable|string',
            'estimation_type' => 'required|integer',
            'length' => 'nullable|numeric|min:0',
            'breadth' => 'nullable|numeric|min:0',
            'height' => 'nullable|numeric|min:0',
            'thickness' => 'nullable|numeric|min:0',
            'quantity' => 'nullable|integer|min:0',
            'cft' => 'nullable|numeric|min:0',
            'cost_per_cft' => 'nullable|numeric|min:0',
            'labor_charges' => 'nullable|numeric|min:0',
            'total_amount' => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            // Create or use existing product
            $productId = $validated['product_id'] ?? null;
            if (empty($productId)) {
                $product = \App\Models\Product::create([
                    'name' => $validated['product_name'],
                    'description' => null,
                ]);
                $productId = $product->id;
            }

            // Prepare estimation data - remove product_name as it's not a column in estimations table
            $estimationData = [
                'org_id' => $validated['org_id'] ?? null,
                'company_id' => $validated['company_id'] ?? null,
                'customer_id' => $validated['customer_id'],
                'project_id' => $validated['project_id'] ?? null,
                'product_id' => $productId,
                'description' => $validated['description'] ?? null,
                'estimation_type' => $validated['estimation_type'],
                'length' => $validated['length'] ?? null,
                'breadth' => $validated['breadth'] ?? null,
                'height' => $validated['height'] ?? null,
                'thickness' => $validated['thickness'] ?? null,
                'quantity' => $validated['quantity'] ?? null,
                'cft' => $validated['cft'] ?? null,
                'cost_per_cft' => $validated['cost_per_cft'] ?? null,
                'labor_charges' => $validated['labor_charges'] ?? null,
                'total_amount' => $validated['total_amount'] ?? 0,
            ];

            // Skip calculation for Direct Amount mode (type 5)
            if (intval($validated['estimation_type']) !== 5) {
                $calculations = $this->calculateCftAndTotal($estimationData);
                $estimationData['cft'] = $calculations['cft'];
                $estimationData['total_amount'] = $calculations['total_amount'];
            } else {
                // Direct Amount mode - set CFT to null
                $estimationData['cft'] = null;
            }

            $estimation = \App\Models\Estimation::create($estimationData);
            $estimation->load(['product', 'customer', 'project']);

            DB::commit();

            return response()->json($estimation, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Store estimation with multiple product items (CRM-style).
     * Creates one estimation and optionally stores items in estimation_items table.
     */
    private function storeWithItems(Request $request)
    {
        $validated = $request->validate([
            'org_id' => 'nullable|integer',
            'company_id' => 'nullable|integer',
            'customer_id' => 'required|exists:customers,id',
            'project_id' => 'nullable|exists:projects,id',
            'description' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'nullable|integer|exists:products,id',
            'items.*.product_name' => 'nullable|string|max:255',
            'items.*.description' => 'nullable|string',
            'items.*.quantity' => 'nullable|integer|min:1',
        ]);

        DB::beginTransaction();

        try {
            $estimation = \App\Models\Estimation::create([
                'org_id' => $validated['org_id'] ?? null,
                'company_id' => $validated['company_id'] ?? null,
                'customer_id' => $validated['customer_id'],
                'project_id' => $validated['project_id'] ?? null,
                'description' => $validated['description'] ?? null,
                'status' => 'draft',
            ]);

            if (isset($validated['items']) && is_array($validated['items'])) {
                foreach ($validated['items'] as $item) {
                    // Resolve product_id: use existing or create new product inline
                    $productId = $item['product_id'] ?? null;
                    if (empty($productId) && !empty($item['product_name'])) {
                        $product = \App\Models\Product::create([
                            'name' => $item['product_name'],
                            'description' => null,
                        ]);
                        $productId = $product->id;
                    }

                    // Store first item's product_id on main estimation for backward compatibility
                    if (!$estimation->product_id && $productId) {
                        $estimation->product_id = $productId;
                        $estimation->save();
                    }

                    // Store in estimation_items table if the model exists
                    if (class_exists(\App\Models\EstimationItem::class)) {
                        \App\Models\EstimationItem::create([
                            'estimation_id' => $estimation->id,
                            'product_id' => $productId,
                            'description' => $item['description'] ?? null,
                            'quantity' => $item['quantity'] ?? 1,
                        ]);
                    }
                }
            }

            DB::commit();

            return response()->json(
                $estimation->load(['customer', 'project', 'product']),
                201
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Store bulk estimations with optional customer/project/product creation.
     */
    private function storeBulk(Request $request)
    {
        $validated = $request->validate([
            'org_id' => 'nullable|integer',
            'company_id' => 'nullable|integer',
            'customer_id' => 'nullable|integer|exists:customers,id',
            'customer_name' => 'nullable|string|required_if:customer_id,null|max:255',
            'project_id' => 'nullable|integer|exists:projects,id',
            'project_name' => 'nullable|string|required_if:project_id,null|max:255',
            'description' => 'nullable|string',
            'estimations' => 'required|array|min:1',
            'estimations.*.product_id' => 'nullable|integer|exists:products,id',
            'estimations.*.product_name' => 'nullable|string|required_if:estimations.*.product_id,null|max:255',
            'estimations.*.estimation_type' => 'required|integer',
            'estimations.*.length' => 'nullable|numeric|min:0',
            'estimations.*.breadth' => 'nullable|numeric|min:0',
            'estimations.*.height' => 'nullable|numeric|min:0',
            'estimations.*.thickness' => 'nullable|numeric|min:0',
            'estimations.*.quantity' => 'nullable|integer|min:0',
            'estimations.*.cost_per_cft' => 'nullable|numeric|min:0',
            'estimations.*.labor_charges' => 'nullable|numeric|min:0',
            'estimations.*.total_amount' => 'nullable|numeric|min:0',
        ]);

        // Create or use existing customer
        $customerId = $validated['customer_id'];
        if (empty($customerId)) {
            $customer = \App\Models\Customer::create([
                'name' => $validated['customer_name'],
            ]);
            $customerId = $customer->id;
        }

        // Create or use existing project
        $projectId = $validated['project_id'];
        if (empty($projectId) && !empty($validated['project_name'])) {
            $project = \App\Models\Project::create([
                'name' => $validated['project_name'],
                'description' => null,
            ]);
            $projectId = $project->id;
        }

        // Create products and estimations
        $createdEstimations = [];
        $totalAmount = 0;

        foreach ($validated['estimations'] as $estimationData) {
            // Create or use existing product
            $productId = $estimationData['product_id'];
            if (empty($productId)) {
                $product = \App\Models\Product::create([
                    'name' => $estimationData['product_name'],
                    'description' => null,
                ]);
                $productId = $product->id;
            }

            // Skip calculation for Direct Amount mode (type 5)
            $estimationType = intval($estimationData['estimation_type']);
            if ($estimationType !== 5) {
                // Calculate CFT and total
                $calculations = $this->calculateCftAndTotal($estimationData);
                $cft = $calculations['cft'];
                $estimationTotal = $calculations['total_amount'];
            } else {
                // Direct Amount mode
                $cft = null;
                $estimationTotal = $estimationData['total_amount'] ?? 0;
            }

            // Create estimation
            $estimation = \App\Models\Estimation::create([
                'org_id' => $validated['org_id'] ?? null,
                'company_id' => $validated['company_id'] ?? null,
                'customer_id' => $customerId,
                'project_id' => $projectId,
                'product_id' => $productId,
                'description' => $validated['description'] ?? null,
                'estimation_type' => $estimationData['estimation_type'],
                'length' => $estimationData['length'] ?? null,
                'breadth' => $estimationData['breadth'] ?? null,
                'height' => $estimationData['height'] ?? null,
                'thickness' => $estimationData['thickness'] ?? null,
                'quantity' => $estimationData['quantity'] ?? null,
                'cft' => $cft,
                'cost_per_cft' => $estimationData['cost_per_cft'] ?? null,
                'labor_charges' => $estimationData['labor_charges'] ?? null,
                'total_amount' => $estimationTotal,
            ]);

            $estimation->load(['product', 'customer', 'project']);
            $createdEstimations[] = $estimation;
            $totalAmount += $estimationTotal;
        }

        return response()->json([
            'message' => 'Estimations created successfully',
            'data' => $createdEstimations,
            'total_amount' => $totalAmount,
            'customer_id' => $customerId,
            'project_id' => $projectId,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $estimation = \App\Models\Estimation::with(['product', 'customer', 'project'])->findOrFail($id);
        return response()->json($estimation);
    }

    /**
     * Update the specified resource in storage.
     * Supports both legacy single-product and new multi-product (items[]) formats.
     */
    public function update(Request $request, string $id)
    {
        $estimation = \App\Models\Estimation::findOrFail($id);

        $validated = $request->validate([
            'org_id' => 'nullable|integer',
            'company_id' => 'nullable|integer',
            'customer_id' => 'sometimes|exists:customers,id',
            'project_id' => 'nullable|exists:projects,id',
            'product_id' => 'sometimes|exists:products,id',
            'description' => 'nullable|string',
            'estimation_type' => 'sometimes|integer',
            'length' => 'nullable|numeric|min:0',
            'breadth' => 'nullable|numeric|min:0',
            'height' => 'nullable|numeric|min:0',
            'thickness' => 'nullable|numeric|min:0',
            'quantity' => 'nullable|integer|min:0',
            'cft' => 'nullable|numeric|min:0',
            'cost_per_cft' => 'nullable|numeric|min:0',
            'labor_charges' => 'nullable|numeric|min:0',
            'total_amount' => 'nullable|numeric|min:0',
            'items' => 'nullable|array',
            'items.*.product_id' => 'nullable|integer|exists:products,id',
            'items.*.product_name' => 'nullable|string|max:255',
            'items.*.description' => 'nullable|string',
            'items.*.quantity' => 'nullable|integer|min:1',
        ]);

        DB::beginTransaction();
        try {
            // Handle items[] if provided (multi-product update)
            if ($request->has('items') && is_array($request->items)) {
                // Clear existing estimation items if the model exists
                if (class_exists(\App\Models\EstimationItem::class)) {
                    \App\Models\EstimationItem::where('estimation_id', $estimation->id)->delete();
                }

                $firstProductId = null;
                foreach ($validated['items'] as $item) {
                    // Resolve product_id: use existing or create new product inline
                    $productId = $item['product_id'] ?? null;
                    if (empty($productId) && !empty($item['product_name'])) {
                        $product = \App\Models\Product::create([
                            'name' => $item['product_name'],
                            'description' => null,
                        ]);
                        $productId = $product->id;
                    }

                    // Track first product for backward compatibility
                    if (!$firstProductId && $productId) {
                        $firstProductId = $productId;
                    }

                    // Store in estimation_items table if the model exists
                    if (class_exists(\App\Models\EstimationItem::class)) {
                        \App\Models\EstimationItem::create([
                            'estimation_id' => $estimation->id,
                            'product_id' => $productId,
                            'description' => $item['description'] ?? null,
                            'quantity' => $item['quantity'] ?? 1,
                        ]);
                    }
                }

                // Update main estimation product_id for backward compatibility
                if ($firstProductId) {
                    $validated['product_id'] = $firstProductId;
                }

                // Remove items from validated since it's not a column
                unset($validated['items']);
            }

            $fullData = array_merge($estimation->toArray(), $validated);

            // Skip calculation for Direct Amount mode (type 5) or when estimation_type is not set
            $estimationType = intval($fullData['estimation_type'] ?? $estimation->estimation_type ?? 0);
            if ($estimationType && $estimationType !== 5) {
                $calculations = $this->calculateCftAndTotal($fullData);
                $validated['cft'] = $calculations['cft'];
                $validated['total_amount'] = $calculations['total_amount'];
            } elseif ($estimationType === 5) {
                // Direct Amount mode - set CFT to null
                $validated['cft'] = null;
            }

            // Remove items key if still present
            unset($validated['items']);

            $estimation->update($validated);
            $estimation->load(['product', 'customer', 'project']);

            DB::commit();

            return response()->json($estimation);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $estimation = \App\Models\Estimation::findOrFail($id);
        $estimation->delete();

        return response()->json(null, 204);
    }

    /**
     * Helper to perform consistent calculation for CFT and Totals.
     */
    private function calculateCftAndTotal(array $data)
    {
        $l = !empty($data['length']) ? floatval($data['length']) : 1;
        $b = !empty($data['breadth']) ? floatval($data['breadth']) : 1;
        $h = !empty($data['height']) ? floatval($data['height']) : 1;
        $t = !empty($data['thickness']) ? floatval($data['thickness']) : 1;
        $q = !empty($data['quantity']) ? floatval($data['quantity']) : 1;

        $type = !empty($data['estimation_type']) ? intval($data['estimation_type']) : 1;
        $cftPerUnit = 0;

        if ($type === 1) {
            $cftPerUnit = ($l * $b * $h) / 144;
        } elseif ($type === 2) {
            $cftPerUnit = $l * $b * $h;
        } elseif ($type === 3) {
            $cftPerUnit = ($l * $b * $t) / 12;
        } elseif ($type === 4) {
            $cftPerUnit = $l * $b * $t;
        } else {
            $cftPerUnit = ($l * $b * $h) / 144;
        }

        $calcCft = $cftPerUnit * $q;

        if (!empty($data['length']) || !empty($data['breadth']) || !empty($data['height']) || !empty($data['thickness']) || !empty($data['quantity'])) {
            $finalCft = $calcCft;
        } else {
            $finalCft = !empty($data['cft']) ? floatval($data['cft']) : 0;
        }

        $cost = !empty($data['cost_per_cft']) ? floatval($data['cost_per_cft']) : 0;
        $labor = !empty($data['labor_charges']) ? floatval($data['labor_charges']) : 0;

        $total = ($finalCft * $cost) + $labor;

        return [
            'cft' => round($finalCft, 2),
            'total_amount' => round($total, 2)
        ];
    }

    /**
     * Approve an estimation.
     */
    public function approve(string $id)
    {
        $estimation = \App\Models\Estimation::findOrFail($id);

        if (!$estimation->canBeApproved()) {
            return response()->json([
                'message' => 'Estimation cannot be approved. Current status: ' . $estimation->status?->label()
            ], 422);
        }

        $estimation->approve();

        return response()->json([
            'message' => 'Estimation approved successfully',
            'data' => $estimation->load(['product', 'customer', 'project'])
        ]);
    }

    /**
     * Cancel an estimation.
     */
    public function cancel(string $id)
    {
        $estimation = \App\Models\Estimation::findOrFail($id);

        $estimation->cancel();

        return response()->json([
            'message' => 'Estimation cancelled successfully',
            'data' => $estimation->load(['product', 'customer', 'project'])
        ]);
    }

    /**
     * Mark estimation as collected.
     */
    public function markAsCollected(string $id)
    {
        $estimation = \App\Models\Estimation::findOrFail($id);

        if (!$estimation->canCollectMaterial()) {
            return response()->json([
                'message' => 'Only approved or partially collected estimations can be marked as collected'
            ], 422);
        }

        $estimation->markAsCollected();

        return response()->json([
            'message' => 'Estimation marked as collected',
            'data' => $estimation->load(['product', 'customer', 'project'])
        ]);
    }

    /**
     * Collect material for an estimation.
     */
    public function collectMaterial(Request $request, string $id)
    {
        $estimation = \App\Models\Estimation::findOrFail($id);

        if (!$estimation->canCollectMaterial()) {
            return response()->json([
                'message' => 'Material can only be collected for approved or partially collected estimations'
            ], 422);
        }

        $validated = $request->validate([
            'wood_type_id' => 'required|exists:timber_wood_types,id',
            'warehouse_id' => 'required|exists:timber_warehouses,id',
            'quantity_cft' => 'required|numeric|min:0.001',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Check if stock is available
        $stockLedger = \App\Models\Timber\TimberStockLedger::where('wood_type_id', $validated['wood_type_id'])
            ->where('warehouse_id', $validated['warehouse_id'])
            ->first();

        if (!$stockLedger || $stockLedger->available_quantity < $validated['quantity_cft']) {
            $available = $stockLedger ? $stockLedger->available_quantity : 0;
            return response()->json([
                'message' => "Insufficient stock. Available: {$available} CFT, Required: {$validated['quantity_cft']} CFT"
            ], 422);
        }

        // Create collection record within a transaction
        DB::beginTransaction();
        try {
            $collection = EstimationCollection::create([
                'estimation_id' => $estimation->id,
                'wood_type_id' => $validated['wood_type_id'],
                'warehouse_id' => $validated['warehouse_id'],
                'quantity_cft' => $validated['quantity_cft'],
                'notes' => $validated['notes'] ?? null,
                'collected_at' => now(),
                'collected_by' => auth()->id(),
            ]);

            // Create stock movement and deduct stock
            $collection->createStockMovement();

            // Update estimation status if needed
            $estimation->updateStatusBasedOnCollection();

            DB::commit();

            return response()->json([
                'message' => 'Material collected successfully',
                'data' => $collection->load(['woodType', 'warehouse', 'collectedBy']),
                'estimation_status' => $estimation->fresh()->status
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to collect material: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get collection history for an estimation.
     */
    public function getCollections(string $id)
    {
        $estimation = \App\Models\Estimation::with(['collections.woodType', 'collections.warehouse', 'collections.collectedBy'])
            ->findOrFail($id);

        $totalCollected = $estimation->collections->sum('quantity_cft');

        return response()->json([
            'estimation_id' => $estimation->id,
            'estimated_cft' => $estimation->cft,
            'total_collected_cft' => $totalCollected,
            'remaining_cft' => max(0, $estimation->cft - $totalCollected),
            'status' => $estimation->status,
            'collections' => $estimation->collections
        ]);
    }

    /**
     * Get available stock for a wood type and warehouse.
     */
    public function getAvailableStock(Request $request)
    {
        $validated = $request->validate([
            'wood_type_id' => 'required|exists:timber_wood_types,id',
            'warehouse_id' => 'nullable|exists:timber_warehouses,id',
        ]);

        $query = \App\Models\Timber\TimberStockLedger::with(['woodType', 'warehouse'])
            ->where('wood_type_id', $validated['wood_type_id']);

        if (isset($validated['warehouse_id'])) {
            $query->where('warehouse_id', $validated['warehouse_id']);
        }

        $stockLedgers = $query->get();

        return response()->json([
            'wood_type_id' => $validated['wood_type_id'],
            'stock' => $stockLedgers->map(function ($ledger) {
                return [
                    'warehouse_id' => $ledger->warehouse_id,
                    'warehouse_name' => $ledger->warehouse?->name,
                    'current_quantity' => $ledger->current_quantity,
                    'reserved_quantity' => $ledger->reserved_quantity,
                    'available_quantity' => $ledger->available_quantity,
                ];
            })
        ]);
    }
}
