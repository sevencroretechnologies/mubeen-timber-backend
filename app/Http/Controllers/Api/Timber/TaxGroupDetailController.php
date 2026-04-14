<?php

namespace App\Http\Controllers\Api\Timber;

use App\Http\Controllers\Controller;
use App\Models\Timber\TaxGroup;
use App\Models\Timber\TaxGroupDetail;
use App\Models\Timber\TaxRate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TaxGroupDetailController extends Controller
{
    /**
     * Get all tax group details for a specific tax group.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'tax_group_id' => 'nullable|exists:tax_groups,id',
        ]);

        $query = TaxGroupDetail::with(['taxRate:id,name,rate,tax_type', 'taxGroup:id,name,code']);

        // Filter by tax_group_id if provided
        if ($request->has('tax_group_id')) {
            $query->where('tax_group_id', $request->tax_group_id);
        }

        // Filter by tax_rate_id if provided
        if ($request->has('tax_rate_id')) {
            $query->where('tax_rate_id', $request->tax_rate_id);
        }

        $details = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => $details->items(),
            'pagination' => [
                'current_page' => $details->currentPage(),
                'total_pages' => $details->lastPage(),
                'per_page' => $details->perPage(),
                'total_items' => $details->total(),
            ],
        ]);
    }

    /**
     * Get a single tax group detail by ID.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $detail = TaxGroupDetail::with(['taxRate', 'taxGroup'])
            ->find($id);

        if (!$detail) {
            return response()->json([
                'success' => false,
                'message' => 'Tax group detail not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $detail,
        ]);
    }

    /**
     * Add a tax rate to a tax group.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'tax_group_id' => 'required|exists:tax_groups,id',
            'tax_rate_id' => 'required|exists:tax_rates,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Check if the combination already exists
            $existing = TaxGroupDetail::where('tax_group_id', $request->tax_group_id)
                ->where('tax_rate_id', $request->tax_rate_id)
                ->first();

            if ($existing) {
                return response()->json([
                    'success' => false,
                    'message' => 'This tax rate is already added to the tax group',
                ], 409);
            }

            // Validate ownership
            $taxGroup = TaxGroup::where('id', $request->tax_group_id)
                ->where('org_id', $request->org_id)
                ->where('company_id', $request->company_id)
                ->first();

            if (!$taxGroup) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tax group not found or access denied',
                ], 404);
            }

            $taxRate = TaxRate::where('id', $request->tax_rate_id)
                ->where('org_id', $request->org_id)
                ->where('company_id', $request->company_id)
                ->first();

            if (!$taxRate) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tax rate not found or access denied',
                ], 404);
            }

            // Create tax group detail
            $detail = TaxGroupDetail::create([
                'tax_group_id' => $request->tax_group_id,
                'tax_rate_id' => $request->tax_rate_id,
            ]);

            // Update tax group total rate
            $taxGroup->total_rate += $taxRate->rate;
            $taxGroup->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Tax rate added to group successfully',
                'data' => $detail->load('taxRate:id,name,rate,tax_type'),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to add tax rate: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove a tax rate from a tax group.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $detail = TaxGroupDetail::find($id);

        if (!$detail) {
            return response()->json([
                'success' => false,
                'message' => 'Tax group detail not found',
            ], 404);
        }

        try {
            DB::beginTransaction();

            // Validate ownership
            $taxGroup = TaxGroup::where('id', $detail->tax_group_id)
                ->where('org_id', $request->org_id)
                ->where('company_id', $request->company_id)
                ->first();

            if (!$taxGroup) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied',
                ], 403);
            }

            $taxRate = $detail->taxRate;

            // Delete the detail
            $detail->delete();

            // Update tax group total rate
            $taxGroup->total_rate -= $taxRate->rate;
            if ($taxGroup->total_rate < 0) {
                $taxGroup->total_rate = 0;
            }
            $taxGroup->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Tax rate removed from group successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove tax rate: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk add tax rates to a tax group.
     */
    public function bulkStore(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'tax_group_id' => 'required|exists:tax_groups,id',
            'tax_rate_ids' => 'required|array|min:1',
            'tax_rate_ids.*' => 'exists:tax_rates,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Validate ownership
            $taxGroup = TaxGroup::where('id', $request->tax_group_id)
                ->where('org_id', $request->org_id)
                ->where('company_id', $request->company_id)
                ->first();

            if (!$taxGroup) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tax group not found or access denied',
                ], 404);
            }

            // Validate all tax rates belong to same org/company
            $validTaxRates = TaxRate::whereIn('id', $request->tax_rate_ids)
                ->where('org_id', $request->org_id)
                ->where('company_id', $request->company_id)
                ->get();

            if ($validTaxRates->count() !== count($request->tax_rate_ids)) {
                return response()->json([
                    'success' => false,
                    'message' => 'One or more tax rates are invalid',
                ], 422);
            }

            // Get existing tax rate IDs for this group
            $existingTaxRateIds = TaxGroupDetail::where('tax_group_id', $request->tax_group_id)
                ->pluck('tax_rate_id')
                ->toArray();

            // Add only new tax rates
            $addedCount = 0;
            foreach ($request->tax_rate_ids as $taxRateId) {
                if (!in_array($taxRateId, $existingTaxRateIds)) {
                    TaxGroupDetail::create([
                        'tax_group_id' => $request->tax_group_id,
                        'tax_rate_id' => $taxRateId,
                    ]);
                    $addedCount++;
                }
            }

            // Recalculate total rate
            $taxGroup->total_rate = $taxGroup->taxRates()->sum('rate');
            $taxGroup->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "{$addedCount} tax rate(s) added to group",
                'data' => $taxGroup->load('taxRates:id,name,rate,tax_type'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to add tax rates: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk remove tax rates from a tax group.
     */
    public function bulkDestroy(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'tax_group_id' => 'required|exists:tax_groups,id',
            'tax_rate_ids' => 'required|array|min:1',
            'tax_rate_ids.*' => 'exists:tax_rates,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Validate ownership
            $taxGroup = TaxGroup::where('id', $request->tax_group_id)
                ->where('org_id', $request->org_id)
                ->where('company_id', $request->company_id)
                ->first();

            if (!$taxGroup) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tax group not found or access denied',
                ], 404);
            }

            // Delete specified tax rates from the group
            $deletedCount = TaxGroupDetail::where('tax_group_id', $request->tax_group_id)
                ->whereIn('tax_rate_id', $request->tax_rate_ids)
                ->delete();

            // Recalculate total rate
            $taxGroup->total_rate = $taxGroup->taxRates()->sum('rate');
            $taxGroup->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "{$deletedCount} tax rate(s) removed from group",
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove tax rates: ' . $e->getMessage(),
            ], 500);
        }
    }
}
