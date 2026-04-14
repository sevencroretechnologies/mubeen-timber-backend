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

class TaxGroupController extends Controller
{
    /**
     * Display a listing of tax groups.
     */
    public function index(Request $request): JsonResponse
    {
        $query = TaxGroup::with(['taxRates:id,name,rate,tax_type'])
            ->where('org_id', $request->org_id)
            ->where('company_id', $request->company_id)
            ->orderBy('name');

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%')
                ->orWhere('code', 'like', '%' . $request->search . '%');
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $taxGroups = $query->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => $taxGroups->items(),
            'pagination' => [
                'current_page' => $taxGroups->currentPage(),
                'total_pages' => $taxGroups->lastPage(),
                'per_page' => $taxGroups->perPage(),
                'total_items' => $taxGroups->total(),
            ],
        ]);
    }

    /**
     * Store a newly created tax group with details in single transaction.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:100|unique:tax_groups,code,NULL,id,org_id,' . $request->org_id . ',company_id,' . $request->company_id,
            'tax_rate_ids' => 'required|array|min:1',
            'tax_rate_ids.*' => 'exists:tax_rates,id',
            'is_active' => 'boolean',
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

            // Create tax group with details in transaction
            $taxGroup = $this->createTaxGroupWithDetails(
                $request->org_id,
                $request->company_id,
                $request->name,
                $request->code,
                $request->tax_rate_ids,
                $request->boolean('is_active', true)
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Tax group created successfully',
                'data' => $taxGroup->load('taxRates:id,name,rate,tax_type'),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create tax group: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified tax group.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $taxGroup = TaxGroup::with(['taxRates:id,name,rate,tax_type'])
            ->where('org_id', $request->org_id)
            ->where('company_id', $request->company_id)
            ->find($id);

        if (!$taxGroup) {
            return response()->json([
                'success' => false,
                'message' => 'Tax group not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $taxGroup,
        ]);
    }

    /**
     * Update the specified tax group with details in single transaction.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $taxGroup = TaxGroup::where('org_id', $request->org_id)
            ->where('company_id', $request->company_id)
            ->find($id);

        if (!$taxGroup) {
            return response()->json([
                'success' => false,
                'message' => 'Tax group not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'code' => 'nullable|string|max:100|unique:tax_groups,code,' . $id . ',id,org_id,' . $request->org_id . ',company_id,' . $request->company_id,
            'tax_rate_ids' => 'sometimes|required|array|min:1',
            'tax_rate_ids.*' => 'exists:tax_rates,id',
            'is_active' => 'boolean',
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

            // Update tax group details if provided
            if ($request->has('tax_rate_ids')) {
                $this->syncTaxGroupDetails($taxGroup, $request->tax_rate_ids);
            }

            // Update basic fields
            $taxGroup->update($request->only(['name', 'code', 'is_active']));

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Tax group updated successfully',
                'data' => $taxGroup->load('taxRates:id,name,rate,tax_type'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update tax group: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified tax group.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $taxGroup = TaxGroup::where('org_id', $request->org_id)
            ->where('company_id', $request->company_id)
            ->find($id);

        if (!$taxGroup) {
            return response()->json([
                'success' => false,
                'message' => 'Tax group not found',
            ], 404);
        }

        try {
            DB::beginTransaction();

            // Delete tax group details first (cascade should handle this, but being explicit)
            TaxGroupDetail::where('tax_group_id', $taxGroup->id)->delete();

            // Delete tax group (soft delete)
            $taxGroup->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Tax group deleted successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete tax group: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get list of active tax groups (for dropdowns).
     */
    public function list(Request $request): JsonResponse
    {
        $taxGroups = TaxGroup::active()
            ->where('org_id', $request->org_id)
            ->where('company_id', $request->company_id)
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'total_rate']);

        return response()->json([
            'success' => true,
            'data' => $taxGroups,
        ]);
    }

    /**
     * Helper: Create tax group with details in single transaction.
     * Ensures tax_group_details are saved immediately.
     */
    protected function createTaxGroupWithDetails(
        int $orgId,
        int $companyId,
        string $name,
        ?string $code,
        array $taxRateIds,
        bool $isActive = true
    ): TaxGroup {
        // Validate tax rates belong to the same org/company
        $validTaxRates = TaxRate::whereIn('id', $taxRateIds)
            ->where('org_id', $orgId)
            ->where('company_id', $companyId)
            ->get();

        if ($validTaxRates->count() !== count($taxRateIds)) {
            throw new \Exception('One or more tax rates are invalid');
        }

        // Calculate total rate
        $totalRate = $validTaxRates->sum('rate');

        // Create tax group
        $taxGroup = TaxGroup::create([
            'name' => $name,
            'code' => $code,
            'total_rate' => $totalRate,
            'is_active' => $isActive,
            'org_id' => $orgId,
            'company_id' => $companyId,
        ]);

        // Prepare details for batch insert
        $detailsData = array_map(fn($id) => [
            'tax_group_id' => $taxGroup->id,
            'tax_rate_id' => $id,
            'created_at' => now(),
            'updated_at' => now(),
        ], $taxRateIds);

        // Batch insert details (more efficient than loop)
        TaxGroupDetail::insert($detailsData);

        return $taxGroup;
    }

    /**
     * Helper: Sync tax group details (replace all).
     * Removes old details and adds new ones in transaction.
     */
    protected function syncTaxGroupDetails(TaxGroup $taxGroup, array $taxRateIds): void
    {
        // Validate tax rates belong to the same org/company
        $validTaxRates = TaxRate::whereIn('id', $taxRateIds)
            ->where('org_id', $taxGroup->org_id)
            ->where('company_id', $taxGroup->company_id)
            ->get();

        if ($validTaxRates->count() !== count($taxRateIds)) {
            throw new \Exception('One or more tax rates are invalid');
        }

        // Delete existing details
        TaxGroupDetail::where('tax_group_id', $taxGroup->id)->delete();

        // Prepare details for batch insert
        $detailsData = array_map(fn($id) => [
            'tax_group_id' => $taxGroup->id,
            'tax_rate_id' => $id,
            'created_at' => now(),
            'updated_at' => now(),
        ], $taxRateIds);

        // Batch insert new details
        TaxGroupDetail::insert($detailsData);

        // Update total rate
        $taxGroup->total_rate = $validTaxRates->sum('rate');
        $taxGroup->save();
    }
}
