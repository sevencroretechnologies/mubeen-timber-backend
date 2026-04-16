<?php

namespace App\Http\Controllers\Api\crm;

use App\Http\Controllers\Controller;
use App\Models\CustomerContactDetail;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CustomerContactDetailController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = CustomerContactDetail::query();
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
            'phone_no' => 'nullable|string',
            'whatsapp_no' => 'nullable|string',
            'personal_email' => 'nullable|email',
            'company_email' => 'nullable|email',
        ]);

        $detail = CustomerContactDetail::create($validated);
        return response()->json($detail, 201);
    }

    public function update(Request $request, CustomerContactDetail $customerContactDetail): JsonResponse
    {
        $validated = $request->validate([
            'phone_no' => 'nullable|string',
            'whatsapp_no' => 'nullable|string',
            'personal_email' => 'nullable|email',
            'company_email' => 'nullable|email',
        ]);

        $customerContactDetail->update($validated);
        return response()->json($customerContactDetail);
    }

    public function destroy(CustomerContactDetail $customerContactDetail): JsonResponse
    {
        $customerContactDetail->delete();
        return response()->json(null, 204);
    }
}
