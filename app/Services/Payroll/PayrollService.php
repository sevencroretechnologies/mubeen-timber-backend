<?php

namespace App\Services\Payroll;

use App\Models\RecurringDeduction;
use App\Models\SalarySlip;
use App\Models\StaffBenefit;
use App\Models\StaffMember;
use App\Services\Core\BaseService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

/**     
 * Payroll Service
 *
 * Handles all business logic for payroll processing.
 */
class PayrollService extends BaseService
{
    protected string $modelClass = SalarySlip::class;

    protected array $defaultRelations = [
        'staffMember',
        'staffMember.jobTitle',
        'staffMember.division',
    ];

    protected array $filterableFields = [
        'staff_member_id' => 'staff_member_id',
        'status' => 'status',
    ];

    /**
     * Get all salary slips.
     */
    public function getAllSalarySlips(array $params = [])
    {
        $query = $this->query()->with($this->defaultRelations);

        $query = $this->applyFilters($query, $params);

        // Month/Year filter - salary_period format is YYYY-MM
        if (! empty($params['month']) && ! empty($params['year'])) {
            $salaryPeriod = sprintf('%04d-%02d-01', $params['year'], $params['month']);
            $query->where('salary_period', $salaryPeriod);
        }

        // Direct salary_period filter
        if (! empty($params['salary_period'])) {
            $query->where('salary_period', $params['salary_period']);
        }

        $query = $this->applyOrdering($query, $params);

        $paginate = $params['paginate'] ?? true;
        $perPage = $params['per_page'] ?? $this->perPage;

        return $paginate
            ? $query->paginate($perPage)
            : $query->get();
    }

    /**
     * Generate salary slip for an employee.
     */
    /**
     * Generate salary slip for an employee.
     */
    public function generateSalarySlip(int $staffMemberId, int $month, int $year): SalarySlip
    {
        return DB::transaction(function () use ($staffMemberId, $month, $year) {
            $salaryPeriod = sprintf('%04d-%02d-01', $year, $month);

            // Check if slip already exists
            $existing = SalarySlip::where('staff_member_id', $staffMemberId)
                ->where('salary_period', $salaryPeriod)
                ->first();

            if ($existing) {
                return $existing;
            }

            // Calculate entire salary breakdown
            $breakdown = $this->calculateSalaryDetails($staffMemberId, $month, $year);

            return SalarySlip::create([
                'staff_member_id' => $staffMemberId,
                'salary_period' => $salaryPeriod,
                'basic_salary' => $breakdown['salary']['base_salary'],
                'benefits_breakdown' => $breakdown['benefits']['breakdown'],
                'deductions_breakdown' => $breakdown['deductions']['breakdown'], // Includes LOP
                'total_earnings' => $breakdown['salary']['total_earnings'],
                'total_deductions' => $breakdown['salary']['total_deductions'],
                'net_payable' => $breakdown['salary']['net_salary'],
                'status' => 'generated',
                'generated_at' => now(),
            ]);
        });
    }

