<?php

namespace App\Http\Controllers\Api\crm;

use App\Http\Controllers\Controller;
use App\Models\RequestType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RequestTypeController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(RequestType::all());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:request_types',
        ]);

        $requestType = RequestType::create($validated);
        return response()->json($requestType, 201);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(RequestType::findOrFail($id));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $requestType = RequestType::findOrFail($id);
        $validated = $request->validate([
            'name' => 'nullable|string|max:255|unique:request_types,name,' . $id,
        ]);
        $requestType->update($validated);
        return response()->json($requestType);
    }

    public function destroy(int $id): JsonResponse
    {
        RequestType::findOrFail($id)->delete();
        return response()->json(null, 204);
    }
}
