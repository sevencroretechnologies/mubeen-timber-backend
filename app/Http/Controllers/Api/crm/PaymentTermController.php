<?php

namespace App\Http\Controllers\Api\crm;

use App\Http\Controllers\Controller;
use App\Models\PaymentTerm;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentTermController extends Controller
{
    public function index(): JsonResponse
    {
        $terms = PaymentTerm::all();
        return response()->json($terms);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:payment_terms,name|max:255',
            'days' => 'nullable|integer',
        ]);

        $term = PaymentTerm::create($validated);
        return response()->json($term, 201);
    }

    public function show(PaymentTerm $paymentTerm): JsonResponse
    {
        return response()->json($paymentTerm);
    }

    public function update(Request $request, PaymentTerm $paymentTerm): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:payment_terms,name,' . $paymentTerm->id . '|max:255',
            'days' => 'nullable|integer',
        ]);

        $paymentTerm->update($validated);
        return response()->json($paymentTerm);
    }

    public function destroy(PaymentTerm $paymentTerm): JsonResponse
    {
        $paymentTerm->delete();
        return response()->json(null, 204);
    }
}
