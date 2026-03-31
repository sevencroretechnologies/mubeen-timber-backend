<?php

namespace App\Http\Controllers\Api\Reports;

use App\Http\Controllers\Controller;
use App\Models\CompanyEvent;
use App\Models\CompanyNotice;
use App\Models\SalarySlip;
use App\Models\StaffMember;
use App\Models\TimeOffRequest;
use App\Models\WorkingDay;
use App\Models\WorkLog;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;
use OpenSpout\Writer\CSV\Writer as CSVWriter;
use OpenSpout\Writer\XLSX\Writer as XLSXWriter;

class ReportController extends Controller
{
    use ApiResponse;

    /**
     * Monthly attendance report.
     */
    public function attendanceReport(Request $request)
    {
        $validated = $request->validate([
            'month' => 'required|date_format:Y-m',
            'office_location_id' => 'nullable|exists:office_locations,id',
            'division_id' => 'nullable|exists:divisions,id',
            'staff_member_id' => 'nullable|exists:staff_members,id',
            'search' => 'nullable|string|max:255',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $page = $validated['page'] ?? 1;
        $perPage = $validated['per_page'] ?? 10;

        $period = Carbon::parse($validated['month'].'-01');
        $startDate = $period->copy()->startOfMonth();
        $endDate = $period->copy()->endOfMonth();

        // Get authenticated user for org_id and company_id
        $user = $request->user();

        // Fetch the working days configuration that covers this period
        $workingDayConfig = null;
        if ($user) {
            $query = WorkingDay::query();
            
            if ($user->org_id) {
                $query->where('org_id', $user->org_id);
            }
            
            if ($user->company_id) {
                $query->where('company_id', $user->company_id);
            }
            
            // Find configuration that overlaps with the selected month
            $workingDayConfig = $query->where(function ($q) use ($startDate, $endDate) {
                $q->where(function ($query) use ($startDate, $endDate) {
                    // Configuration covers the period
                    $query->where('from_date', '<=', $endDate->format('Y-m-d'))
                          ->where('to_date', '>=', $startDate->format('Y-m-d'));
                })->orWhere(function ($query) use ($startDate) {
                    // Open-ended configuration that started before or during the period
                    $query->where('from_date', '<=', $startDate->format('Y-m-d'))
                          ->whereNull('to_date');
                });
            })->first();
        }

        // Default to Monday-Friday if no configuration found
        $workingDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
        if ($workingDayConfig) {
            $workingDays = array_keys(array_filter($workingDayConfig->getWorkingDaysArray()));
        }

        // Calculate actual working days in the month based on configuration
        $totalWorkingDays = 0;
        $workingDates = [];
        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $dayName = strtolower($date->format('l'));
            if (in_array($dayName, $workingDays)) {
                $totalWorkingDays++;
                $workingDates[] = $date->format('Y-m-d');
            }
        }

        $query = StaffMember::active();

        if (! empty($validated['office_location_id'])) {
            $query->where('office_location_id', $validated['office_location_id']);
        }
        if (! empty($validated['division_id'])) {
            $query->where('division_id', $validated['division_id']);
        }
        if (! empty($validated['staff_member_id'])) {
            $query->where('id', $validated['staff_member_id']);
        }

        // Apply search filter
        if (! empty($validated['search'])) {
            $searchTerm = $validated['search'];
            $query->where(function ($q) use ($searchTerm) {
                $q->where('staff_code', 'like', "%{$searchTerm}%")
                  ->orWhere('full_name', 'like', "%{$searchTerm}%");
            });
        }

        $staffMembers = $query->get();
        $report = [];

        foreach ($staffMembers as $staff) {
            $logs = WorkLog::where('staff_member_id', $staff->id)
                ->forPeriod($startDate, $endDate)
                ->get()
                ->keyBy('log_date');

            // Ensure work logs exist for all working days in the period
            foreach ($workingDates as $currentDate) {
                if (! $logs->has($currentDate)) {
                    // Create a work log entry for this date (only for working days)
                    $log = WorkLog::updateOrCreate(
                        [
                            'staff_member_id' => $staff->id,
                            'log_date' => $currentDate,
                        ],
                        [
                            'status' => 'absent', // Default to absent until they clock in
                            'late_minutes' => 0,
                            'overtime_minutes' => 0,
                            'early_leave_minutes' => 0,
                            'break_minutes' => 0,
                            'tenant_id' => $staff->tenant_id,
                            'author_id' => auth()->id(),
                        ]
                    );
                    $logs->put($currentDate, $log);
                }
            }

            // Filter logs to only include working days for counting
            $workingDayLogs = $logs->filter(function ($log, $date) use ($workingDates) {
                return in_array($date, $workingDates);
            });

            // Count attendance based on clock_in presence for more accurate counting
            // A day is considered "present" if clock_in exists, regardless of status field
            $presentDays = $workingDayLogs->filter(function ($log) {
                return !empty($log->clock_in) || $log->status === 'present';
            })->count();
            $halfDays = $workingDayLogs->where('status', 'half_day')->count();
            $onLeave = $workingDayLogs->where('status', 'on_leave')->count();
            $holidays = $workingDayLogs->where('status', 'holiday')->count();
            // Absent = working days without clock_in and not on_leave/holiday
            $absentDays = $workingDayLogs->filter(function ($log) {
                return empty($log->clock_in) && 
                       !in_array($log->status, ['present', 'half_day', 'on_leave', 'holiday']);
            })->count();

            $report[] = [
                'staff_member' => [
                    'id' => $staff->id,
                    'staff_code' => $staff->staff_code,
                    'full_name' => $staff->full_name,
                ],
                'present_days' => $presentDays,
                'absent_days' => $absentDays,
                'half_days' => $halfDays,
                'on_leave' => $onLeave,
                'holidays' => $holidays,
                'total_late_minutes' => (int) $workingDayLogs->sum('late_minutes'),
                'total_overtime_minutes' => (int) $workingDayLogs->sum('overtime_minutes'),
                'total_early_leave_minutes' => (int) $workingDayLogs->sum('early_leave_minutes'),
            ];
        }

        // Calculate summary from all data
        $summary = [
            'total_staff' => count($report),
            'total_present_days' => collect($report)->sum('present_days'),
            'total_absent_days' => collect($report)->sum('absent_days'),
            'total_half_days' => collect($report)->sum('half_days'),
            'total_on_leave' => collect($report)->sum('on_leave'),
            'total_holidays' => collect($report)->sum('holidays'),
            'total_late_minutes' => collect($report)->sum('total_late_minutes'),
            'total_overtime_minutes' => collect($report)->sum('total_overtime_minutes'),
            'total_early_leave_minutes' => collect($report)->sum('total_early_leave_minutes'),
        ];

        // Apply pagination to report
        $paginatedReport = collect($report)->slice(($page - 1) * $perPage, $perPage)->values();

        return response()->json([
            'success' => true,
            'data' => [
                'period' => $validated['month'],
                'total_working_days' => $totalWorkingDays,
                'working_days_config' => $workingDays,
                'summary' => $summary,
                'report' => $paginatedReport,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => count($report),
                    'last_page' => (int) ceil(count($report) / $perPage),
                ],
            ],
        ]);
    }

