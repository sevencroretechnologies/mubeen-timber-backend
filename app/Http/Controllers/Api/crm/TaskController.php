<?php

namespace App\Http\Controllers\Api\crm;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TaskController extends Controller
{
    public function index()
    {
        $tasks = Task::with(['taskSource', 'taskType', 'user'])->latest()->get();
        return response()->json($tasks);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'task_source_id' => 'required|exists:task_sources,id',
            'task_type_id' => 'required|exists:task_types,id',
            'related_id' => 'nullable|integer',
            'due_date' => 'nullable|date',
            'status' => 'nullable|string|max:50',
            'user_id' => 'nullable|exists:users,id',
        ]);

        $task = Task::create($validated);

        return response()->json($task, Response::HTTP_CREATED);
    }

    public function show(Task $task)
    {
        $task->load(['taskSource', 'taskType', 'user']);
        return response()->json($task);
    }

    public function update(Request $request, Task $task)
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'task_source_id' => 'exists:task_sources,id',
            'task_type_id' => 'exists:task_types,id',
            'related_id' => 'nullable|integer',
            'due_date' => 'nullable|date',
            'status' => 'nullable|string|max:50',
            'user_id' => 'nullable|exists:users,id',
        ]);

        $task->update($validated);

        return response()->json($task);
    }

    public function destroy(Task $task)
    {
        $task->delete();
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
