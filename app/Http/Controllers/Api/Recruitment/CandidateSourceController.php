<?php

namespace App\Http\Controllers\Api\Recruitment;

use App\Http\Controllers\Controller;
use App\Models\CandidateSource;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CandidateSourceController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = CandidateSource::query();

        if ($request->search) {
            $query->where('title', 'like', "%{$request->search}%");
        }

        // Sorting
        if ($request->has('order_by')) {
            $query->orderBy($request->order_by, $request->order ?? 'asc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $sources = $request->input('paginate') === 'false'
            ? $query->get()
            : $query->paginate($request->per_page ?? 15);

        return $this->success($sources);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $source = CandidateSource::create($request->all());

        return $this->created($source, 'Candidate Source created successfully');
    }

    public function show(CandidateSource $candidateSource)
    {
        return $this->success($candidateSource);
    }

    public function update(Request $request, CandidateSource $candidateSource)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $candidateSource->update($request->all());

        return $this->success($candidateSource, 'Candidate Source updated successfully');
    }

    public function destroy(CandidateSource $candidateSource)
    {
        $candidateSource->delete();
        return $this->success(null, 'Candidate Source deleted successfully');
    }
}
