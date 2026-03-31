<?php

namespace App\Http\Controllers\Api\crm;

use App\Http\Controllers\Controller;
use App\Models\Source;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SourceController extends Controller
{
    public function index(): JsonResponse
    {
        $sources = Source::all();
        return response()->json($sources);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'source_code' => 'nullable|string|max:255',
        ]);

        $source = Source::create($validated);
        return response()->json($source, 201);
    }

    public function show(int $id): JsonResponse
    {
        $source = Source::findOrFail($id);
        return response()->json($source);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'source_code' => 'nullable|string|max:255',
        ]);

        $source = Source::findOrFail($id);
        $source->update($validated);
        return response()->json($source);
    }

    public function destroy(int $id): JsonResponse
    {
        $source = Source::findOrFail($id);
        $source->delete();
        return response()->json(null, 204);
    }
}
