<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class EstimationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(\App\Models\Estimation::with('product')->get());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'estimation_type' => 'required|integer',
            'cft' => 'nullable|numeric|min:0',
            'cost_per_cft' => 'nullable|numeric|min:0',
            'labor_charges' => 'nullable|numeric|min:0',
            'total_amount' => 'nullable|numeric|min:0',
        ]);

        $estimation = \App\Models\Estimation::create($validated);

        return response()->json($estimation, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $estimation = \App\Models\Estimation::with('product')->findOrFail($id);
        return response()->json($estimation);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $estimation = \App\Models\Estimation::findOrFail($id);

        $validated = $request->validate([
            'product_id' => 'sometimes|exists:products,id',
            'estimation_type' => 'sometimes|integer',
            'cft' => 'nullable|numeric|min:0',
            'cost_per_cft' => 'nullable|numeric|min:0',
            'labor_charges' => 'nullable|numeric|min:0',
            'total_amount' => 'nullable|numeric|min:0',
        ]);

        $estimation->update($validated);

        return response()->json($estimation);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $estimation = \App\Models\Estimation::findOrFail($id);
        $estimation->delete();

        return response()->json(null, 204);
    }
}
