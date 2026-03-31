<?php

namespace App\Http\Controllers\Api\Recruitment;

use App\Http\Controllers\Controller;
use App\Models\JobStage;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class JobStageController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = JobStage::query();

        // Search support
        if ($request->search) {
            $query->where('title', 'like', "%{$request->search}%");
        }

        // Sorting support
        if ($request->filled('order_by')) {
            $direction = $request->input('order', 'asc');
            $query->orderBy($request->input('order_by'), $direction);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $stages = $request->boolean('paginate', true)
            ? $query->paginate($request->input('per_page', 15))
            : $query->get();

        return $this->success($stages);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|in:active,inactive',
            'is_default' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $stage = JobStage::create([
            'title' => $request->title,
            'description' => $request->description,
            'status' => $request->status ?? 'active',
            'is_default' => $request->is_default ?? false,
        ]);

        return $this->created($stage, 'Job stage created successfully');
    }

    public function show(JobStage $jobStage)
    {
        return $this->success($jobStage);
    }

    public function update(Request $request, JobStage $jobStage)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|in:active,inactive',
            'is_default' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $jobStage->update($request->all());

        return $this->success($jobStage, 'Job stage updated successfully');
    }

    public function destroy(JobStage $jobStage)
    {
        $jobStage->delete();

        return $this->noContent('Job stage deleted successfully');
    }
}
