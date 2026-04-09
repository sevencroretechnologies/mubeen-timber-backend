<?php

namespace App\Http\Controllers;

use App\Models\EstimationProductsItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EstimationProductsItemController extends Controller
{
    /**
     * Display a paginated listing of estimation product items.
     * Supports filtering by org, company, estimation, customer, project, product.
     */
    public function index(Request $request): JsonResponse
    {
        $query = EstimationProductsItem::with([
            'organization', 'company', 'estimation', 'product', 'customer', 'project',
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

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        // ── Search by name or related entities ───────────────────────
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhereHas('product', function ($pq) use ($search) {
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

        $allowedSorts = [
            'id', 'name', 'length', 'breadth', 'height',
            'thickness', 'quantity', 'item_cft', 'created_at',
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
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'          => 'nullable|string|max:255',
            'org_id'        => 'nullable|integer|exists:organizations,id',
            'company_id'    => 'nullable|integer|exists:companies,id',
            'estimation_id' => 'nullable|integer|exists:estimations,id',
            'product_id'    => 'nullable|integer|exists:products,id',
            'customer_id'   => 'nullable|integer|exists:customers,id',
            'project_id'    => 'nullable|integer|exists:projects,id',
            'length'        => 'nullable|numeric|min:0',
            'breadth'       => 'nullable|numeric|min:0',
            'height'        => 'nullable|numeric|min:0',
            'thickness'     => 'nullable|numeric|min:0',
            'quantity'      => 'required|integer|min:1',
            'item_cft'      => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $item = EstimationProductsItem::create($validated);

            $item->load(['organization', 'company', 'estimation', 'product', 'customer', 'project']);

            DB::commit();

            return response()->json($item, 201);
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
            'organization', 'company', 'estimation', 'product', 'customer', 'project',
        ])->findOrFail($id);

        return response()->json($item);
    }

    /**
     * Update the specified estimation product item.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $item = EstimationProductsItem::findOrFail($id);

        $validated = $request->validate([
            'name'          => 'sometimes|nullable|string|max:255',
            'org_id'        => 'sometimes|nullable|integer|exists:organizations,id',
            'company_id'    => 'sometimes|nullable|integer|exists:companies,id',
            'estimation_id' => 'sometimes|nullable|integer|exists:estimations,id',
            'product_id'    => 'sometimes|nullable|integer|exists:products,id',
            'customer_id'   => 'sometimes|nullable|integer|exists:customers,id',
            'project_id'    => 'sometimes|nullable|integer|exists:projects,id',
            'length'        => 'nullable|numeric|min:0',
            'breadth'       => 'nullable|numeric|min:0',
            'height'        => 'nullable|numeric|min:0',
            'thickness'     => 'nullable|numeric|min:0',
            'quantity'      => 'sometimes|integer|min:1',
            'item_cft'      => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $item->fill($validated);
            $item->save();

            $item->load(['organization', 'company', 'estimation', 'product', 'customer', 'project']);

            DB::commit();

            return response()->json($item);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to update estimation product item: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified estimation product item from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $item = EstimationProductsItem::findOrFail($id);
        $item->delete();

        return response()->json(null, 204);
    }
}
