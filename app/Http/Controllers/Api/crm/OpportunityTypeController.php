<?php

namespace App\Http\Controllers\Api\crm;

use App\Http\Controllers\Controller;
use App\Models\OpportunityType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OpportunityTypeController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(OpportunityType::all());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:opportunity_types',
            'description' => 'nullable|string',
        ]);

        $type = OpportunityType::create($validated);
        return response()->json($type, 201);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(OpportunityType::findOrFail($id));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $type = OpportunityType::findOrFail($id);
        $validated = $request->validate([
            'name' => 'nullable|string|max:255|unique:opportunity_types,name,' . $id,
            'description' => 'nullable|string',
        ]);
        $type->update($validated);
        return response()->json($type);
    }

    public function destroy(int $id): JsonResponse
    {
        OpportunityType::findOrFail($id)->delete();
        return response()->json(null, 204);
    }
}
