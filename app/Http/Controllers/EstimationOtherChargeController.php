<?php

namespace App\Http\Controllers;

use App\Models\EstimationOtherCharge;
use App\Models\Estimation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EstimationOtherChargeController extends Controller
{
    /**
     * Display a listing of other charges.
     */
    public function index(Request $request)
    {
        $query = EstimationOtherCharge::query();

        if ($request->has('org_id')) {
            $query->where('org_id', $request->org_id);
        }

        if ($request->has('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->has('estimation_id')) {
            $query->where('estimation_id', $request->estimation_id);
        }

        return response()->json($query->get());
    }

    /**
     * Store or update other charges for an estimation.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'estimation_id' => 'required|exists:estimations,id',
            'org_id' => 'nullable|integer',
            'company_id' => 'nullable|integer',
            'labour_charges' => 'nullable|numeric|min:0',
            'transport_and_handling' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'approximate_tax' => 'nullable|numeric|min:0',
            'overall_total_cft' => 'nullable|numeric|min:0',
            'other_description_amount' => 'nullable|numeric|min:0',
            'other_description' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Since it's a 1:1 relationship usually, we can use updateOrCreate
            $otherCharge = EstimationOtherCharge::updateOrCreate(
                ['estimation_id' => $validated['estimation_id']],
                $validated
            );

            DB::commit();
            return response()->json($otherCharge, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified other charges.
     */
    public function show(string $id)
    {
        $otherCharge = EstimationOtherCharge::findOrFail($id);
        return response()->json($otherCharge);
    }

    /**
     * Get other charges by estimation ID.
     */
    public function getByEstimation(string $estimationId)
    {
        $otherCharge = EstimationOtherCharge::where('estimation_id', $estimationId)->firstOrFail();
        return response()->json($otherCharge);
    }

    /**
     * Update the specified other charges.
     */
    public function update(Request $request, string $id)
    {
        $otherCharge = EstimationOtherCharge::findOrFail($id);

        $validated = $request->validate([
            'labour_charges' => 'nullable|numeric|min:0',
            'transport_and_handling' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'approximate_tax' => 'nullable|numeric|min:0',
            'overall_total_cft' => 'nullable|numeric|min:0',
            'other_description_amount' => 'nullable|numeric|min:0',
            'other_description' => 'nullable|string',
        ]);

        $otherCharge->update($validated);

        return response()->json($otherCharge);
    }

    /**
     * Remove the specified other charges.
     */
    public function destroy(string $id)
    {
        $otherCharge = EstimationOtherCharge::findOrFail($id);
        $otherCharge->delete();

        return response()->json(null, 204);
    }
}