    /**
     * Calculate detailed salary breakdown including LOP and attendance.
     */
    public function calculateSalaryDetails(int $staffMemberId, int $month, int $year): array
    {
        $employee = StaffMember::with(['jobTitle', 'company'])->findOrFail($staffMemberId);
        $periodStart = sprintf('%04d-%02d-01', $year, $month);
        $startDate = \Carbon\Carbon::parse($periodStart);
        $endDate = $startDate->copy()->endOfMonth();

        // Get Working Days Configuration for this employee's organization/company
        $workingDaysConfig = $this->getWorkingDaysConfig($employee, $startDate, $endDate);

        // Calculate total working days in the month (excluding non-working days like weekends)
        $totalWorkingDays = $workingDaysConfig['total_working_days'];
        $workingDates = $workingDaysConfig['working_dates'];
        $nonWorkingDates = $workingDaysConfig['non_working_dates'];
        $workingDaysArray = $workingDaysConfig['working_days']; // ['monday', 'tuesday', etc.]

        // 1. Attendance Stats - Only count working days
        $workLogs = \App\Models\WorkLog::where('staff_member_id', $staffMemberId)
            ->whereBetween('log_date', [$startDate, $endDate])
            ->get();

        // Create a collection of working dates for easy lookup
        $workingDatesCollection = collect($workingDates);

        // Filter work logs to only include working days
        $workLogsOnWorkingDays = $workLogs->filter(function ($log) use ($workingDates) {
            return in_array($log->log_date->format('Y-m-d'), $workingDates);
        });

        // Get dates that have work logs
        $datesWithLogs = $workLogsOnWorkingDays->pluck('log_date')->map(function ($date) {
            return $date->format('Y-m-d');
        })->unique()->toArray();

        // Count no-show days (working days WITHOUT any work log)
        $noShowDays = $workingDatesCollection->filter(function ($dateStr) use ($datesWithLogs) {
            return !in_array($dateStr, $datesWithLogs);
        })->count();

        // Count attendance from work logs (only on working days)
        $presentDays = $workLogsOnWorkingDays->filter(function ($log) {
            return in_array($log->status, ['present', 'late']);
        })->count();

        $absentDays = $workLogsOnWorkingDays->where('status', 'absent')->count();
        $halfDays = $workLogsOnWorkingDays->where('status', 'half_day')->count();
        $lateDays = $workLogsOnWorkingDays->where('status', 'late')->count();

        // Total absents = marked as absent + no-show on working days
        $totalAbsentDays = $absentDays + $noShowDays;

        // 2. Unpaid Leaves - Only count working days
        $unpaidLeaves = \App\Models\TimeOffRequest::where('staff_member_id', $staffMemberId)
            ->where('approval_status', 'approved')
            ->whereHas('category', function ($q) {
                $q->where('is_paid', false);
            })
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate]);
            })
            ->get();

        $unpaidLeaveDays = 0;
        foreach ($unpaidLeaves as $leave) {
            // Calculate overlap with current month
            $start = \Carbon\Carbon::parse($leave->start_date);
            $end = \Carbon\Carbon::parse($leave->end_date);

            // Clamp to current month
            if ($start->lt($startDate)) $start = $startDate->copy();
            if ($end->gt($endDate)) $end = $endDate->copy();

            // Count only working days in the leave period
            for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
                if (in_array($date->format('Y-m-d'), $workingDates)) {
                    $unpaidLeaveDays++;
                }
            }
        }

        // 3. Calculate LOP
        // Formula: Total Absents + (0.5 * HalfDay) + UnpaidLeaves
        $lopDays = $totalAbsentDays + ($halfDays * 0.5) + $unpaidLeaveDays;

        // 4. Financials
        $baseSalary = $employee->base_salary ?? 0;

        // Calculate per-day salary based on WORKING DAYS (not calendar days)
        $perDaySalary = $totalWorkingDays > 0 ? ($baseSalary / $totalWorkingDays) : 0;
        $lopAmount = round($lopDays * $perDaySalary, 2);

        // 5. Existing Benefits/Deductions
        $benefits = $this->calculateBenefits($staffMemberId, $month, $year);
        $recurringDeductions = $this->calculateDeductions($staffMemberId, $month, $year);

        // Add LOP to deductions
        $allDeductions = $recurringDeductions;
        if ($lopDays > 0) {
            $allDeductions['breakdown'][] = [
                'name' => "Loss of Pay ({$lopDays} days)",
                'amount' => $lopAmount
            ];
            $allDeductions['total'] += $lopAmount;
        }

        $totalEarnings = $baseSalary + $benefits['total'];
        $totalDeductions = $allDeductions['total'];
        $netSalary = max(0, $totalEarnings - $totalDeductions);

        return [
            'staff' => [
                'id' => $employee->id,
                'name' => $employee->full_name,
                'base_salary' => $baseSalary
            ],
            'attendance' => [
                'total_calendar_days' => $startDate->daysInMonth,
                'total_working_days' => $totalWorkingDays,
                'working_days_config' => $workingDaysArray,
                'present_days' => $presentDays,
                'absent_days' => $totalAbsentDays,
                'marked_absent_days' => $absentDays,
                'no_show_days' => $noShowDays,
                'half_days' => $halfDays,
                'late_days' => $lateDays,
                'unpaid_leave_days' => $unpaidLeaveDays,
                'lop_days' => $lopDays,
            ],
            'benefits' => $benefits,
            'deductions' => $allDeductions,
            'salary' => [
                'base_salary' => $baseSalary,
                'per_day_salary' => round($perDaySalary, 2),
                'total_earnings' => $totalEarnings,
                'total_deductions' => $totalDeductions,
                'net_salary' => $netSalary
            ]
        ];
    }

    /**
     * Bulk generate salary slips for all employees.
     */
    public function bulkGenerateSalarySlips(int $month, int $year, ?array $employeeIds = null): Collection
    {
        return DB::transaction(function () use ($month, $year, $employeeIds) {
            $query = StaffMember::active();

            if ($employeeIds) {
                $query->whereIn('id', $employeeIds);
            }

            $employees = $query->get();
            $generated = collect();

            foreach ($employees as $employee) {
                $slip = $this->generateSalarySlip($employee->id, $month, $year);
                $generated->push($slip);
            }

            return $generated;
        });
    }

    /**
     * Mark salary slip as paid.
     */
    public function markAsPaid(int $salarySlipId, array $data = []): SalarySlip
    {
        $slip = SalarySlip::findOrFail($salarySlipId);

        $slip->update([
            'status' => 'paid',
            'paid_at' => now(),
            'payment_method' => $data['payment_method'] ?? null,
            'payment_reference' => $data['payment_reference'] ?? null,
        ]);

        return $slip->fresh($this->defaultRelations);
    }

    /**
     * Bulk mark salary slips as paid.
     */
    public function bulkMarkAsPaid(array $slipIds, array $data = []): int
    {
        return SalarySlip::whereIn('id', $slipIds)->update([
            'status' => 'paid',
            'paid_at' => now(),
            'payment_method' => $data['payment_method'] ?? null,
            'payment_reference' => $data['payment_reference'] ?? null,
        ]);
    }

    /**
     * Calculate employee benefits.
     */
    public function calculateBenefits(int $staffMemberId, int $month, int $year): array
    {
        $benefits = StaffBenefit::where('staff_member_id', $staffMemberId)
            ->where('is_active', true)
            ->with('benefitType')
            ->get();

        $breakdown = [];
        $total = 0;

        foreach ($benefits as $benefit) {
            $amount = $benefit->amount;
            $breakdown[] = [
                'name' => $benefit->benefitType?->title ?? 'Benefit',
                'amount' => $amount,
            ];
            $total += $amount;
        }

        return [
            'total' => $total,
            'breakdown' => $breakdown,
        ];
    }

    /**
     * Calculate employee deductions.
     */
    public function calculateDeductions(int $staffMemberId, int $month, int $year): array
    {
        $deductions = RecurringDeduction::where('staff_member_id', $staffMemberId)
            ->where('is_active', true)
            ->with('withholdingType')
            ->get();

        $breakdown = [];
        $total = 0;

        foreach ($deductions as $deduction) {
            $amount = $deduction->amount;
            $breakdown[] = [
                'name' => $deduction->withholdingType?->title ?? 'Deduction',
                'amount' => $amount,
            ];
            $total += $amount;
        }

        return [
            'total' => $total,
            'breakdown' => $breakdown,
        ];
    }

    /**
     * Get payroll summary for a month.
     */
    public function getMonthlyPayrollSummary(int $month, int $year): array
    {
        $salaryPeriod = sprintf('%04d-%02d-01', $year, $month);
        $slips = SalarySlip::where('salary_period', $salaryPeriod)->get();

        return [
            'month' => $month,
            'year' => $year,
            'salary_period' => $salaryPeriod,
            'total_employees' => $slips->count(),
            'total_earnings' => $slips->sum('total_earnings'),
            'total_deductions' => $slips->sum('total_deductions'),
            'total_net_payable' => $slips->sum('net_payable'),
            'paid_count' => $slips->where('status', 'paid')->count(),
            'pending_count' => $slips->where('status', 'generated')->count(),
        ];
    }

    /**
     * Get employee salary history.
     */
    public function getEmployeeSalaryHistory(int $staffMemberId, int $limit = 12): Collection
    {
        return SalarySlip::where('staff_member_id', $staffMemberId)
            ->orderByDesc('salary_period')
            ->limit($limit)
            ->get();
    }

    /**
     * Get payroll statistics.
     */
    public function getStatistics(): array
    {
        $currentPeriod = now()->format('Y-m-01');
        $currentYear = now()->year;

        $monthlyData = SalarySlip::where('salary_period', $currentPeriod)->get();

        return [
            'current_month' => [
                'total_salary' => $monthlyData->sum('net_payable'),
                'employees_paid' => $monthlyData->where('status', 'paid')->count(),
                'employees_pending' => $monthlyData->where('status', 'generated')->count(),
            ],
            'year_to_date' => [
                'total_salary' => SalarySlip::where('salary_period', 'like', $currentYear . '-%')->sum('net_payable'),
                'total_slips' => SalarySlip::where('salary_period', 'like', $currentYear . '-%')->count(),
            ],
        ];
    }

    // App\Services\Payroll\PayrollService.php
    public function generateSalarySlipPdf($salarySlip)
    {
        // Make sure you have a view at resources/views/pdf/salary-slip.blade.php
        $pdf = PDF::loadView('pdf.salary-slip', [
            'slip' => $salarySlip,
            'company' => \App\Models\Company::first(),
        ]);

        // Customize PDF settings
        $pdf->setPaper('A4', 'portrait');
        $pdf->setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
        ]);

        return $pdf->output();
    }

    /**
     * Get working days configuration for an employee within a date range.
     *
     * @param StaffMember $employee
     * @param \Carbon\Carbon $startDate
     * @param \Carbon\Carbon $endDate
     * @return array
     */
    protected function getWorkingDaysConfig(StaffMember $employee, \Carbon\Carbon $startDate, \Carbon\Carbon $endDate): array
    {
        // Get organization and company IDs from employee
        $orgId = $employee->org_id;
        $companyId = $employee->company_id;

        // Try to find a working days configuration for this date range
        $workingDay = \App\Models\WorkingDay::where('org_id', $orgId)
            ->where('company_id', $companyId)
            ->where(function ($q) use ($startDate) {
                $q->whereNull('from_date')
                  ->orWhere('from_date', '<=', $startDate->format('Y-m-d'));
            })
            ->where(function ($q) use ($endDate) {
                $q->whereNull('to_date')
                  ->orWhere('to_date', '>=', $endDate->format('Y-m-d'));
            })
            ->first();

        // Default to Monday-Friday if no configuration found
        $workingDays = $workingDay ? [
            'monday' => $workingDay->monday,
            'tuesday' => $workingDay->tuesday,
            'wednesday' => $workingDay->wednesday,
            'thursday' => $workingDay->thursday,
            'friday' => $workingDay->friday,
            'saturday' => $workingDay->saturday,
            'sunday' => $workingDay->sunday,
        ] : [
            'monday' => true,
            'tuesday' => true,
            'wednesday' => true,
            'thursday' => true,
            'friday' => true,
            'saturday' => false,
            'sunday' => false,
        ];

        // Get array of working day names
        $workingDaysArray = array_keys(array_filter($workingDays));

        // Calculate working dates in the range
        $workingDates = [];
        $nonWorkingDates = [];

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $dayName = strtolower($date->format('l'));
            if (isset($workingDays[$dayName]) && $workingDays[$dayName]) {
                $workingDates[] = $date->format('Y-m-d');
            } else {
                $nonWorkingDates[] = $date->format('Y-m-d');
            }
        }

        return [
            'working_days' => $workingDaysArray,
            'total_working_days' => count($workingDates),
            'working_dates' => $workingDates,
            'non_working_dates' => $nonWorkingDates,
        ];
    }
}
