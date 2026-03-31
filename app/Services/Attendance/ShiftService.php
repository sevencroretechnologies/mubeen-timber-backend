<?php

namespace App\Services\Attendance;

use App\Models\Shift;
use App\Models\ShiftAssignment;
use App\Services\Core\BaseService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Shift Service
 *
 * Handles all business logic for shift management.
 */
class ShiftService extends BaseService
{
    protected string $modelClass = Shift::class;

    protected array $defaultRelations = [];

    protected array $searchableFields = [
        'name',
    ];

    /**
     * Get all shifts.
     */
    public function getAll(array $params = []): LengthAwarePaginator|Collection
    {
        $query = $this->query();

        if (! empty($params['search'])) {
            $query = $this->applySearch($query, $params['search']);
        }

        $query = $this->applyOrdering($query, $params);

        $paginate = ($params['paginate'] ?? 'true') !== 'false';
        $perPage = $params['per_page'] ?? $this->perPage;

        return $paginate
            ? $query->paginate($perPage)
            : $query->get();
    }

    /**
     * Create a new shift.
     */
    public function create(array $data): Shift
    {
        return Shift::create($data);
    }

    /**
     * Update a shift.
     */
    public function update(int|Model $shift, array $data): Model
    {
        if (is_int($shift)) {
            $shift = $this->findOrFail($shift);
        }

        $shift->update($data);

        return $shift->fresh();
    }

    /**
     * Delete a shift.
     */   
     public function delete(Model|int $model): bool
    {
        if (is_int($model)) {
            $model = $this->findOrFail($model);
        }

        return $model->delete();
    }

    /**
     * Assign shift to employees.
     */
    public function assignToEmployees(int|Shift $shift, array $employeeIds, array $data = []): Collection
    {
        if (is_int($shift)) {
            $shift = $this->findOrFail($shift);
        }

        return DB::transaction(function () use ($shift, $employeeIds, $data) {
            $assignments = collect();
            foreach ($employeeIds as $employeeId) {
                $assignment = ShiftAssignment::updateOrCreate(
                    [
                        'shift_id' => $shift->id,
                        'staff_member_id' => $employeeId,
                    ],
                    [
                        'effective_from' => $data['effective_from'] ?? now(),
                        'effective_to' => $data['effective_to'] ?? null,
                    ]
                );
                $assignments->push($assignment);
            }

            return $assignments;
        });
    }

    /**
     * Get shifts for dropdown.
     * Updated to match BaseService method signature
     */
    public function getForDropdown(array $params = [], array $fields = ['id', 'name', 'start_time', 'end_time']): Collection
    {
        $query = $this->query();
        
        // Apply search if provided
        if (!empty($params['search'])) {
            $query = $this->applySearch($query, $params['search']);
        }
        
        // Use the provided fields or default ones
        $query->select($fields);
        
        // Order by name as specified
        $query->orderBy('name');
        
        return $query->get();
    }

    public function getEmployeeShift(int $staffMemberId, string $date = null): ?Shift
    {
        $date = $date ?? now()->toDateString();
        
        $assignment = ShiftAssignment::where('staff_member_id', $staffMemberId)
            ->where('effective_from', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', $date);
            })
            ->first();

        return $assignment ? $assignment->shift : null;
    }

    /**
     * Get shift schedule for date range.
     */
    public function getEmployeeShiftSchedule(int $staffMemberId, string $startDate, string $endDate): Collection
    {
        return ShiftAssignment::with('shift')
            ->where('staff_member_id', $staffMemberId)
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('effective_from', [$startDate, $endDate])
                    ->orWhereBetween('effective_to', [$startDate, $endDate])
                    ->orWhere(function ($q2) use ($startDate, $endDate) {
                        $q2->where('effective_from', '<=', $startDate)
                            ->where(function ($q3) use ($endDate) {
                                $q3->whereNull('effective_to')
                                    ->orWhere('effective_to', '>=', $endDate);
                            });
                    });
            })
            ->orderBy('effective_from')
            ->get();
    }
}