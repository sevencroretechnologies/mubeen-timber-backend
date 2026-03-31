<?php

namespace App\Http\Controllers\Api\crm;

use App\Http\Controllers\Controller;
use App\Services\crm\ProspectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;

class ProspectController extends Controller
{
    public function __construct(private ProspectService $prospectService) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $data = $this->prospectService->list($request->all());

            return response()->json([
                'message' => 'All prospects retrieved successfully.',
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
                'error' => 'Failed to retrieve prospects',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_name' => 'nullable|string|max:255|unique:prospects',
            'status' => 'nullable|string|max:50',
            'source' => 'nullable|string|max:255',
            'industry' => 'nullable|string|max:255',
            'market_segment' => 'nullable|string|max:255',
            'customer_group' => 'nullable|string|max:255',
            'territory' => 'nullable|string|max:255',
            'no_of_employees' => 'nullable|string|max:50',
            'annual_revenue' => 'nullable|numeric|min:0',
            'fax' => 'nullable|string|max:50',
            'website' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'zip_code' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'prospect_owner_id' => 'nullable|integer|exists:users,id',
            'company' => 'nullable|string|max:255',
        ]);

        $prospect = $this->prospectService->create($validated);
        return response()->json($prospect, 201);
    }

    public function show(int $id): JsonResponse
    {
        $prospect = $this->prospectService->find($id);
        return response()->json($prospect);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'company_name' => 'nullable|string|max:255|unique:prospects,company_name,' . $id,
            'status' => 'nullable|string|max:50',
// ...
            'source' => 'nullable|string|max:255',
            'industry' => 'nullable|string|max:255',
            'market_segment' => 'nullable|string|max:255',
            'customer_group' => 'nullable|string|max:255',
            'territory' => 'nullable|string|max:255',
            'no_of_employees' => 'nullable|string|max:50',
            'annual_revenue' => 'nullable|numeric|min:0',
            'fax' => 'nullable|string|max:50',
            'website' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'zip_code' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'prospect_owner_id' => 'nullable|integer|exists:users,id',
            'company' => 'nullable|string|max:255',
        ]);

        $prospect = $this->prospectService->update($id, $validated);
        return response()->json($prospect);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->prospectService->delete($id);
        return response()->json(null, 204);
    }
}
