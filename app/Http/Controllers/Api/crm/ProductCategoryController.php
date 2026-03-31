<?php

namespace App\Http\Controllers\Api\crm;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Exception;

class ProductCategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $query = ProductCategory::query();

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            }

            $perPage = $request->query('per_page', 15);
            $queryParameters = Arr::except($request->query(), ['user_id']);

            $data = $query->orderBy('created_at', 'desc')
                ->paginate($perPage)
                ->appends($queryParameters);

            return response()->json([
                'message' => 'All product categories retrieved successfully.',
                'data' => $data,
                'pagination' => [
                    'current_page' => $data->currentPage(),
                    'total_pages' => $data->lastPage(),
                    'per_page' => $data->perPage(),
                    'total_items' => $data->total(),
                    'next_page_url' => $data->nextPageUrl(),
                    'prev_page_url' => $data->previousPageUrl(),
                ],
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Failed to retrieve product categories',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:product_categories',
            'description' => 'nullable|string',
        ]);

        $productCategory = ProductCategory::create($validated);
        return response()->json($productCategory, 201);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(ProductCategory::findOrFail($id));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $productCategory = ProductCategory::findOrFail($id);
        $validated = $request->validate([
            'name' => 'nullable|string|max:255|unique:product_categories,name,' . $id,
            'description' => 'nullable|string',
        ]);
        $productCategory->update($validated);
        return response()->json($productCategory);
    }

    public function destroy(int $id): JsonResponse
    {
        ProductCategory::findOrFail($id)->delete();
        return response()->json(null, 204);
    }
}