    /**
     * Leave report.
     */
    public function leaveReport(Request $request)
    {
        $validated = $request->validate([
            'year' => 'required|integer|min:2020|max:2100',
            'month' => 'nullable|integer|min:1|max:12',
            'time_off_category_id' => 'nullable|exists:time_off_categories,id',
            'staff_member_id' => 'nullable|exists:staff_members,id',
            'search' => 'nullable|string|max:255',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $page = $validated['page'] ?? 1;
        $perPage = $validated['per_page'] ?? 10;

        $query = TimeOffRequest::with(['staffMember', 'category'])
            ->whereYear('start_date', $validated['year']);

        if (! empty($validated['month'])) {
            $query->whereMonth('start_date', $validated['month']);
        }
        if (! empty($validated['time_off_category_id'])) {
            $query->where('time_off_category_id', $validated['time_off_category_id']);
        }
        if (! empty($validated['staff_member_id'])) {
            $query->where('staff_member_id', $validated['staff_member_id']);
        }

        // Apply search filter
        if (! empty($validated['search'])) {
            $searchTerm = $validated['search'];
            $query->whereHas('staffMember', function ($q) use ($searchTerm) {
                $q->where('staff_code', 'like', "%{$searchTerm}%")
                  ->orWhere('full_name', 'like', "%{$searchTerm}%");
            });
        }

        // Get all requests for summary (before pagination)
        $allRequests = (clone $query)->get();

        // Calculate summary
        $summary = [
            'total_requests' => $allRequests->count(),
            'approved' => $allRequests->where('approval_status', 'approved')->count(),
            'pending' => $allRequests->where('approval_status', 'pending')->count(),
            'declined' => $allRequests->where('approval_status', 'declined')->count(),
            'total_days_taken' => $allRequests->where('approval_status', 'approved')->sum('total_days'),
        ];

        // Group by category (all data, not paginated)
        $byCategory = $allRequests->where('approval_status', 'approved')
            ->groupBy('time_off_category_id')
            ->map(function ($items, $categoryId) {
                $category = $items->first()->category;

                return [
                    'category_id' => $categoryId,
                    'category_title' => $category?->title,
                    'count' => $items->count(),
                    'total_days' => $items->sum('total_days'),
                ];
            })->values();

        // Apply pagination to by_category
        $paginatedCategories = $byCategory->slice(($page - 1) * $perPage, $perPage)->values();

        // When a specific category is selected, provide employee-level breakdown
        $byEmployee = [];
        $categoryAnnualQuota = null;
        if (! empty($validated['time_off_category_id'])) {
            // Fetch the category to get annual_quota
            $selectedCategory = \App\Models\TimeOffCategory::find($validated['time_off_category_id']);
            $categoryAnnualQuota = $selectedCategory?->annual_quota ?? 0;

            $byEmployee = $allRequests
                ->groupBy('staff_member_id')
                ->map(function ($items, $staffMemberId) use ($categoryAnnualQuota) {
                    $staffMember = $items->first()->staffMember;
                    $approvedItems = $items->where('approval_status', 'approved');
                    $pendingItems = $items->where('approval_status', 'pending');

                    return [
                        'staff_member_id' => $staffMemberId,
                        'employee_name' => $staffMember?->full_name ?? 'Unknown',
                        'staff_code' => $staffMember?->staff_code ?? 'N/A',
                        'approved_count' => $approvedItems->count(),
                        'approved_days' => $approvedItems->sum('total_days'),
                        'pending_count' => $pendingItems->count(),
                        'pending_days' => $pendingItems->sum('total_days'),
                        'annual_quota' => $categoryAnnualQuota,
                    ];
                })
                ->values()
                ->toArray();
        }

        return response()->json([
            'success' => true,
            'data' => [
                'year' => $validated['year'],
                'month' => $validated['month'] ?? 'all',
                'summary' => $summary,
                'by_category' => $paginatedCategories,
                'by_employee' => $byEmployee,
                'category_annual_quota' => $categoryAnnualQuota,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $byCategory->count(),
                    'last_page' => (int) ceil($byCategory->count() / $perPage),
                ],
            ],
        ]);
    }

    /**
     * Payroll report.
     */
    public function payrollReport(Request $request)
    {
        $validated = $request->validate([
            'salary_period' => 'required|date_format:Y-m',
            'office_location_id' => 'nullable|exists:office_locations,id',
            'division_id' => 'nullable|exists:divisions,id',
        ]);

        // Parse the period to match database date format (Y-m-01)
        $period = Carbon::parse($validated['salary_period'].'-01');

        $query = SalarySlip::with(['staffMember.officeLocation', 'staffMember.division'])
            ->whereDate('salary_period', $period);

        if (! empty($validated['office_location_id'])) {
            $query->whereHas('staffMember', function ($q) use ($validated) {
                $q->where('office_location_id', $validated['office_location_id']);
            });
        }
        if (! empty($validated['division_id'])) {
            $query->whereHas('staffMember', function ($q) use ($validated) {
                $q->where('division_id', $validated['division_id']);
            });
        }

        $slips = $query->get();

        $summary = [
            'total_employees' => $slips->count(),
            'total_basic_salary' => $slips->sum('basic_salary'),
            'total_earnings' => $slips->sum('total_earnings'),
            'total_deductions' => $slips->sum('total_deductions'),
            'total_net_payable' => $slips->sum('net_payable'),
            'paid_count' => $slips->where('status', 'paid')->count(),
            'pending_count' => $slips->where('status', '!=', 'paid')->count(),
        ];

        // Group by division
        $byDivision = $slips->groupBy(function ($slip) {
            return $slip->staffMember?->division_id;
        })->map(function ($items, $divisionId) {
            $division = $items->first()->staffMember?->division;

            return [
                'division_id' => $divisionId,
                'division_title' => $division?->title ?? 'Unassigned',
                'employee_count' => $items->count(),
                'total_net_payable' => $items->sum('net_payable'),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => [
                'salary_period' => $validated['salary_period'],
                'summary' => $summary,
                'by_division' => $byDivision,
                'slips' => $slips,
            ],
        ]);
    }

    /**
     * Dashboard statistics.
     */
    public function dashboard(Request $request)
    {
        $today = now();
        $monthStart = $today->copy()->startOfMonth();
        $monthEnd = $today->copy()->endOfMonth();

        // Employee counts
        $totalEmployees = StaffMember::count();
        $activeEmployees = StaffMember::active()->count();
        $newThisMonth = StaffMember::whereMonth('hire_date', $today->month)
            ->whereYear('hire_date', $today->year)->count();

        // Attendance today
        $todayAttendance = WorkLog::forDate($today->toDateString())->get();
        $presentToday = $todayAttendance->where('status', 'present')->count();
        $absentToday = $todayAttendance->where('status', 'absent')->count();

        // Leave requests
        $pendingLeaves = TimeOffRequest::pending()->count();
        $approvedLeavesThisMonth = TimeOffRequest::approved()
            ->forPeriod($monthStart, $monthEnd)->count();

        // Upcoming events
        $upcomingEvents = CompanyEvent::upcoming()->limit(5)->get();

        // Recent announcements (filter by publish_date and expire_date)
        $recentAnnouncements = CompanyNotice::where('publish_date', '<=', $today)
            ->where(function ($query) use ($today) {
                $query->whereNull('expire_date')
                    ->orWhere('expire_date', '>=', $today);
            })
            ->latest()
            ->limit(5)
            ->get();

        // Payroll status for current month
        $currentPeriod = $today->format('Y-m');
        $payrollStatus = [
            'period' => $currentPeriod,
            'generated' => SalarySlip::forPeriod($currentPeriod)->count(),
            'paid' => SalarySlip::forPeriod($currentPeriod)->paid()->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'employees' => [
                    'total' => $totalEmployees,
                    'active' => $activeEmployees,
                    'new_this_month' => $newThisMonth,
                ],
                'attendance_today' => [
                    'present' => $presentToday,
                    'absent' => $absentToday,
                    'not_marked' => $activeEmployees - $todayAttendance->count(),
                ],
                'leave_requests' => [
                    'pending' => $pendingLeaves,
                    'approved_this_month' => $approvedLeavesThisMonth,
                ],
                'payroll' => $payrollStatus,
                'upcoming_events' => $upcomingEvents,
                'recent_announcements' => $recentAnnouncements,
            ],
        ]);
    }

