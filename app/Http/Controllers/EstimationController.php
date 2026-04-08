<?php

namespace App\Http\Controllers;

use App\Enums\EstimationStatus;
use App\Http\Requests\StoreEstimationRequest;
use App\Models\Estimation;
use App\Services\EstimationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class EstimationController extends Controller
{
    /**
     * Estimation service instance.
     */
    protected EstimationService $estimationService;

    /**
     * Create a new service instance.
     */
    public function __construct(EstimationService $estimationService)
    {
        $this->estimationService = $estimationService;
    }
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
     * Creates a complete estimation with products and other charges in a single transaction.
     * Also supports legacy multi-product items[] format via storeWithItems().
     *
     * @param \App\Http\Requests\StoreEstimationRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreEstimationRequest $request): JsonResponse
    {
        // Handle legacy multi-product items[] format (CRM-style)
        if ($request->has('items') && is_array($request->items)) {
            return $this->storeWithItems($request);
        }

        try {
            \Log::info('Estimation creation request received', [
                'data' => $request->validated(),
                'has_attachments' => isset($request->validated()['attachments']),
                'attachments_count' => isset($request->validated()['attachments']) ? count($request->validated()['attachments']) : 0,
            ]);

            $result = $this->estimationService->storeCompleteEstimation($request->validated());

            return response()->json([
                'message' => 'Estimation created successfully',
                'data' => [
                    'estimation' => $result['estimation'],
                    'products' => $result['products'],
                    'other_charges' => $result['other_charges'],
                    'total_cft' => $result['total_cft'],
                    'summary' => [
                        'total_products' => $result['products']->count(),
                        'total_amount' => $result['products']->sum('total_amount'),
                        'grand_total' => $result['grand_total'] ?? 0,
                    ],
                ]
            ], 201);
        } catch (\Illuminate\Database\QueryException $e) {
            \Log::error('Database error in estimation creation', [
                'message' => $e->getMessage(),
                'sql' => $e->getSql(),
                'bindings' => $e->getBindings(),
            ]);
            return response()->json([
                'message' => 'Database error: ' . $e->getMessage(),
                'error' => config('app.debug') ? $e->getMessage() : 'A database error occurred.',
            ], 500);
        } catch (\Exception $e) {
            \Log::error('Error in estimation creation', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'message' => 'Failed to create estimation: ' . $e->getMessage(),
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while creating the estimation.',
            ], 500);
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
        $estimation = \App\Models\Estimation::with([
            'project',
            'customer',
            'products.product',
            'otherCharge',
            'attachments'
        ])->findOrFail($id);

        return response()->json($estimation);
    }

    /**
     * Update the specified resource in storage.
     * Updates complete estimation with products and charges in a single transaction.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            \Log::info('Estimation update request received', [
                'estimation_id' => $id,
                'data' => $request->all(),
            ]);

            $validated = $request->validate([
                // Basic info
                'description' => 'nullable|string',
                'additional_notes' => 'nullable|string',
                'status' => 'nullable|string|in:draft,approved,partially_collected,collected,cancelled',

                // Products array (for complete update)
                'products' => 'nullable|array',
                'products.*.product_id' => 'nullable|integer|exists:products,id',
                'products.*.length' => 'nullable|numeric',
                'products.*.breadth' => 'nullable|numeric',
                'products.*.height' => 'nullable|numeric',
                'products.*.thickness' => 'nullable|numeric',
                'products.*.cft_calculation_type' => 'nullable|string|in:1,2,3,4,5',
                'products.*.quantity' => 'nullable|integer|min:1',
                'products.*.cft' => 'nullable|numeric',
                'products.*.rate' => 'nullable|numeric',
                'products.*.total_amount' => 'nullable|numeric',

                // Other charges
                'labour_charges' => 'nullable|numeric|min:0',
                'transport_handling' => 'nullable|numeric|min:0',
                'discount' => 'nullable|numeric|min:0',
                'tax' => 'nullable|numeric|min:0',
                'total_cft' => 'nullable|numeric|min:0',

                // Attachments
                'attachments' => 'nullable|array',
                'attachments.*' => 'nullable|string',
            ]);

            $result = $this->estimationService->updateCompleteEstimation((int) $id, $validated);

            return response()->json([
                'message' => 'Estimation updated successfully',
                'data' => [
                    'estimation' => $result['estimation'],
                    'products' => $result['products'],
                    'other_charges' => $result['other_charges'],
                    'summary' => [
                        'total_products' => $result['products']->count(),
                        'total_amount' => $result['products']->sum('total_amount'),
                        'grand_total' => $result['grand_total'] ?? 0,
                    ],
                ]
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Estimation not found',
                'error' => 'The requested estimation does not exist.',
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error in estimation update', [
                'estimation_id' => $id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'message' => 'Failed to update estimation: ' . $e->getMessage(),
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while updating the estimation.',
            ], 500);
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
            'additional_notes' => 'nullable|string|max:1000',
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
                'additional_notes' => $validated['additional_notes'] ?? null,
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
