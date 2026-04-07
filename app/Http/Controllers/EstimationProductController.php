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
        $query = EstimationProduct::with(['organization', 'company', 'product', 'customer', 'project']);

        // ── Filters ──────────────────────────────────────────────────

        if ($request->filled('org_id')) {
            $query->where('org_id', $request->org_id);
        }

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
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

        // Whitelist allowed sort columns
        $allowedSorts = [
            'id', 'length', 'breadth', 'height', 'thickness',
            'quantity', 'cft', 'cost_per_cft', 'total_amount', 'created_at',
        ];

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
     * Store a newly created estimation product.
     * Automatically calculates CFT and total_amount based on the calculation type.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'org_id'               => 'required|integer|exists:organizations,id',
            'company_id'           => 'required|integer|exists:companies,id',
            'product_id'           => 'required|integer|exists:products,id',
            'customer_id'          => 'required|integer|exists:customers,id',
            'project_id'           => 'required|integer|exists:projects,id',
            'length'               => 'nullable|numeric|min:0',
            'breadth'              => 'nullable|numeric|min:0',
            'height'               => 'nullable|numeric|min:0',
            'thickness'            => 'nullable|numeric|min:0',
            'cft_calculation_type' => 'required|string|in:1,2,3,4,5',
            'quantity'             => 'required|integer|min:1',
            'cft'                  => 'nullable|numeric|min:0',
            'cost_per_cft'         => 'nullable|numeric|min:0',
            'total_amount'         => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $estimationProduct = new EstimationProduct($validated);

            // Auto-calculate CFT for types 1-4, only type 5 uses manual CFT
            if ($validated['cft_calculation_type'] !== '5') {
                $estimationProduct->cft = round($estimationProduct->calculateCft(), 2);
            }

            // Auto-calculate total_amount: cft * cost_per_cft * quantity
            $estimationProduct->total_amount = round($estimationProduct->calculateTotalAmount(), 2);

            $estimationProduct->save();

            // Load relationships for response
            $estimationProduct->load(['organization', 'company', 'product', 'customer', 'project']);

            DB::commit();

            return response()->json($estimationProduct, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create estimation product: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified estimation product.
     */
    public function show(string $id): JsonResponse
    {
        $estimationProduct = EstimationProduct::with(['organization', 'company', 'product', 'customer', 'project'])
            ->findOrFail($id);

        return response()->json($estimationProduct);
    }

    /**
     * Update the specified estimation product.
     * Recalculates CFT and total_amount on update.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $estimationProduct = EstimationProduct::findOrFail($id);

        $validated = $request->validate([
            'org_id'               => 'sometimes|integer|exists:organizations,id',
            'company_id'           => 'sometimes|integer|exists:companies,id',
            'product_id'           => 'sometimes|integer|exists:products,id',
            'customer_id'          => 'sometimes|integer|exists:customers,id',
            'project_id'           => 'sometimes|integer|exists:projects,id',
            'length'               => 'nullable|numeric|min:0',
            'breadth'              => 'nullable|numeric|min:0',
            'height'               => 'nullable|numeric|min:0',
            'thickness'            => 'nullable|numeric|min:0',
            'cft_calculation_type' => 'sometimes|string|in:1,2,3,4,5',
            'quantity'             => 'sometimes|integer|min:1',
            'cft'                  => 'nullable|numeric|min:0',
            'cost_per_cft'         => 'nullable|numeric|min:0',
            'total_amount'         => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $estimationProduct->fill($validated);

            // Determine the calculation type (use updated or existing)
            $calcType = $estimationProduct->cft_calculation_type;

            // Auto-calculate CFT for types 1-4, only type 5 uses manual CFT
            if ($calcType !== '5') {
                $estimationProduct->cft = round($estimationProduct->calculateCft(), 2);
            }

            // Auto-calculate total_amount: cft * cost_per_cft * quantity
            $estimationProduct->total_amount = round($estimationProduct->calculateTotalAmount(), 2);

            $estimationProduct->save();

            // Load relationships for response
            $estimationProduct->load(['organization', 'company', 'product', 'customer', 'project']);

            DB::commit();

            return response()->json($estimationProduct);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to update estimation product: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified estimation product from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $estimationProduct = EstimationProduct::findOrFail($id);
        $estimationProduct->delete();

        return response()->json(null, 204);
    }
}