    /**
     * Headcount report by location/division.
     */
    public function headcountReport(Request $request)
    {
        $byLocation = StaffMember::active()
            ->select('office_location_id', DB::raw('COUNT(*) as count'))
            ->with('officeLocation')
            ->groupBy('office_location_id')
            ->get()
            ->map(function ($item) {
                return [
                    'location_id' => $item->office_location_id,
                    'location_name' => $item->officeLocation?->title ?? 'Unassigned',
                    'count' => $item->count,
                ];
            });

        $byDivision = StaffMember::active()
            ->select('division_id', DB::raw('COUNT(*) as count'))
            ->with('division')
            ->groupBy('division_id')
            ->get()
            ->map(function ($item) {
                return [
                    'division_id' => $item->division_id,
                    'division_name' => $item->division?->title ?? 'Unassigned',
                    'count' => $item->count,
                ];
            });

        $byJobTitle = StaffMember::active()
            ->select('job_title_id', DB::raw('COUNT(*) as count'))
            ->with('jobTitle')
            ->groupBy('job_title_id')
            ->get()
            ->map(function ($item) {
                return [
                    'job_title_id' => $item->job_title_id,
                    'job_title_name' => $item->jobTitle?->title ?? 'Unassigned',
                    'count' => $item->count,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'total_active' => StaffMember::active()->count(),
                'by_location' => $byLocation,
                'by_division' => $byDivision,
                'by_job_title' => $byJobTitle,
            ],
        ]);
    }

