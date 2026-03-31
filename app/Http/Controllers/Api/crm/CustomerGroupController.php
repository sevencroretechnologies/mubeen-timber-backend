<?php

namespace App\Http\Controllers\Api\crm;

use App\Http\Controllers\Controller;
use App\Models\CustomerGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerGroupController extends Controller
{
    public function index(): JsonResponse
    {
        $groups = CustomerGroup::all();
        return response()->json($groups);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:customer_groups,name|max:255',
        ]);

        $group = CustomerGroup::create($validated);
        return response()->json($group, 201);
    }

    public function show(CustomerGroup $customerGroup): JsonResponse
    {
        return response()->json($customerGroup);
    }

    public function update(Request $request, CustomerGroup $customerGroup): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:customer_groups,name,' . $customerGroup->id . '|max:255',
        ]);

        $customerGroup->update($validated);
        return response()->json($customerGroup);
    }

    public function destroy(CustomerGroup $customerGroup): JsonResponse
    {
        $customerGroup->delete();
        return response()->json(null, 204);
    }
}
