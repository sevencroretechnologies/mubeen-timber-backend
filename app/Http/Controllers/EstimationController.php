<?php

namespace App\Http\Controllers;

use App\Enums\EstimationStatus;
use App\Http\Requests\StoreEstimationRequest;
use App\Models\Estimation;
use App\Models\EstimationCollection;
use App\Services\EstimationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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
        $relations = ['project', 'customer'];
        if ($request->has('project_id') || $request->boolean('with_details')) {
            $relations = array_merge($relations, ['products.product', 'products.items', 'otherCharge']);
        }

        $query = Estimation::with($relations);

        if ($request->has('org_id')) {
            $query->where('org_id', $request->org_id);
        }

        if ($request->has('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->has('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $query->orderBy('created_at', 'desc');

        $estimations = $query->get();

        return response()->json($estimations);
    }

    /**
     * Store a newly created resource in storage.
     * Creates a complete estimation with products, items, and other charges in a single transaction.
     *
     * @param \App\Http\Requests\StoreEstimationRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreEstimationRequest $request): JsonResponse
    {
        try {
            \Log::info('Estimation creation request received', [
                'data' => $request->validated(),
                'has_attachments' => isset($request->validated()['attachments']),
                'attachments_count' => isset($request->validated()['attachments']) ? count($request->validated()['attachments']) : 0,
            ]);

            $dataForService = $request->validated();
            unset($dataForService['attachments']);
            $result = $this->estimationService->storeCompleteEstimation($dataForService);
            $estimation = $result['estimation'];

            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('estimations', 'public');
                    DB::table('estimation_attachments')->insert([
                        'estimation_id' => $estimation->id,
                        'org_id' => $estimation->org_id,
                        'company_id' => $estimation->company_id,
                        'image' => $path,
                        'description' => $file->getClientOriginalName(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

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
                    'attachments' => DB::table('estimation_attachments')
                        ->where('estimation_id', $estimation->id)
                        ->whereNull('deleted_at')
                        ->get()
                        ->map(function ($file) {
                            return [
                                'id' => $file->id,
                                'url' => asset('storage/' . $file->image),
                                'name' => $file->description,
                            ];
                        }),
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
     * Display the specified resource with full details and summary.
     */
    public function show(string $id): JsonResponse
    {
        $estimation = Estimation::with([
            'project',
            'customer',
            'products.product',
            'products.items',
            'otherCharge',
            'attachments'
        ])->findOrFail($id);

        // Calculate summary from items
        $productsTotal = $estimation->products->sum(function ($p) {
            return $p->total_amount ?? 0;
        });

        $totalCft = 0;
        foreach ($estimation->products as $product) {
            foreach ($product->items as $item) {
                $totalCft += ($item->item_cft ?? 0) * ($item->quantity ?? 1);
            }
        }

        $chargesTotal = 0;
        if ($estimation->otherCharge) {
            $chargesTotal += ($estimation->otherCharge->labour_charges ?? 0);
            $chargesTotal += ($estimation->otherCharge->transport_and_handling ?? 0);
            $chargesTotal += ($estimation->otherCharge->approximate_tax ?? 0);
            $chargesTotal -= ($estimation->otherCharge->discount ?? 0);
        }

        // Attachments from DB
        $attachments = DB::table('estimation_attachments')
            ->where('estimation_id', $estimation->id)
            ->whereNull('deleted_at')
            ->get()
            ->map(function ($file) {
                return [
                    'id' => $file->id,
                    'url' => asset('storage/' . $file->image),
                    'name' => $file->description,
                ];
            });

        return response()->json([
            'data' => $estimation,
            'summary' => [
                'total_products' => $estimation->products->count(),
                'total_items' => $estimation->products->sum(fn($p) => $p->items->count()),
                'total_cft' => round($totalCft, 2),
                'products_total' => round($productsTotal, 2),
                'charges_total' => round($chargesTotal, 2),
                'grand_total' => round($estimation->grand_total ?? 0, 2),
                'status' => $estimation->status?->value ?? $estimation->status,
                'status_label' => $estimation->status?->label() ?? 'N/A',
            ],
            'other_charges' => $estimation->otherCharge ? [
                'labour_charges' => $estimation->otherCharge->labour_charges ?? 0,
                'transport_and_handling' => $estimation->otherCharge->transport_and_handling ?? 0,
                'discount' => $estimation->otherCharge->discount ?? 0,
                'approximate_tax' => $estimation->otherCharge->approximate_tax ?? 0,
                'overall_total_cft' => $estimation->otherCharge->overall_total_cft ?? 0,
                'other_description' => $estimation->otherCharge->other_description,
                'other_description_amount' => $estimation->otherCharge->other_description_amount ?? 0,
            ] : null,
            'attachments' => $attachments,
        ]);
    }

    /**
     * Update the specified resource in storage.
     * Updates complete estimation with products, items, and charges in a single transaction.
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
                'status' => 'nullable|string|in:draft,approved,pending,collected,cancelled',

                // Products array (basic)
                'products' => 'nullable|array',
                'products.*.id' => 'nullable|integer|exists:estimation_products,id',
                'products.*.product_id' => 'nullable|integer|exists:products,id',
                'deleted_product_ids' => 'nullable|array',
                'deleted_product_ids.*' => 'integer|exists:estimation_products,id',

                // Items array (nested inside each product)
                'products.*.items' => 'nullable|array',
                'products.*.items.*.id' => 'nullable|integer|exists:estimation_products_item,id',
                'products.*.items.*.name' => 'nullable|string|max:255',
                'products.*.items.*.length' => 'nullable|numeric',
                'products.*.items.*.breadth' => 'nullable|numeric',
                'products.*.items.*.height' => 'nullable|numeric',
                'products.*.items.*.thickness' => 'nullable|numeric',
                'products.*.items.*.unit_type' => 'nullable|string|in:1,2,3,4,5',
                'products.*.items.*.quantity' => 'nullable|integer|min:1',
                'products.*.items.*.rate' => 'nullable|numeric',
                'products.*.items.*.item_cft' => 'nullable|numeric',
                'products.*.deleted_item_ids' => 'nullable|array',
                'products.*.deleted_item_ids.*' => 'integer',

                // Other charges
                'labour_charges' => 'nullable|numeric|min:0',
                'transport_handling' => 'nullable|numeric|min:0',
                'discount' => 'nullable|numeric|min:0',
                'tax' => 'nullable|numeric|min:0',
                'total_cft' => 'nullable|numeric|min:0',

                // Attachments
                'attachments' => 'nullable|array',
                'attachments.*' => 'nullable',
                'deleted_attachment_ids' => 'nullable|array',
                'deleted_attachment_ids.*' => 'integer|exists:estimation_attachments,id',
            ]);

            $dataForService = $validated;
            unset($dataForService['attachments']);
            unset($dataForService['deleted_attachment_ids']);

            $result = $this->estimationService->updateCompleteEstimation((int) $id, $dataForService);
            $estimation = $result['estimation'];

            // Handle deleted attachments
            if (!empty($request->deleted_attachment_ids)) {
                $attachmentsToDelete = DB::table('estimation_attachments')
                    ->whereIn('id', $request->deleted_attachment_ids)
                    ->get();

                foreach ($attachmentsToDelete as $file) {
                    if ($file->image) {
                        Storage::disk('public')->delete($file->image);
                    }
                }

                DB::table('estimation_attachments')
                    ->whereIn('id', $request->deleted_attachment_ids)
                    ->delete();
            }

            // Handle new attachments
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('estimations', 'public');
                    DB::table('estimation_attachments')->insert([
                        'estimation_id' => $estimation->id,
                        'org_id' => $estimation->org_id,
                        'company_id' => $estimation->company_id,
                        'image' => $path,
                        'description' => $file->getClientOriginalName(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

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
                    'attachments' => DB::table('estimation_attachments')
                        ->where('estimation_id', $estimation->id)
                        ->whereNull('deleted_at')
                        ->get()
                        ->map(function ($file) {
                            return [
                                'id' => $file->id,
                                'url' => asset('storage/' . $file->image),
                                'name' => $file->description,
                            ];
                        }),
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
        $estimation = Estimation::findOrFail($id);
        $estimation->delete();

        return response()->json(null, 204);
    }

    /**
     * Approve an estimation.
     */
    public function approve(string $id)
    {
        $estimation = Estimation::findOrFail($id);

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
        $estimation = Estimation::findOrFail($id);

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
        $estimation = Estimation::findOrFail($id);

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
        $estimation = Estimation::findOrFail($id);

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
        $estimation = Estimation::with(['collections.woodType', 'collections.warehouse', 'collections.collectedBy'])
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
