<?php

namespace App\Http\Controllers\Api\Settings;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Core\BaseController;
use App\Models\WorkingDay;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Traits\ApiResponse;

class WorkingDayController extends Controller
{
    use ApiResponse;
    /**
     * Get all working days configurations.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = WorkingDay::with(['organization', 'company']);

            $user = $request->user();
            if ($user->org_id) {
                $query->where('org_id', $user->org_id);
            }
            if ($user->company_id) {
                $query->where('company_id', $user->company_id);
            }

            if ($request->has('date')) {
                $query->forDate($request->date);
            }

            $query->orderBy('created_at', 'desc');

            $paginate = $request->get('paginate', 'true') === 'true';
            $perPage = $request->get('per_page', 15);

            $result = $paginate
                ? $query->paginate($perPage)
                : $query->get();

            return $this->success($result, 'Working days configurations retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to fetch working days: ' . $e->getMessage());
        }
    }

    /**
     * Get a specific working day configuration.
     */
    public function show(int $id): JsonResponse
    {
        try {
            $workingDay = WorkingDay::with(['organization', 'company'])->findOrFail($id);
            return $this->success($workingDay, 'Working day configuration retrieved successfully');
        } catch (\Exception $e) {
            return $this->notFound('Working day configuration not found');
        }
    }

