<?php

namespace App\Http\Controllers\Api\crm;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Exception;

class ProjectController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Project::with('customer:id,name');

            if ($request->filled('customer_id')) {
                $query->where('customer_id', $request->customer_id);
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            $perPage = $request->query('per_page', 15);
            $queryParameters = Arr::except($request->query(), ['user_id']);

            $data = $query->orderBy('created_at', 'desc')
                ->paginate($perPage)
                ->appends($queryParameters);

            return response()->json([
                'message' => 'All projects retrieved successfully.',
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
                'error' => 'Failed to retrieve projects',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'customer_id' => 'nullable|exists:customers,id',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'nullable|string|in:active,completed,on_hold,cancelled',
        ]);

        $project = Project::create($validated);
        return response()->json($project, 201);
    }

    public function show(int $id): JsonResponse
    {
        $project = Project::with('products')->findOrFail($id);
        return response()->json($project);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $project = Project::findOrFail($id);
        $validated = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'nullable|string|in:active,completed,on_hold,cancelled',
        ]);
        $project->update($validated);
        return response()->json($project);
    }

    public function destroy(int $id): JsonResponse
    {
        Project::findOrFail($id)->delete();
        return response()->json(null, 204);
    }
}
