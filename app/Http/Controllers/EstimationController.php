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
        $query = \App\Models\Estimation::with(['project', 'customer']);

        // Filter by org_id if provided
        if ($request->has('org_id')) {
            $query->where('org_id', $request->org_id);
        }

        // Filter by company_id if provided
        if ($request->has('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        // Filter by customer_id if provided
        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        // Filter by project_id if provided
        if ($request->has('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        // Filter by status if provided
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Order by latest first
        $query->orderBy('created_at', 'desc');

        $estimations = $query->get();

        return response()->json($estimations);
    }

    /**
     * Store a newly created resource in storage.
     * Supports single and multi-product (items[]) estimation creation.
     */
    public function store(Request $request)
    {
        // Handle multi-product items[] format (new CRM-style)
        if ($request->has('items') && is_array($request->items)) {
            return $this->storeWithItems($request);
        }

        $validated = $request->validate([
            'org_id' => 'nullable|integer',
            'company_id' => 'nullable|integer',
            'customer_id' => 'required|exists:customers,id',
            'project_id' => 'required|integer|exists:projects,id',
            'description' => 'nullable|string',
            'status' => 'nullable|string|in:draft,pending,approved,rejected',
        ]);

        DB::beginTransaction();
        try {
            $estimation = \App\Models\Estimation::create([
                'org_id' => $validated['org_id'] ?? null,
                'company_id' => $validated['company_id'] ?? null,
                'customer_id' => $validated['customer_id'],
                'project_id' => $validated['project_id'],
                'description' => $validated['description'] ?? null,
                'status' => $validated['status'] ?? EstimationStatus::Draft->value,
            ]);

            $estimation->load(['project', 'customer']);

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
            'project_id' => 'nullable|integer|exists:projects,id',
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
                $estimation->load(['customer', 'project']),
                201
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $estimation = \App\Models\Estimation::with(['project', 'customer'])->findOrFail($id);
        return response()->json($estimation);
    }

    /**
     * Update the specified resource in storage.
     * Supports both single-product and multi-product (items[]) formats.
     */
    public function update(Request $request, string $id)
    {
        $estimation = \App\Models\Estimation::findOrFail($id);

        $validated = $request->validate([
            'org_id' => 'nullable|integer',
            'company_id' => 'nullable|integer',
            'customer_id' => 'sometimes|exists:customers,id',
            'project_id' => 'sometimes|exists:projects,id',
            'description' => 'nullable|string',
            'status' => 'nullable|string|in:draft,pending,approved,rejected',
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

                // Remove items from validated since it's not a column
                unset($validated['items']);
            }

            $estimation->update($validated);
            $estimation->load(['project', 'customer']);

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
            'data' => $estimation->load(['project', 'customer'])
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
            'data' => $estimation->load(['project', 'customer'])
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
            'data' => $estimation->load(['project', 'customer'])
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
            'total_collected_cft' => $totalCollected,
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
