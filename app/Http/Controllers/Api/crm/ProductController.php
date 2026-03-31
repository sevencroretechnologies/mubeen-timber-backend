<?php

namespace App\Http\Controllers\Api\crm;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Exception;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Product::with('category');

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            }

            if ($request->filled('category_id')) {
                $query->where('category_id', $request->category_id);
            }

            $perPage = $request->query('per_page', 15);
            $queryParameters = Arr::except($request->query(), ['user_id']);

            $data = $query->orderBy('created_at', 'desc')
                ->paginate($perPage)
                ->appends($queryParameters);

            return response()->json([
                'message' => 'All products retrieved successfully.',
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
                'error' => 'Failed to retrieve products',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => 'nullable|integer|exists:product_categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'long_description' => 'nullable|string',
            'slug' => 'nullable|string|max:255|unique:products',
            'stock' => 'nullable|integer|min:0',
            'rate' => 'nullable|numeric|min:0',
            'amount' => 'nullable|numeric|min:0',
        ]);

        $product = Product::create($validated);
        $product->load('category');
        return response()->json($product, 201);
    }

    public function show(int $id): JsonResponse
    {
        $product = Product::with('category')->findOrFail($id);
        return response()->json($product);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $product = Product::findOrFail($id);
        $validated = $request->validate([
            'category_id' => 'nullable|integer|exists:product_categories,id',
            'name' => 'nullable|string|max:255',
            'code' => 'nullable|string|max:255|unique:products,code,' . $id,
            'description' => 'nullable|string',
            'long_description' => 'nullable|string',
            'slug' => 'nullable|string|max:255|unique:products,slug,' . $id,
            'stock' => 'nullable|integer|min:0',
            'rate' => 'nullable|numeric|min:0',
            'amount' => 'nullable|numeric|min:0',
        ]);
        $product->update($validated);
        $product->load('category');
        return response()->json($product);
    }

    public function destroy(int $id): JsonResponse
    {
        Product::findOrFail($id)->delete();
        return response()->json(null, 204);
    }
}
