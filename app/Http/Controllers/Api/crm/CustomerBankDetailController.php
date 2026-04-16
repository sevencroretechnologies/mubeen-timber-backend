<?php

namespace App\Http\Controllers\Api\crm;

use App\Http\Controllers\Controller;
use App\Models\CustomerBankDetail;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CustomerBankDetailController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = CustomerBankDetail::query();
        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }
        return response()->json($query->get());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'org_id' => 'nullable|exists:organizations,id',
            'company_id' => 'nullable|exists:companies,id',
            'bank_name' => 'nullable|string',
            'branch_name' => 'nullable|string',
            'account_no' => 'nullable|string',
            'ifsc_code' => 'nullable|string',
            'bank_address' => 'nullable|string',
        ]);

        $detail = CustomerBankDetail::create($validated);
        return response()->json($detail, 201);
    }

    public function update(Request $request, CustomerBankDetail $customerBankDetail): JsonResponse
    {
        $validated = $request->validate([
            'bank_name' => 'nullable|string',
            'branch_name' => 'nullable|string',
            'account_no' => 'nullable|string',
            'ifsc_code' => 'nullable|string',
            'bank_address' => 'nullable|string',
        ]);

        $customerBankDetail->update($validated);
        return response()->json($customerBankDetail);
    }

    public function destroy(CustomerBankDetail $customerBankDetail): JsonResponse
    {
        $customerBankDetail->delete();
        return response()->json(null, 204);
    }
}
