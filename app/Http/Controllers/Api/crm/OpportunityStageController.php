<?php

namespace App\Http\Controllers\Api\crm;

use App\Http\Controllers\Controller;
use App\Models\OpportunityStage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OpportunityStageController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(OpportunityStage::all());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:opportunity_stages',
            'description' => 'nullable|string',
        ]);

        $stage = OpportunityStage::create($validated);
        return response()->json($stage, 201);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(OpportunityStage::findOrFail($id));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $stage = OpportunityStage::findOrFail($id);
        $validated = $request->validate([
            'name' => 'nullable|string|max:255|unique:opportunity_stages,name,' . $id,
            'description' => 'nullable|string',
        ]);
        $stage->update($validated);
        return response()->json($stage);
    }

    public function destroy(int $id): JsonResponse
    {
        OpportunityStage::findOrFail($id)->delete();
        return response()->json(null, 204);
    }
}
