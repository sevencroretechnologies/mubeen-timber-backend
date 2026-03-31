<?php

namespace App\Http\Controllers\Api\crm;

use App\Http\Controllers\Controller;
use App\Models\IndustryType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IndustryTypeController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(IndustryType::all());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:industry_types',
        ]);

        $industryType = IndustryType::create($validated);
        return response()->json($industryType, 201);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(IndustryType::findOrFail($id));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $industryType = IndustryType::findOrFail($id);
        $validated = $request->validate([
            'name' => 'nullable|string|max:255|unique:industry_types,name,' . $id,
        ]);
        $industryType->update($validated);
        return response()->json($industryType);
    }

    public function destroy(int $id): JsonResponse
    {
        IndustryType::findOrFail($id)->delete();
        return response()->json(null, 204);
    }
}
