<?php

namespace App\Http\Controllers\Api\Attendance;

use App\Http\Controllers\Controller;
use App\Models\StaffMember;
use App\Models\WorkLog;
use App\Services\Attendance\AttendanceService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use App\Services\Attendance\ShiftService;


/**
 * Work Log Controller
 *
 * Handles HTTP requests for attendance/work log management.
 */
class WorkLogController extends Controller
{
    use ApiResponse;

    protected AttendanceService $attendanceService;
    protected ShiftService $shiftService;
    protected $service;

    public function __construct(AttendanceService $attendanceService, ShiftService $shiftService)
    {
        $this->attendanceService = $attendanceService;
        $this->shiftService = $shiftService;
        $this->service = $attendanceService;
    }

    /**
     * Check if user is admin or has admin-like roles
     */
    protected function isAdminUser($user): bool
    {
        return $user->hasAnyRole(['admin', 'administrator', 'organisation', 'company', 'hr']);
    }

    /**
     * Get staff member ID for non-admin users
     */
    protected function getStaffMemberId($user): ?int
    {
        $staffMember = StaffMember::where('user_id', $user->id)->first();
        return $staffMember ? $staffMember->id : null;
    }

    /**
     * Display work logs for the current user (My Work Logs page).
     * This should return ONLY the logged-in user's work logs, regardless of role.
     */
    public function myLogs(Request $request): JsonResponse
    {
        try {
            $params = $request->only([
                'start_date',
                'end_date',
                'month',
                'year',
                'paginate',
                'per_page',
                'page',
            ]);

            $user = $request->user();

            // Get the staff member ID for the logged-in user
            $staffMemberId = $this->getStaffMemberId($user);

            if (!$staffMemberId) {
                // If user doesn't have a staff member record, return empty
                return $this->success([
                    'data' => [],
                    'meta' => [
                        'current_page' => 1,
                        'from' => null,
                        'last_page' => 1,
                        'links' => [],
                        'path' => $request->url(),
                        'per_page' => $params['per_page'] ?? 10,
                        'to' => null,
                        'total' => 0,
                    ]
                ], 'Work logs retrieved successfully');
            }

            // ALWAYS filter by the logged-in user's staff_member_id
            $params['staff_member_id'] = $staffMemberId;

            $result = $this->service->getAll($params);

            return $this->success($result, 'Work logs retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve work logs: ' . $e->getMessage());
        }
    }

