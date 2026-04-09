<?php

namespace App\Http\Controllers;

use App\Models\EstimationProduct;
use App\Models\EstimationProductsItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EstimationProductsItemController extends Controller
{
    /**
     * Display a paginated listing of estimation product items.
     * Supports filtering by org, company, estimation, estimation_product, product.
     */
    public function index(Request $request): JsonResponse
    {
        $query = EstimationProductsItem::with([
            'organization', 'company', 'estimation', 'product', 'estimationProduct',
        ]);

        // ── Filters ──────────────────────────────────────────────────

        if ($request->filled('org_id')) {
            $query->where('org_id', $request->org_id);
        }

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->filled('estimation_id')) {
            $query->where('estimation_id', $request->estimation_id);
        }

        if ($request->filled('estimation_product_id')) {
            $query->where('estimation_product_id', $request->estimation_product_id);
        }

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        // ── Search by name or product name ───────────────────────────
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhereHas('product', function ($pq) use ($search) {
                      $pq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // ── Sorting ──────────────────────────────────────────────────
        $sortField = $request->input('sort_by', 'created_at');
        $sortDir   = $request->input('sort_dir', 'desc');

        $allowedSorts = [
            'id', 'name', 'length', 'breadth', 'height',
            'thickness', 'quantity', 'item_cft', 'rate',
            'total_amount', 'created_at',
        ];

        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDir === 'asc' ? 'asc' : 'desc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        // ── Pagination ───────────────────────────────────────────────
        $perPage = (int) $request->input('per_page', 15);
        $items   = $query->paginate($perPage);

        return response()->json($items);
    }

    /**
     * Store a newly created estimation product item.
     * Auto-calculates CFT and total_amount, then recalculates parent product total.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'                   => 'nullable|string|max:255',
            'org_id'                 => 'nullable|integer|exists:organizations,id',
            'company_id'             => 'nullable|integer|exists:companies,id',
            'estimation_product_id'  => 'required|integer|exists:estimation_products,id',
            'length'                 => 'nullable|numeric|min:0',
            'breadth'                => 'nullable|numeric|min:0',
            'height'                 => 'nullable|numeric|min:0',
            'thickness'              => 'nullable|numeric|min:0',
            'unit_type'              => 'nullable|string|in:1,2,3,4,5',
            'quantity'               => 'required|integer|min:1',
            'rate'                   => 'nullable|numeric|min:0',
            'item_cft'               => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            // Resolve parent product to fill denormalized fields
            $parentProduct = EstimationProduct::findOrFail($validated['estimation_product_id']);

            $validated['estimation_id'] = $parentProduct->estimation_id;
            $validated['product_id']    = $parentProduct->product_id;
            $validated['org_id']        = $validated['org_id'] ?? $parentProduct->org_id;
            $validated['company_id']    = $validated['company_id'] ?? $parentProduct->company_id;

            $item = EstimationProductsItem::create($validated);

            // Auto-calculate CFT and total_amount
            $item->performCalculations();
            $item->save();

            // Recalculate parent product total from all items
            $parentProduct->recalculateFromItems();

            $item->load(['organization', 'company', 'estimation', 'product', 'estimationProduct']);

            DB::commit();

            return response()->json([
                'message' => 'Item created successfully',
                'data' => $item,
                'parent_product_total' => $parentProduct->total_amount,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to create estimation product item: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified estimation product item.
     */
    public function show(string $id): JsonResponse
    {
        $item = EstimationProductsItem::with([
            'organization', 'company', 'estimation', 'product', 'estimationProduct',
        ])->findOrFail($id);

        return response()->json($item);
    }

    /**
     * Update the specified estimation product item.
     * Auto-recalculates CFT, total_amount, and parent product total.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $item = EstimationProductsItem::findOrFail($id);

        $validated = $request->validate([
            'name'          => 'sometimes|nullable|string|max:255',
            'length'        => 'nullable|numeric|min:0',
            'breadth'       => 'nullable|numeric|min:0',
            'height'        => 'nullable|numeric|min:0',
            'thickness'     => 'nullable|numeric|min:0',
            'unit_type'     => 'sometimes|nullable|string|in:1,2,3,4,5',
            'quantity'      => 'sometimes|integer|min:1',
            'rate'          => 'nullable|numeric|min:0',
            'item_cft'      => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $item->fill($validated);

            // Recalculate CFT and total_amount
            $item->performCalculations();
            $item->save();

            // Recalculate parent product total
            $parentProduct = $item->estimationProduct;
            if ($parentProduct) {
                $parentProduct->recalculateFromItems();
            }

            $item->load(['organization', 'company', 'estimation', 'product', 'estimationProduct']);

            DB::commit();

            return response()->json([
                'message' => 'Item updated successfully',
                'data' => $item,
                'parent_product_total' => $parentProduct?->total_amount,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to update estimation product item: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified estimation product item from storage.
     * Recalculates parent product total after deletion.
     */
    public function destroy(string $id): JsonResponse
    {
        $item = EstimationProductsItem::findOrFail($id);
        $parentProduct = $item->estimationProduct;

        $item->delete();

        // Recalculate parent product total
        if ($parentProduct) {
            $parentProduct->recalculateFromItems();
        }

        return response()->json([
            'message' => 'Item deleted successfully',
            'parent_product_total' => $parentProduct?->total_amount,
        ], 200);
    }
}
