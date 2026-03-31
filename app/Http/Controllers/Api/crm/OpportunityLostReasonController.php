<?php

namespace App\Http\Controllers\Api\crm;

use App\Http\Controllers\Controller;
use App\Models\OpportunityLostReason;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Exception;

class OpportunityLostReasonController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $query = OpportunityLostReason::with('opportunity');

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where('opportunity_lost_reasons', 'like', "%{$search}%");
            }

            if ($request->filled('opportunity_id')) {
                $query->where('opportunity_id', $request->opportunity_id);
            }

            $perPage = $request->query('per_page', 15);
            $queryParameters = Arr::except($request->query(), ['user_id']);

            $data = $query->orderBy('created_at', 'desc')
                ->paginate($perPage)
                ->appends($queryParameters);

            return response()->json([
                'message' => 'All opportunity lost reasons retrieved successfully.',
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
                'error' => 'Failed to retrieve opportunity lost reasons',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'opportunity_id' => 'required|exists:opportunities,id',
            'opportunity_lost_reasons' => 'required|string|max:255',
        ]);

        $reason = OpportunityLostReason::create($validated);
        return response()->json($reason->load('opportunity'), 201);
    }

    public function show(int $id): JsonResponse
    {
        $reason = OpportunityLostReason::with('opportunity')->findOrFail($id);
        return response()->json($reason);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $reason = OpportunityLostReason::findOrFail($id);

        $validated = $request->validate([
            'opportunity_id' => 'sometimes|required|exists:opportunities,id',
            'opportunity_lost_reasons' => 'sometimes|required|string|max:255',
        ]);

        $reason->update($validated);
        return response()->json($reason->load('opportunity'));
    }

    public function destroy(int $id): JsonResponse
    {
        OpportunityLostReason::findOrFail($id)->delete();
        return response()->json(null, 204);
    }
}