    /**
     * Export attendance report to CSV.
     */
    public function exportAttendanceReport(Request $request)
    {
        $validated = $request->validate([
            'month' => 'required|date_format:Y-m',
            'office_location_id' => 'nullable|exists:office_locations,id',
            'division_id' => 'nullable|exists:divisions,id',
            'staff_member_id' => 'nullable|exists:staff_members,id',
            'format' => 'required|in:csv,xlsx',
        ]);

        $period = Carbon::parse($validated['month'].'-01');
        $startDate = $period->copy()->startOfMonth();
        $endDate = $period->copy()->endOfMonth();

        $query = StaffMember::active();

        if (! empty($validated['office_location_id'])) {
            $query->where('office_location_id', $validated['office_location_id']);
        }
        if (! empty($validated['division_id'])) {
            $query->where('division_id', $validated['division_id']);
        }
        if (! empty($validated['staff_member_id'])) {
            $query->where('id', $validated['staff_member_id']);
        }

        $staffMembers = $query->get();

        $fileName = "attendance_report_{$validated['month']}.{$validated['format']}";

        return new StreamedResponse(function () use ($staffMembers, $startDate, $endDate, $validated) {
            $output = fopen('php://output', 'w');

            // CSV Header
            fputcsv($output, [
                'Staff Code',
                'Full Name',
                'Present Days',
                'Absent Days',
                'Half Days',
                'On Leave',
                'Holidays',
                'Late Minutes',
                'Overtime Minutes',
                'Early Leave Minutes',
            ]);

            // CSV Data
            foreach ($staffMembers as $staff) {
                $logs = WorkLog::where('staff_member_id', $staff->id)
                    ->forPeriod($startDate, $endDate)
                    ->get();

                fputcsv($output, [
                    $staff->staff_code,
                    $staff->full_name,
                    $logs->where('status', 'present')->count(),
                    $logs->where('status', 'absent')->count(),
                    $logs->where('status', 'half_day')->count(),
                    $logs->where('status', 'on_leave')->count(),
                    $logs->where('status', 'holiday')->count(),
                    $logs->sum('late_minutes'),
                    $logs->sum('overtime_minutes'),
                    $logs->sum('early_leave_minutes'),
                ]);
            }

            fclose($output);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
    }

    /**
     * Export leave report to CSV.
     */
    public function exportLeaveReport(Request $request)
    {
        $validated = $request->validate([
            'year' => 'required|integer|min:2020|max:2100',
            'month' => 'nullable|integer|min:1|max:12',
            'time_off_category_id' => 'nullable|exists:time_off_categories,id',
            'staff_member_id' => 'nullable|exists:staff_members,id',
            'format' => 'required|in:csv,xlsx',
        ]);

        $query = TimeOffRequest::with(['staffMember', 'category'])
            ->whereYear('start_date', $validated['year']);

        if (! empty($validated['month'])) {
            $query->whereMonth('start_date', $validated['month']);
        }
        if (! empty($validated['time_off_category_id'])) {
            $query->where('time_off_category_id', $validated['time_off_category_id']);
        }
        if (! empty($validated['staff_member_id'])) {
            $query->where('staff_member_id', $validated['staff_member_id']);
        }

        $requests = $query->get();

        $fileName = "leave_report_{$validated['year']}".(isset($validated['month']) ? "_{$validated['month']}" : '').".{$validated['format']}";

        return new StreamedResponse(function () use ($requests) {
            $output = fopen('php://output', 'w');

            // CSV Header
            fputcsv($output, [
                'Staff Code',
                'Staff Name',
                'Category',
                'Start Date',
                'End Date',
                'Total Days',
                'Reason',
                'Status',
                'Applied On',
            ]);

            // CSV Data
            foreach ($requests as $request) {
                fputcsv($output, [
                    $request->staffMember->staff_code ?? 'N/A',
                    $request->staffMember->full_name ?? 'N/A',
                    $request->category->title ?? 'N/A',
                    $request->start_date,
                    $request->end_date,
                    $request->total_days,
                    $request->reason ?? 'N/A',
                    $request->approval_status,
                    $request->created_at->format('Y-m-d'),
                ]);
            }

            fclose($output);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
    }

    /**
     * Export payroll report to CSV.
     */
    public function exportPayrollReport(Request $request)
    {
        $validated = $request->validate([
            'salary_period' => 'required|date_format:Y-m',
            'office_location_id' => 'nullable|exists:office_locations,id',
            'division_id' => 'nullable|exists:divisions,id',
            'format' => 'required|in:csv,xlsx',
        ]);

        // Parse the period to match database date format (Y-m-01)
        $period = Carbon::parse($validated['salary_period'].'-01');

        $query = SalarySlip::with(['staffMember.officeLocation', 'staffMember.division'])
            ->whereDate('salary_period', $period);

        if (! empty($validated['office_location_id'])) {
            $query->whereHas('staffMember', function ($q) use ($validated) {
                $q->where('office_location_id', $validated['office_location_id']);
            });
        }
        if (! empty($validated['division_id'])) {
            $query->whereHas('staffMember', function ($q) use ($validated) {
                $q->where('division_id', $validated['division_id']);
            });
        }

        $slips = $query->get();

        $fileName = "payroll_report_{$validated['salary_period']}.{$validated['format']}";

        return new StreamedResponse(function () use ($slips) {
            $output = fopen('php://output', 'w');

            // CSV Header
            fputcsv($output, [
                'Staff Code',
                'Staff Name',
                'Basic Salary',
                'Total Earnings',
                'Total Deductions',
                'Net Payable',
                'Status',
                'Payment Date',
            ]);

            // CSV Data
            foreach ($slips as $slip) {
                fputcsv($output, [
                    $slip->staffMember->staff_code ?? 'N/A',
                    $slip->staffMember->full_name ?? 'N/A',
                    number_format($slip->basic_salary, 2),
                    number_format($slip->total_earnings, 2),
                    number_format($slip->total_deductions, 2),
                    number_format($slip->net_payable, 2),
                    $slip->status,
                    $slip->payment_date ?? 'N/A',
                ]);
            }

            fclose($output);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
    }
}
