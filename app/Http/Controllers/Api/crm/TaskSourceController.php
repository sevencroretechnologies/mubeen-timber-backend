<?php

namespace App\Http\Controllers\Api\crm;

use App\Http\Controllers\Controller;
use App\Models\TaskSource;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TaskSourceController extends Controller
{
    public function index()
    {
        return response()->json(TaskSource::all());
    }

    public function store(Request $request)
    {
        $validated = $request->validate(['name' => 'required|string|unique:task_sources,name']);
        $source = TaskSource::create($validated);
        return response()->json($source, Response::HTTP_CREATED);
    }

    public function destroy(TaskSource $taskSource)
    {
        $taskSource->delete();
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
