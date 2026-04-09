<?php

namespace App\Http\Controllers;

use App\Models\EstimationProduct;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EstimationProductController extends Controller
{
    /**
     * Display a paginated listing of estimation products.
     * Supports search by product name, sorting, and filtering.
     */
    public function index(Request $request): JsonResponse
    {
        $query = EstimationProduct::with(['organization', 'company', 'product', 'customer', 'project', 'items']);

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

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        // ── Search by product name ───────────────────────────────────
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('product', function ($pq) use ($search) {
                    $pq->where('name', 'like', "%{$search}%");
                })
                ->orWhereHas('customer', function ($cq) use ($search) {
                    $cq->where('name', 'like', "%{$search}%");
                })
                ->orWhereHas('project', function ($prq) use ($search) {
                    $prq->where('name', 'like', "%{$search}%");
                });
            });
        }

        // ── Sorting ──────────────────────────────────────────────────
        $sortField = $request->input('sort_by', 'created_at');
        $sortDir   = $request->input('sort_dir', 'desc');

        $allowedSorts = ['id', 'total_amount', 'created_at'];

        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDir === 'asc' ? 'asc' : 'desc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        // ── Pagination ───────────────────────────────────────────────
        $perPage = (int) $request->input('per_page', 15);
        $estimationProducts = $query->paginate($perPage);

        return response()->json($estimationProducts);
    }

    /**
     * Store a newly created estimation product (basic – only product_id).
     * Items are added separately via EstimationProductsItemController.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'estimation_id' => 'required|integer|exists:estimations,id',
            'org_id'        => 'nullable|integer|exists:organizations,id',
            'company_id'    => 'nullable|integer|exists:companies,id',
            'product_id'    => 'required|integer|exists:products,id',
            'customer_id'   => 'required|integer|exists:customers,id',
            'project_id'    => 'required|integer|exists:projects,id',
        ]);

        DB::beginTransaction();
        try {
            $estimationProduct = EstimationProduct::create(array_merge($validated, [
                'total_amount' => 0,
            ]));

            $estimationProduct->load(['organization', 'company', 'product', 'customer', 'project', 'items']);

            DB::commit();

            return response()->json([
                'message' => 'Product added successfully. Now add items for this product.',
                'data' => $estimationProduct,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create estimation product: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified estimation product with its items.
     */
    public function show(string $id): JsonResponse
    {
        $estimationProduct = EstimationProduct::with([
            'organization', 'company', 'product', 'customer', 'project', 'items',
        ])->findOrFail($id);

        return response()->json([
            'data' => $estimationProduct,
            'summary' => [
                'total_items' => $estimationProduct->items->count(),
                'total_cft' => $estimationProduct->total_cft,
                'total_quantity' => $estimationProduct->total_quantity,
                'total_amount' => $estimationProduct->total_amount,
            ],
        ]);
    }

    /**
     * Update the specified estimation product (only product_id can change).
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $estimationProduct = EstimationProduct::findOrFail($id);

        $validated = $request->validate([
            'product_id' => 'sometimes|integer|exists:products,id',
        ]);

        DB::beginTransaction();
        try {
            $estimationProduct->fill($validated);
            $estimationProduct->save();

            // If product_id changed, update all child items
            if (isset($validated['product_id'])) {
                $estimationProduct->items()->update([
                    'product_id' => $validated['product_id'],
                ]);
            }

            $estimationProduct->load(['organization', 'company', 'product', 'customer', 'project', 'items']);

            DB::commit();

            return response()->json([
                'message' => 'Product updated successfully',
                'data' => $estimationProduct,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to update estimation product: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified estimation product from storage.
     * This cascades to delete all items.
     */
    public function destroy(string $id): JsonResponse
    {
        $estimationProduct = EstimationProduct::findOrFail($id);
        $estimationProduct->delete();

        return response()->json(['message' => 'Product and all its items deleted successfully'], 200);
    }
}
