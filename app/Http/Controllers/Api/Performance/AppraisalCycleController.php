<?php

namespace App\Http\Controllers\Api\Performance;

use App\Http\Controllers\Controller;
use App\Models\AppraisalCycle;
use App\Models\AppraisalRecord;
use App\Models\StaffMember;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class AppraisalCycleController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $user = $request->user();
        $query = AppraisalCycle::withCount('records')->with('author');

        // Filter by authenticated user's org_id and company_id (same as StaffMemberController)
        if ($user) {
            if ($user->org_id) {
                $query->where('org_id', $user->org_id);
            }
            if ($user->company_id) {
                $query->where('company_id', $user->company_id);
            }
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $cycles = $request->boolean('paginate', true)
            ? $query->latest()->paginate($request->input('per_page', 15))
            : $query->latest()->get();

        return $this->success($cycles);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'cycle_start' => 'required|date',
            'cycle_end' => 'required|date|after_or_equal:cycle_start',
            'review_deadline' => 'nullable|date|after_or_equal:cycle_start',
            'notes' => 'nullable|string',
        ]);

        $validated['status'] = 'draft';
        $validated['author_id'] = $request->user()->id;

        // Add org_id and company_id from authenticated user (same as StaffMemberController)
        $authenticatedUser = $request->user();
        $validated['org_id'] = $authenticatedUser->org_id ?? null;
        $validated['company_id'] = $authenticatedUser->company_id ?? null;

        $cycle = AppraisalCycle::create($validated);

        return $this->created($cycle, 'Appraisal cycle created');
    }

    public function show(AppraisalCycle $appraisalCycle)
    {
        return $this->success($appraisalCycle->load(['author', 'records.staffMember']));
    }

    /**
     * Activate cycle and create records for all active staff.
     */
    public function activate(Request $request, AppraisalCycle $appraisalCycle)
    {
        if ($appraisalCycle->status !== 'draft') {
            return $this->error('Cycle is not in draft status', 422);
        }

        // Get authenticated user's org and company
        $user = $request->user();

        // Create appraisal records for active staff within the same org/company
        $staffQuery = StaffMember::active();

        if ($user->org_id) {
            $staffQuery->where('org_id', $user->org_id);
        }
        if ($user->company_id) {
            $staffQuery->where('company_id', $user->company_id);
        }

        $staffMembers = $staffQuery->get();
        $created = 0;

        foreach ($staffMembers as $staff) {
            if (! AppraisalRecord::where('appraisal_cycle_id', $appraisalCycle->id)
                ->where('staff_member_id', $staff->id)->exists()) {
                AppraisalRecord::create([
                    'appraisal_cycle_id' => $appraisalCycle->id,
                    'staff_member_id' => $staff->id,
                    'status' => 'pending',
                ]);
                $created++;
            }
        }

        $appraisalCycle->update(['status' => 'active']);

        return $this->success($appraisalCycle->fresh(), "Cycle activated. Created {$created} appraisal records.");
    }

    /**
     * Close cycle.
     */
    public function close(Request $request, AppraisalCycle $appraisalCycle)
    {
        $appraisalCycle->update(['status' => 'closed']);

        return $this->success($appraisalCycle->fresh(), 'Cycle closed');
    }

    public function update(Request $request, AppraisalCycle $appraisalCycle)
    {
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'cycle_start' => 'sometimes|required|date',
            'cycle_end' => 'sometimes|required|date',
            'review_deadline' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $appraisalCycle->update($validated);

        return $this->success($appraisalCycle->fresh(), 'Cycle updated');
    }

    public function destroy(AppraisalCycle $appraisalCycle)
    {
        if ($appraisalCycle->status !== 'draft') {
            return $this->error('Can only delete draft cycles', 422);
        }

        $appraisalCycle->delete();

        return $this->noContent('Cycle deleted');
    }
}
