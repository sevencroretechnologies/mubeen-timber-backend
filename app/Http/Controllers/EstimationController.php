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
        return response()->json(\App\Models\Estimation::with(['product', 'customer', 'project'])->get());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'project_id' => 'nullable|exists:projects,id',
            'product_id' => 'required|exists:products,id',
            'estimation_type' => 'required|integer',
            'length' => 'nullable|numeric|min:0',
            'breadth' => 'nullable|numeric|min:0',
            'height' => 'nullable|numeric|min:0',
            'thickness' => 'nullable|numeric|min:0',
            'quantity' => 'nullable|integer|min:0',
            'cft' => 'nullable|numeric|min:0',
            'cost_per_cft' => 'nullable|numeric|min:0',
            'labor_charges' => 'nullable|numeric|min:0',
            'total_amount' => 'nullable|numeric|min:0',
        ]);

        $calculations = $this->calculateCftAndTotal($validated);
        $validated['cft'] = $calculations['cft'];
        $validated['total_amount'] = $calculations['total_amount'];

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
            'customer_id' => 'sometimes|exists:customers,id',
            'project_id' => 'nullable|exists:projects,id',
            'product_id' => 'sometimes|exists:products,id',
            'estimation_type' => 'sometimes|integer',
            'length' => 'nullable|numeric|min:0',
            'breadth' => 'nullable|numeric|min:0',
            'height' => 'nullable|numeric|min:0',
            'thickness' => 'nullable|numeric|min:0',
            'quantity' => 'nullable|integer|min:0',
            'cft' => 'nullable|numeric|min:0',
            'cost_per_cft' => 'nullable|numeric|min:0',
            'labor_charges' => 'nullable|numeric|min:0',
            'total_amount' => 'nullable|numeric|min:0',
        ]);

        $fullData = array_merge($estimation->toArray(), $validated);
        $calculations = $this->calculateCftAndTotal($fullData);
        
        $validated['cft'] = $calculations['cft'];
        $validated['total_amount'] = $calculations['total_amount'];

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

    /**
     * Helper to perform consistent calculation for CFT and Totals.
     */
    private function calculateCftAndTotal(array $data)
    {
        $l = !empty($data['length']) ? floatval($data['length']) : 1;
        $b = !empty($data['breadth']) ? floatval($data['breadth']) : 1;
        $h = !empty($data['height']) ? floatval($data['height']) : 1;
        $t = !empty($data['thickness']) ? floatval($data['thickness']) : 1;
        $q = !empty($data['quantity']) ? floatval($data['quantity']) : 1;
        
        $type = !empty($data['estimation_type']) ? intval($data['estimation_type']) : 1;
        $cftPerUnit = 0;

        if ($type === 1) {
            $cftPerUnit = ($l * $b * $h) / 144;
        } elseif ($type === 2) {
            $cftPerUnit = $l * $b * $h;
        } elseif ($type === 3) {
            $cftPerUnit = ($l * $b * $t) / 12;
        } elseif ($type === 4) {
            $cftPerUnit = $l * $b * $t;
        } else {
            $cftPerUnit = ($l * $b * $h) / 144;
        }

        $calcCft = $cftPerUnit * $q;

        if (!empty($data['length']) || !empty($data['breadth']) || !empty($data['height']) || !empty($data['thickness']) || !empty($data['quantity'])) {
            $finalCft = $calcCft;
        } else {
            $finalCft = !empty($data['cft']) ? floatval($data['cft']) : 0;
        }

        $cost = !empty($data['cost_per_cft']) ? floatval($data['cost_per_cft']) : 0;
        $labor = !empty($data['labor_charges']) ? floatval($data['labor_charges']) : 0;
        
        $total = ($finalCft * $cost) + $labor;

        return [
            'cft' => round($finalCft, 2),
            'total_amount' => round($total, 2)
        ];
    }
}
