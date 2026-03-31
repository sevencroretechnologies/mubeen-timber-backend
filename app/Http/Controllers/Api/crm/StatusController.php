<?php

namespace App\Http\Controllers\Api\crm;

use App\Http\Controllers\Controller;
use App\Models\Status;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StatusController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Status::all());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status_name' => 'required|string|max:255|unique:statuses',
        ]);

        $status = Status::create($validated);
        return response()->json($status, 201);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(Status::findOrFail($id));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $status = Status::findOrFail($id);
        $validated = $request->validate([
            'status_name' => 'nullable|string|max:255|unique:statuses,status_name,' . $id,
        ]);
        $status->update($validated);
        return response()->json($status);
    }

    public function destroy(int $id): JsonResponse
    {
        Status::findOrFail($id)->delete();
        return response()->json(null, 204);
    }
}
