<?php

namespace App\Http\Controllers\Api\crm;

use App\Http\Controllers\Controller;
use App\Models\SalesTaskDetail;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Exception;

class SalesTaskDetailController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = SalesTaskDetail::with(['salesTask.taskType', 'salesTask.taskSource', 'salesTask.assignedUser']);

            if ($request->has('sales_task_id')) {
                $query->where('sales_task_id', $request->sales_task_id);
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where('description', 'like', "%{$search}%");
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
                'message' => 'All sales task details retrieved successfully.',
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
                'error' => 'Failed to retrieve sales task details',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'sales_task_id' => 'nullable|exists:sales_tasks,id',
            'date' => 'required|date',
            'time' => 'required',
            'description' => 'nullable|string',
            'status' => 'required|in:Open,In Progress,Closed',
        ]);

        $salesTaskDetail = SalesTaskDetail::create($validated);

        return response()->json($salesTaskDetail, Response::HTTP_CREATED);
    }

    public function show($id)
    {
        return SalesTaskDetail::findOrFail($id);
    }

    public function update(Request $request, SalesTaskDetail $salesTaskDetail)
    {
        $validated = $request->validate([
            'sales_task_id' => 'nullable|exists:sales_tasks,id',
            'date' => 'date',
            'time' => 'string', // time validation can be tricky, relying on database or basic string format
            'description' => 'string',
            'status' => 'in:Open,In Progress,Closed',
        ]);

        $salesTaskDetail->update($validated);

        return response()->json($salesTaskDetail);
    }

    public function destroy(SalesTaskDetail $salesTaskDetail)
    {
        $salesTaskDetail->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
