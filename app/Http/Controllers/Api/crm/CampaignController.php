<?php

namespace App\Http\Controllers\Api\crm;

use App\Http\Controllers\Controller;
use App\Services\crm\CampaignService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;

class CampaignController extends Controller
{
    public function __construct(private CampaignService $campaignService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $data = $this->campaignService->list($request->all());

            return response()->json([
                'message' => 'All campaigns retrieved successfully.',
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
                'error' => 'Failed to retrieve campaigns',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'campaign_code' => 'nullable|string|max:255',
        ]);

        $campaign = $this->campaignService->create($validated);
        return response()->json($campaign, 201);
    }

    public function show(int $id): JsonResponse
    {
        $campaign = $this->campaignService->find($id);
        return response()->json($campaign);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'campaign_code' => 'nullable|string|max:255',
        ]);

        $campaign = $this->campaignService->update($id, $validated);
        return response()->json($campaign);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->campaignService->delete($id);
        return response()->json(null, 204);
    }
}
