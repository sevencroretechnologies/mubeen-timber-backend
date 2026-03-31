<?php

namespace App\Http\Controllers\Api\crm;

use App\Http\Controllers\Controller;
use App\Models\TaskType;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TaskTypeController extends Controller
{
    public function index()
    {
        return response()->json(TaskType::all());
    }

    public function store(Request $request)
    {
        $validated = $request->validate(['name' => 'required|string|unique:task_types,name']);
        $type = TaskType::create($validated);
        return response()->json($type, Response::HTTP_CREATED);
    }

    public function destroy(TaskType $taskType)
    {
        $taskType->delete();
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
