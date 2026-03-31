<?php

namespace App\Http\Controllers\Api\crm;

use App\Http\Controllers\Controller;
use App\Models\Territory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TerritoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Territory::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('territory_name', 'like', "%{$search}%")
                  ->orWhereHas('manager', function ($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%");
                  });
            });
        }

        return response()->json(
            $query->orderBy('territory_name')->get()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'territory_name' => 'required|string|max:255|unique:territories',
            'territory_manager' => 'nullable|integer|exists:users,id',
        ]);

        $territory = Territory::create($validated);
        return response()->json($territory->fresh(), 201);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(Territory::findOrFail($id));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $territory = Territory::findOrFail($id);
        $validated = $request->validate([
            'territory_name' => 'nullable|string|max:255|unique:territories,territory_name,' . $id,
            'territory_manager' => 'nullable|integer|exists:users,id',
        ]);

        $territory->update($validated);
        return response()->json($territory->fresh());
    }

    public function destroy(int $id): JsonResponse
    {
        Territory::findOrFail($id)->delete();
        return response()->json(null, 204);
    }
}
