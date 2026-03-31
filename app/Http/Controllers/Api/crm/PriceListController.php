<?php

namespace App\Http\Controllers\Api\crm;

use App\Http\Controllers\Controller;
use App\Models\PriceList;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PriceListController extends Controller
{
    public function index(): JsonResponse
    {
        $priceLists = PriceList::all();
        return response()->json($priceLists);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'currency' => 'required|string|size:3',
        ]);

        // Custom uniqueness check for name + currency combination
        if (PriceList::where('name', $validated['name'])->where('currency', $validated['currency'])->exists()) {
            return response()->json(['message' => 'The price list name and currency combination already exists.'], 422);
        }

        $priceList = PriceList::create($validated);
        return response()->json($priceList, 201);
    }

    public function show(PriceList $priceList): JsonResponse
    {
        return response()->json($priceList);
    }

    public function update(Request $request, PriceList $priceList): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'currency' => 'required|string|size:3',
        ]);

        // Custom uniqueness check for name + currency combination, excluding current record
        if (PriceList::where('name', $validated['name'])
            ->where('currency', $validated['currency'])
            ->where('id', '!=', $priceList->id)
            ->exists()
        ) {
            return response()->json(['message' => 'The price list name and currency combination already exists.'], 422);
        }

        $priceList->update($validated);
        return response()->json($priceList);
    }

    public function destroy(PriceList $priceList): JsonResponse
    {
        $priceList->delete();
        return response()->json(null, 204);
    }
}
