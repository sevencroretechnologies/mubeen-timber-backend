<?php

namespace App\Models;

use App\Traits\HasOrgAndCompany;
use App\Traits\StandardDateSerialization;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkLog extends Model
{
    use HasFactory, HasOrgAndCompany, StandardDateSerialization;

    protected $fillable = [
        'staff_member_id',
        'log_date',
        'status',
        'clock_in',
        'clock_out',
        'total_hours',
        'late_minutes',
        'early_leave_minutes',
        'overtime_minutes',
        'break_minutes',
        'notes',
        'clock_in_ip',
        'clock_in_latitude',
        'clock_in_longitude',
        'clock_in_accuracy',
        'clock_out_ip',
        'clock_out_latitude',
        'clock_out_longitude',
        'clock_out_accuracy',
        'tenant_id',
        'author_id',
    ];

    protected $casts = [
        'log_date' => 'datetime',
        'late_minutes' => 'integer',
        'early_leave_minutes' => 'integer',
        'overtime_minutes' => 'integer',
        'break_minutes' => 'integer',
        // 'clock_in' => 'datetime',
        // 'clock_out' => 'datetime',
    ];

    // In WorkLog model
public function getClockInFullAttribute(): ?string
{
    if (!$this->clock_in) return null;
    
    // Combine date and time properly
    return $this->log_date->format('Y-m-d') . ' ' . $this->clock_in;
}

public function getClockOutFullAttribute(): ?string
{
    if (!$this->clock_out) return null;
    
    // Combine date and time properly
    return $this->log_date->format('Y-m-d') . ' ' . $this->clock_out;
}

public function getClockInDisplayAttribute(): ?string
{
    if (!$this->clock_in) return null;
    
    // Parse the time and format it for display
    $time = Carbon::parse($this->clock_in);
    return $time->format('H:i');
}

public function getClockOutDisplayAttribute(): ?string
{
    if (!$this->clock_out) return null;
    
    // Parse the time and format it for display
    $time = Carbon::parse($this->clock_out);
    return $time->format('H:i');
}

// Append to JSON
protected $appends = ['clock_in_full', 'clock_out_full', 'clock_in_display', 'clock_out_display'];

    public function staffMember()
    {
        return $this->belongsTo(StaffMember::class);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

public function shift()
{
    return $this->hasOneThrough(
        Shift::class,
        ShiftAssignment::class,
        'staff_member_id', // Foreign key on ShiftAssignment table
        'id', // Foreign key on Shift table
        'staff_member_id', // Local key on WorkLog table
        'shift_id' // Local key on ShiftAssignment table
    )->where(function ($query) {
        $logDate = $this->log_date->format('Y-m-d');
        $query->whereDate('shift_assignments.effective_from', '<=', $logDate)
            ->where(function ($q) use ($logDate) {
                $q->whereNull('shift_assignments.effective_to')
                    ->orWhereDate('shift_assignments.effective_to', '>=', $logDate);
            });
    });
}

    /**
     * Calculate working hours.
     */
    public function getWorkingMinutesAttribute(): int
    {
        if (! $this->clock_in || ! $this->clock_out) {
            return 0;
        }

        $clockIn = Carbon::parse($this->clock_in);
        $clockOut = Carbon::parse($this->clock_out);

        return $clockOut->diffInMinutes($clockIn) - $this->break_minutes;
    }

    /**
     * Format working hours.
     */
    public function getWorkingHoursFormattedAttribute(): string
    {
        $minutes = $this->working_minutes;
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;

        return sprintf('%d:%02d', $hours, $mins);
    }

    // Scopes
    public function scopeForDate($query, $date)
    {
        return $query->where('log_date', $date);
    }

    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('log_date', [$startDate, $endDate]);
    }

    public function scopePresent($query)
    {
        return $query->whereIn('status', ['present', 'half_day']);
    }

    public function scopeAbsent($query)
    {
        return $query->where('status', 'absent');
    }
}
