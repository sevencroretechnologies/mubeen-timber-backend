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
     * Supports both single and bulk estimation creation with optional customer/project creation.
     */
    public function store(Request $request)
    {
        // Handle bulk estimations
        if ($request->has('estimations') && is_array($request->estimations)) {
            return $this->storeBulk($request);
        }

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
     * Store bulk estimations with optional customer/project/product creation.
     */
    private function storeBulk(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'nullable|integer|exists:customers,id',
            'customer_name' => 'nullable|string|required_if:customer_id,null|max:255',
            'project_id' => 'nullable|integer|exists:projects,id',
            'project_name' => 'nullable|string|required_if:project_id,null|max:255',
            'estimations' => 'required|array|min:1',
            'estimations.*.product_id' => 'nullable|integer|exists:products,id',
            'estimations.*.product_name' => 'nullable|string|required_if:estimations.*.product_id,null|max:255',
            'estimations.*.estimation_type' => 'required|integer',
            'estimations.*.length' => 'nullable|numeric|min:0',
            'estimations.*.breadth' => 'nullable|numeric|min:0',
            'estimations.*.height' => 'nullable|numeric|min:0',
            'estimations.*.thickness' => 'nullable|numeric|min:0',
            'estimations.*.quantity' => 'nullable|integer|min:0',
            'estimations.*.cost_per_cft' => 'nullable|numeric|min:0',
            'estimations.*.labor_charges' => 'nullable|numeric|min:0',
        ]);

        // Create or use existing customer
        $customerId = $validated['customer_id'];
        if (empty($customerId)) {
            $customer = \App\Models\Customer::create([
                'name' => $validated['customer_name'],
            ]);
            $customerId = $customer->id;
        }

        // Create or use existing project
        $projectId = $validated['project_id'];
        if (empty($projectId) && !empty($validated['project_name'])) {
            $project = \App\Models\Project::create([
                'name' => $validated['project_name'],
                'description' => null,
            ]);
            $projectId = $project->id;
        }

        // Create products and estimations
        $createdEstimations = [];
        $totalAmount = 0;

        foreach ($validated['estimations'] as $estimationData) {
            // Create or use existing product
            $productId = $estimationData['product_id'];
            if (empty($productId)) {
                $product = \App\Models\Product::create([
                    'customer_id' => $customerId,
                    'project_id' => $projectId,
                    'name' => $estimationData['product_name'],
                    'description' => null,
                ]);
                $productId = $product->id;
            }

            // Calculate CFT and total
            $calculations = $this->calculateCftAndTotal($estimationData);

            // Create estimation
            $estimation = \App\Models\Estimation::create([
                'customer_id' => $customerId,
                'project_id' => $projectId,
                'product_id' => $productId,
                'estimation_type' => $estimationData['estimation_type'],
                'length' => $estimationData['length'] ?? null,
                'breadth' => $estimationData['breadth'] ?? null,
                'height' => $estimationData['height'] ?? null,
                'thickness' => $estimationData['thickness'] ?? null,
                'quantity' => $estimationData['quantity'] ?? null,
                'cft' => $calculations['cft'],
                'cost_per_cft' => $estimationData['cost_per_cft'] ?? null,
                'labor_charges' => $estimationData['labor_charges'] ?? null,
                'total_amount' => $calculations['total_amount'],
            ]);

            $estimation->load(['product', 'customer', 'project']);
            $createdEstimations[] = $estimation;
            $totalAmount += $calculations['total_amount'];
        }

        return response()->json([
            'message' => 'Estimations created successfully',
            'data' => $createdEstimations,
            'total_amount' => $totalAmount,
            'customer_id' => $customerId,
            'project_id' => $projectId,
        ], 201);
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