    /**
     * Store a new working day configuration.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'monday' => 'boolean',
            'tuesday' => 'boolean',
            'wednesday' => 'boolean',
            'thursday' => 'boolean',
            'friday' => 'boolean',
            'saturday' => 'boolean',
            'sunday' => 'boolean',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Get org_id and company_id from authenticated user
            $user = $request->user();

            $data = $validator->validated();
            $orgId = $user->org_id;
            $companyId = $user->company_id;

            // Scenario 1: Check if there's an existing unclosed record (to_date is null)
            $existingOpenRecord = WorkingDay::where('org_id', $orgId)
                ->where('company_id', $companyId)
                ->whereNull('to_date')
                ->first();

            if ($existingOpenRecord) {
                return $this->error('There is an existing working days configuration without an end date. Please close that configuration by setting a "To Date" before creating a new one.', 422, ['existing_record' => $existingOpenRecord]);
            }

            // Scenario 2: Check for overlapping date ranges
            $fromDate = $data['from_date'] ?? null;
            $toDate = $data['to_date'] ?? null;

            $query = WorkingDay::where('org_id', $orgId)
                ->where('company_id', $companyId);

            if ($fromDate) {
                // Check for any overlap between the new date range and existing records
                // Two ranges overlap if: (StartA <= EndB) and (EndA >= StartB)
                $query->where(function ($q) use ($fromDate, $toDate) {
                    $q->where(function ($query) use ($fromDate, $toDate) {
                            // Existing record with both from_date and to_date
                            $query->whereNotNull('from_date')
                                  ->whereNotNull('to_date')
                                  ->where(function ($q) use ($fromDate, $toDate) {
                                      // Check overlap: existing range starts before or at new range ends
                                      // AND existing range ends on or after new range starts
                                      $q->where('from_date', '<=', $toDate)
                                        ->where('to_date', '>=', $fromDate);
                                  });
                        })->orWhere(function ($query) use ($fromDate, $toDate) {
                            // Existing record with no end date (to_date is null)
                            // Overlaps if the new range's from_date falls within the indefinite range
                            $query->whereNull('to_date')
                                  ->where('from_date', '<=', $fromDate);
                        });
                });
            }

            $overlappingRecord = $query->first();

            if ($overlappingRecord) {
                // ... (message building logic omitted for brevity in targetContent match but I'll keep it in Replacement)
                $message = 'This date range overlaps with an existing working days configuration. ';
                if ($overlappingRecord->from_date && $overlappingRecord->to_date) {
                    $fromDateStr = \Carbon\Carbon::parse($overlappingRecord->from_date)->format('M d, Y');
                    $toDateStr = \Carbon\Carbon::parse($overlappingRecord->to_date)->format('M d, Y');
                    $message .= "Existing configuration: {$fromDateStr} to {$toDateStr}. Please use different dates or update the existing configuration.";
                } elseif ($overlappingRecord->from_date) {
                    $fromDateStr = \Carbon\Carbon::parse($overlappingRecord->from_date)->format('M d, Y');
                    $message .= "Existing configuration starts from {$fromDateStr}. Please update that configuration first.";
                } else {
                    $message .= 'Please update the existing configuration first.';
                }

                return $this->error($message, 422, ['existing_record' => $overlappingRecord]);
            }

            $data['org_id'] = $orgId;
            $data['company_id'] = $companyId;

            $workingDay = WorkingDay::create($data);

            return $this->created($workingDay->load(['organization', 'company']), 'Working day configuration created successfully.');
        } catch (\Exception $e) {
            return $this->serverError('Failed to create working day configuration: ' . $e->getMessage());
        }
    }

    /**
     * Update a working day configuration.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'monday' => 'boolean',
            'tuesday' => 'boolean',
            'wednesday' => 'boolean',
            'thursday' => 'boolean',
            'friday' => 'boolean',
            'saturday' => 'boolean',
            'sunday' => 'boolean',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $workingDay = WorkingDay::findOrFail($id);
            $data = $validator->validated();

            // Get authenticated user for org_id and company_id
            $user = $request->user();
            $orgId = $user->org_id;
            $companyId = $user->company_id;

            // Check for overlapping date ranges with other records (excluding current record)
            $fromDate = $data['from_date'] ?? $workingDay->from_date;
            $toDate = $data['to_date'] ?? $workingDay->to_date;

            // Build query to find overlapping records
            $query = WorkingDay::where('org_id', $orgId)
                ->where('company_id', $companyId)
                ->where('id', '!=', $id); // Exclude current record

            if ($fromDate) {
                // Check for any overlap between the new date range and existing records
                // Two ranges overlap if: (StartA <= EndB) and (EndA >= StartB)
                $query->where(function ($q) use ($fromDate, $toDate) {
                    $q->where(function ($query) use ($fromDate, $toDate) {
                            // Existing record with both from_date and to_date
                            $query->whereNotNull('from_date')
                                  ->whereNotNull('to_date')
                                  ->where(function ($q) use ($fromDate, $toDate) {
                                      // Check overlap: existing range starts before or at new range ends
                                      // AND existing range ends on or after new range starts
                                      $q->where('from_date', '<=', $toDate)
                                        ->where('to_date', '>=', $fromDate);
                                  });
                        })->orWhere(function ($query) use ($fromDate, $toDate) {
                            // Existing record with no end date (to_date is null)
                            // Overlaps if the new range's from_date falls within the indefinite range
                            $query->whereNull('to_date')
                                  ->where('from_date', '<=', $fromDate);
                        });
                });
            }

            $overlappingRecord = $query->first();

            if ($overlappingRecord) {
                $message = 'This date range overlaps with an existing working days configuration. ';
                if ($overlappingRecord->from_date && $overlappingRecord->to_date) {
                    $fromDateStr = \Carbon\Carbon::parse($overlappingRecord->from_date)->format('M d, Y');
                    $toDateStr = \Carbon\Carbon::parse($overlappingRecord->to_date)->format('M d, Y');
                    $message .= "Existing configuration: {$fromDateStr} to {$toDateStr}.";
                } elseif ($overlappingRecord->from_date) {
                    $fromDateStr = \Carbon\Carbon::parse($overlappingRecord->from_date)->format('M d, Y');
                    $message .= "Existing configuration starts from {$fromDateStr} (no end date).";
                }

                return $this->error($message, 422, ['existing_record' => $overlappingRecord]);
            }

            $workingDay->update($data);

            return $this->success($workingDay->load(['organization', 'company']), 'Working day configuration updated successfully.');
        } catch (\Exception $e) {
            return $this->serverError('Failed to update working day configuration: ' . $e->getMessage());
        }
    }

    /**
     * Delete a working day configuration.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $workingDay = WorkingDay::findOrFail($id);
            $workingDay->delete();

            return $this->noContent('Working day configuration deleted successfully.');
        } catch (\Exception $e) {
            return $this->serverError('Failed to delete working day configuration: ' . $e->getMessage());
        }
    }

    /**
     * Get working days count for a specific date range.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getWorkingDaysCount(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $startDate = \Carbon\Carbon::parse($request->start_date);
            $endDate = \Carbon\Carbon::parse($request->end_date);

            $user = $request->user();
            $query = WorkingDay::query();

            if ($user->org_id) {
                $query->where('org_id', $user->org_id);
            }
            if ($user->company_id) {
                $query->where('company_id', $user->company_id);
            }

            $workingDaysConfig = $query->get();

            if ($workingDaysConfig->isEmpty()) {
                $workingDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
            } else {
                $config = $workingDaysConfig->first();
                $workingDays = array_keys(array_filter($config->getWorkingDaysArray()));
            }

            $workingDaysCount = 0;
            $totalDays = $startDate->diffInDays($endDate) + 1;
            $workingDates = [];

            for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
                $dayName = strtolower($date->format('l'));
                if (in_array($dayName, $workingDays)) {
                    $workingDaysCount++;
                    $workingDates[] = $date->format('Y-m-d');
                }
            }

            return $this->success([
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'total_days' => $totalDays,
                'working_days_count' => $workingDaysCount,
                'working_days' => $workingDays,
                'working_dates' => $workingDates,
            ], 'Working days calculated successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to calculate working days: ' . $e->getMessage());
        }
    }

    /**
     * Get active working days configuration for a specific date.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getActiveConfig(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $query = WorkingDay::forDate($request->date);

            $user = $request->user();

            if ($user->org_id) {
                $query->where('org_id', $user->org_id);
            }

            if ($user->company_id) {
                $query->where('company_id', $user->company_id);
            }

            $workingDay = $query->first();

            if (!$workingDay) {
                return $this->success([
                    'monday' => true,
                    'tuesday' => true,
                    'wednesday' => true,
                    'thursday' => true,
                    'friday' => true,
                    'saturday' => false,
                    'sunday' => false,
                    'from_date' => null,
                    'to_date' => null,
                    'is_default' => true,
                ], 'Default working days configuration retrieved');
            }

            return $this->success($workingDay->append('is_default'), 'Active working days configuration retrieved');
        } catch (\Exception $e) {
            return $this->serverError('Failed to fetch working days configuration: ' . $e->getMessage());
        }
    }
}
