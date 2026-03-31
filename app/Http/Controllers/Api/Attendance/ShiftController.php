<?php

namespace App\Http\Controllers\Api\Attendance;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use App\Models\ShiftAssignment;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ShiftController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $shifts = Shift::withCount('assignments')->get();

        return $this->success($shifts);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'break_duration_minutes' => 'nullable|integer|min:0',
            'color' => 'nullable|string|max:20',
            'is_night_shift' => 'nullable|boolean',
            'overtime_after_hours' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $data = $request->all();
        if (isset($data['start_time']) && isset($data['end_time'])) {
            $data['is_night_shift'] = $data['end_time'] <= $data['start_time'];
        }

        $shift = Shift::create($data);

        return $this->created($shift, 'Shift created');
    }

    public function show(Shift $shift)
    {
        $shift->load('assignments.staffMember');

        return $this->success($shift);
    }

    public function update(Request $request, Shift $shift)
    {
        $data = $request->all();
        if (isset($data['start_time']) && isset($data['end_time'])) {
            $data['is_night_shift'] = $data['end_time'] <= $data['start_time'];
        }

        $shift->update($data);

        return $this->success($shift, 'Updated');
    }

    public function destroy(Shift $shift)
    {
        try {
            $shift->delete();
            return $this->success(null, 'Shift deleted successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to delete shift', 500, ['error' => $e->getMessage()]);
        }
    }

    public function deleteAssignment($assignmentId)
    {
        try {
            $assignment = ShiftAssignment::findOrFail($assignmentId);
            $assignment->delete();

            return response()->json([
                'success' => true,
                'message' => 'Assignment deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete assignment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function assign(Request $request, Shift $shift)
    {
        $validator = Validator::make($request->all(), [
            'staff_member_id' => 'required', // Remove array requirement
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date|after:effective_from',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        // Convert single staff member ID to array if needed
        $staffMemberIds = $request->staff_member_id;
        if (!is_array($staffMemberIds)) {
            $staffMemberIds = [$staffMemberIds];
        }

        // Validate each staff member ID exists
        foreach ($staffMemberIds as $staffMemberId) {
            if (!\App\Models\StaffMember::where('id', $staffMemberId)->exists()) {
                return $this->validationError([
                    'staff_member_id' => ['Staff member with ID ' . $staffMemberId . ' does not exist']
                ]);
            }
        }

        $assignments = collect();

        foreach ($staffMemberIds as $staffMemberId) {
            $assignment = ShiftAssignment::create([
                'shift_id' => $shift->id,
                'staff_member_id' => $staffMemberId,
                'effective_from' => $request->effective_from,
                'effective_to' => $request->effective_to,
            ]);

            $assignments->push($assignment->load('staffMember'));
        }

        return $this->success($assignments, 'Shift assigned successfully');
    }

    public function roster(Request $request)
    {
        $query = ShiftAssignment::with(['shift', 'staffMember']);

        if ($request->date) {
            $query->where('effective_from', '<=', $request->date)
                ->where(function ($q) use ($request) {
                    $q->whereNull('effective_to')
                        ->orWhere('effective_to', '>=', $request->date);
                });
        }

        return $this->success($query->get());
    }

    public function employeeShifts($staffMemberId)
    {
        $assignments = ShiftAssignment::where('staff_member_id', $staffMemberId)
            ->with('shift')
            ->orderBy('effective_from', 'desc')
            ->get();

        return $this->success($assignments);
    }
}
