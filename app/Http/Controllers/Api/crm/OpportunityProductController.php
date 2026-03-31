<?php

namespace App\Http\Controllers\Api\crm;

use App\Http\Controllers\Controller;
use App\Models\OpportunityProduct;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OpportunityProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = OpportunityProduct::with(['opportunity', 'product']);

        if ($request->filled('opportunity_id')) {
            $query->where('opportunity_id', $request->opportunity_id);
        }

        $items = $query->get();
        return response()->json($items);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'opportunity_id' => 'required|exists:opportunities,id',
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $item = OpportunityProduct::create($validated);
        return response()->json($item->load(['opportunity', 'product']), 201);
    }

    public function show(int $id): JsonResponse
    {
        $item = OpportunityProduct::with(['opportunity', 'product'])->findOrFail($id);
        return response()->json($item);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $item = OpportunityProduct::findOrFail($id);

        $validated = $request->validate([
            'opportunity_id' => 'sometimes|required|exists:opportunities,id',
            'product_id' => 'sometimes|required|exists:products,id',
            'quantity' => 'sometimes|required|integer|min:1',
        ]);

        $item->update($validated);
        return response()->json($item->load(['opportunity', 'product']));
    }

    public function destroy(int $id): JsonResponse
    {
        OpportunityProduct::findOrFail($id)->delete();
        return response()->json(null, 204);
    }
}