    /**
     * Display a listing of work logs.
     */
/**
 * Display a listing of work logs.
 */
public function index(Request $request): JsonResponse
{
    try {
        Log::info('WorkLogController index called', ['params' => $request->all(), 'user' => $request->user()->id ?? null]);
        
        $params = $request->only([
            'staff_member_id',
            'office_location_id',
            'date',
            'start_date',
            'end_date',
            'month',
            'year',
            'paginate',
            'per_page',
            'page',
        ]);

        $user = $request->user();
        Log::info('User info', ['user_id' => $user->id ?? null, 'user_roles' => $user->roles ?? []]);

        // Check user role and adjust parameters
        if (!$this->isAdminUser($user)) {
            Log::info('User is not admin');
            // For non-admin users, they can only see their own work logs
            $staffMemberId = $this->getStaffMemberId($user);

            if ($staffMemberId) {
                $params['staff_member_id'] = $staffMemberId;
                Log::info('Non-admin staff member ID set', ['staff_member_id' => $staffMemberId]);
            } else {
                Log::warning('Staff member not found for non-admin user');
                return $this->error('Staff member not found', 404);
            }
        } else {
            Log::info('User is admin, can see all work logs');
        }
        // For admin users, they can see all work logs (staff_member_id filter remains optional)

        Log::info('Calling service getAll with params', $params);
        $result = $this->service->getAll($params);
        Log::info('Service getAll returned successfully');

        return $this->success($result, 'Work logs retrieved successfully');
    } catch (\Exception $e) {
        Log::error('Error in WorkLogController index', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'params' => $request->all()
        ]);
        return $this->serverError('Failed to retrieve work logs: ' . $e->getMessage());
    }
}

    /**
     * Store a new work log (manual attendance entry).
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'staff_member_id' => 'required|exists:staff_members,id',
                'log_date' => 'required|date',
                'clock_in' => 'nullable|date_format:H:i',
                'clock_out' => 'nullable|date_format:H:i|after:clock_in',
                'status' => 'nullable|in:present,absent,late,half_day,leave',
                'notes' => 'nullable|string|max:500',
            ]);

            $workLog = $this->service->recordAttendance($validated);

            return $this->created($workLog, 'Attendance recorded successfully');
        } catch (ValidationException $e) {
            return $this->validationError($e->errors());
        } catch (\Exception $e) {
            return $this->serverError('Failed to record attendance: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified work log.
     */
    public function show(int $id): JsonResponse
    {
        try {
            $workLog = $this->service->findById($id);

            if (! $workLog) {
                return $this->notFound('Work log not found');
            }

            return $this->success($workLog, 'Work log retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve work log: ' . $e->getMessage());
        }
    }

    /**
     * Update the specified work log.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'clock_in' => 'nullable|date_format:H:i',
                'clock_out' => 'nullable|date_format:H:i',
                'status' => 'nullable|in:present,absent,late,half_day,leave',
                'notes' => 'nullable|string|max:500',
            ]);

            $workLog = $this->service->update($id, $validated);

            return $this->success($workLog, 'Work log updated successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Work log not found');
        } catch (ValidationException $e) {
            return $this->validationError($e->errors());
        } catch (\Exception $e) {
            return $this->serverError('Failed to update work log: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified work log.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->service->delete($id);

            return $this->noContent('Work log deleted successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Work log not found');
        } catch (\Exception $e) {
            return $this->serverError('Failed to delete work log: ' . $e->getMessage());
        }
    }

    /**
     * Clock in for an employee.
     */
 public function clockIn(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            // Get staff member ID
            $staffMemberId = $request->input('staff_member_id');
            if (!$staffMemberId) {
                if (!$this->isAdminUser($user)) {
                    $staffMemberId = $this->getStaffMemberId($user);
                } else {
                    return $this->error('Staff member ID is required for admin users', 400);
                }
            }

            if (!$staffMemberId) {
                return $this->error('Staff member not found', 404);
            }

            $workLog = $this->attendanceService->clockIn((int)$staffMemberId, [
                'ip_address' => $request->ip(),
                'location' => $request->input('location'),
                'author_id' => $user->id,
            ]);

            return $this->success($workLog, 'Clocked in successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

     /**
     * Get employee's shift-based attendance analytics.
     */
    public function shiftAnalytics(Request $request): JsonResponse
    {
        try {
            $staffMemberId = $request->input('staff_member_id');
            $month = $request->input('month', now()->month);
            $year = $request->input('year', now()->year);

            if (!$staffMemberId) {
                return $this->error('Staff member ID is required', 422);
            }

            $attendance = $this->attendanceService->getEmployeeMonthlyAttendance($staffMemberId, $month, $year);
            
            // Calculate shift compliance
            $shift = $attendance['shift'] ?? null;
            $compliance = [];
            
            if ($shift) {
                $totalDays = $attendance['working_days'];
                $lateDays = $attendance['late_days'];
                $earlyLeaveDays = count(array_filter($attendance['records']->toArray(), function($record) {
                    return $record['early_leave_minutes'] > 0;
                }));
                
                $compliance = [
                    'shift_name' => $shift->name,
                    'shift_timings' => $shift->start_time . ' - ' . $shift->end_time,
                    'total_working_days' => $totalDays,
                    'punctuality_rate' => $totalDays > 0 
                        ? round((($totalDays - $lateDays) / $totalDays) * 100, 2) 
                        : 0,
                    'early_leave_rate' => $totalDays > 0 
                        ? round(($earlyLeaveDays / $totalDays) * 100, 2) 
                        : 0,
                    'average_late_minutes' => $attendance['late_days'] > 0 
                        ? round($attendance['total_late_minutes'] / $attendance['late_days'], 2) 
                        : 0,
                    'average_overtime_minutes' => $attendance['records']->count() > 0 
                        ? round($attendance['total_overtime_minutes'] / $attendance['records']->count(), 2) 
                        : 0,
                ];
            }

            $response = [
                'attendance_summary' => $attendance,
                'shift_compliance' => $compliance,
            ];

            return $this->success($response, 'Shift analytics retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve shift analytics: ' . $e->getMessage());
        }
    }

    /**
     * Clock out for an employee.
     */
    public function clockOut(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Get staff member ID from request or user
            $staffMemberId = $request->input('staff_member_id');

            // If staff_member_id is not provided in request
            if (!$staffMemberId) {
                // For non-admin users, use their own staff member ID
                if (!$this->isAdminUser($user)) {
                    $staffMemberId = $this->getStaffMemberId($user);
                } else {
                    // For admin users without staff_member_id, return error
                    return $this->error('Staff member ID is required for admin users', 400);
                }
            }

            if (! $staffMemberId) {
                return $this->error('Staff member not found', 404);
            }

            // Ensure staffMemberId is an integer
            $staffMemberId = (int) $staffMemberId;

            $workLog = $this->service->clockOut($staffMemberId, [
                'ip_address' => $request->ip(),
                'location' => $request->input('location'),
            ]);

            return $this->success($workLog, 'Clocked out successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Clock in for current user (self)
     */
    public function clockInSelf(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',
                'accuracy' => 'nullable|numeric|min:0',
            ]);

            $user = $request->user();
            $staffMemberId = $this->getStaffMemberId($user);

            if (!$staffMemberId) {
                return $this->error('Staff member not found', 404);
            }

            $workLog = $this->service->clockIn($staffMemberId, [
                'ip_address' => $request->ip(),
                'latitude' => $request->input('latitude'),
                'longitude' => $request->input('longitude'),
                'accuracy' => $request->input('accuracy'),
            ]);

            return $this->success($workLog, 'Clocked in successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Clock out for current user (self)
     */
    public function clockOutSelf(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',
                'accuracy' => 'nullable|numeric|min:0',
            ]);

            $user = $request->user();
            $staffMemberId = $this->getStaffMemberId($user);

            if (!$staffMemberId) {
                return $this->error('Staff member not found', 404);
            }

            $workLog = $this->service->clockOut($staffMemberId, [
                'ip_address' => $request->ip(),
                'latitude' => $request->input('latitude'),
                'longitude' => $request->input('longitude'),
                'accuracy' => $request->input('accuracy'),
            ]);

            return $this->success($workLog, 'Clocked out successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Get current status for current user (self)
     */
    public function currentStatusSelf(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $staffMemberId = $this->getStaffMemberId($user);

            if (!$staffMemberId) {
                return $this->error('Staff member not found', 404);
            }

            $status = $this->service->getCurrentStatus($staffMemberId);

            return $this->success($status, 'Current status retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve current status: ' . $e->getMessage());
        }
    }


    /**
     * Get today's attendance summary.
     */
    public function todaySummary(): JsonResponse
    {
        try {
            $summary = $this->service->getTodaySummary();

            return $this->success($summary, 'Today\'s attendance summary retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve attendance summary: ' . $e->getMessage());
        }
    }

    /**
     * Get attendance report.
     */
    public function report(Request $request): JsonResponse
    {
        try {
            $params = $request->only(['start_date', 'end_date']);
            $report = $this->service->getAttendanceReport($params);

            return $this->collection($report, 'Attendance report generated successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to generate attendance report: ' . $e->getMessage());
        }
    }

    /**
     * Get monthly attendance for an employee.
     */
    public function monthlyAttendance(Request $request, int $staffMemberId): JsonResponse
    {
        try {
            $month = $request->input('month', now()->month);
            $year = $request->input('year', now()->year);

            $attendance = $this->service->getEmployeeMonthlyAttendance($staffMemberId, $month, $year);

            return $this->success($attendance, 'Monthly attendance retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve monthly attendance: ' . $e->getMessage());
        }
    }

    /**
     * Get current attendance status for an employee.
     */
    public function currentStatus(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Get staff member ID from request or user
            $staffMemberId = $request->input('staff_member_id');

            // If staff_member_id is not provided in request
            if (!$staffMemberId) {
                // For non-admin users, use their own staff member ID
                if (!$this->isAdminUser($user)) {
                    $staffMemberId = $this->getStaffMemberId($user);
                } else {
                    // For admin users without staff_member_id, return error
                    return $this->error('Staff member ID is required for admin users', 400);
                }
            }

            if (! $staffMemberId) {
                return $this->error('Staff member not found', 404);
            }

            // Ensure staffMemberId is an integer
            $staffMemberId = (int) $staffMemberId;

            $status = $this->service->getCurrentStatus($staffMemberId);

            return $this->success($status, 'Current status retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve current status: ' . $e->getMessage());
        }
    }

    /**
     * Bulk record attendance.
     */
    public function bulkRecord(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'records' => 'required|array|min:1',
                'records.*.staff_member_id' => 'required|exists:staff_members,id',
                'records.*.log_date' => 'required|date',
                'records.*.status' => 'required|in:present,absent,late,half_day,leave',
            ]);

            $records = $this->service->bulkRecordAttendance($validated['records']);

            return $this->success([
                'recorded' => $records->count(),
            ], 'Bulk attendance recorded successfully');
        } catch (ValidationException $e) {
            return $this->validationError($e->errors());
        } catch (\Exception $e) {
            return $this->serverError('Failed to record bulk attendance: ' . $e->getMessage());
        }
    }

    /**
     * Bulk store attendance (alias for bulkRecord for route compatibility).
     */
    public function bulkStore(Request $request): JsonResponse
    {
        return $this->bulkRecord($request);
    }

 /**
     * Get enhanced attendance summary with shift info.
     */
    public function summary(Request $request): JsonResponse
    {
        try {
            $staffMemberId = $request->input('staff_member_id');
            $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
            $endDate = $request->input('end_date', now()->endOfMonth()->toDateString());

            $summary = $this->attendanceService->getSummaryForDateRange($startDate, $endDate, $staffMemberId);
            
            // Add shift information if staff member specified
            if ($staffMemberId) {
                $shiftSchedule = $this->shiftService->getEmployeeShiftSchedule(
                    $staffMemberId, 
                    $startDate, 
                    $endDate
                );
                $summary['shift_schedule'] = $shiftSchedule;
            }

            return $this->success($summary, 'Attendance summary retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve attendance summary: ' . $e->getMessage());
        }
    }

    /**
     * Get work log summary for the current user
     */
    public function mySummary(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $staffMemberId = $this->getStaffMemberId($user);

            if (!$staffMemberId) {
                return $this->success([], 'No work log summary found');
            }

            $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
            $endDate = $request->input('end_date', now()->endOfMonth()->toDateString());

            $summary = $this->service->getSummaryForDateRange($startDate, $endDate, $staffMemberId);

            return $this->success($summary, 'Work log summary retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve work log summary: ' . $e->getMessage());
        }
    }

    /**
     * Get monthly attendance for the current user
     */
    public function myMonthlyAttendance(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $staffMemberId = $this->getStaffMemberId($user);

            if (!$staffMemberId) {
                return $this->success([], 'No monthly attendance found');
            }

            $month = $request->input('month', now()->month);
            $year = $request->input('year', now()->year);

            $attendance = $this->service->getEmployeeMonthlyAttendance($staffMemberId, $month, $year);

            return $this->success($attendance, 'Monthly attendance retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve monthly attendance: ' . $e->getMessage());
        }
    }
}
