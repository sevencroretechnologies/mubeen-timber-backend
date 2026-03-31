<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkingDay extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'org_id',
        'company_id',
        'monday',
        'tuesday',
        'wednesday',
        'thursday',
        'friday',
        'saturday',
        'sunday',
        'from_date',
        'to_date',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'monday' => 'boolean',
        'tuesday' => 'boolean',
        'wednesday' => 'boolean',
        'thursday' => 'boolean',
        'friday' => 'boolean',
        'saturday' => 'boolean',
        'sunday' => 'boolean',
        'from_date' => 'date',
        'to_date' => 'date',
    ];

    /**
     * Get the organization that owns the working days.
     */
    public function organization()
    {
        return $this->belongsTo(Organization::class, 'org_id');
    }

    /**
     * Get the company that owns the working days.
     */
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /**
     * Get the working days as an array.
     *
     * @return array
     */
    public function getWorkingDaysArray(): array
    {
        return [
            'monday' => $this->monday,
            'tuesday' => $this->tuesday,
            'wednesday' => $this->wednesday,
            'thursday' => $this->thursday,
            'friday' => $this->friday,
            'saturday' => $this->saturday,
            'sunday' => $this->sunday,
        ];
    }

    /**
     * Get the count of working days in a week.
     *
     * @return int
     */
    public function getWorkingDaysCount(): int
    {
        return collect([
            $this->monday,
            $this->tuesday,
            $this->wednesday,
            $this->thursday,
            $this->friday,
            $this->saturday,
            $this->sunday,
        ])->filter()->count();
    }

    /**
     * Check if a specific date is a working day.
     *
     * @param \Carbon\Carbon $date
     * @return bool
     */
    public function isWorkingDay(\Carbon\Carbon $date): bool
    {
        $day = strtolower($date->format('l'));

        // Check if the date is within the range
        if ($this->from_date && $date->lt($this->from_date)) {
            return false;
        }

        if ($this->to_date && $date->gt($this->to_date)) {
            return false;
        }

        // Check if the day is enabled
        return $this->$day ?? false;
    }

    /**
     * Scope to get active working days.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query;
    }

    /**
     * Scope to get working days for a specific date.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string|\Carbon\Carbon $date
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForDate($query, $date)
    {
        $date = $date instanceof \Carbon\Carbon ? $date : \Carbon\Carbon::parse($date);

        return $query->where(function ($q) use ($date) {
            $q->whereNull('from_date')
              ->orWhere('from_date', '<=', $date->format('Y-m-d'));
        })->where(function ($q) use ($date) {
            $q->whereNull('to_date')
              ->orWhere('to_date', '>=', $date->format('Y-m-d'));
        });
    }

    /**
     * Scope for organization.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $orgId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForOrganization($query, int $orgId)
    {
        return $query->where('org_id', $orgId);
    }

    /**
     * Scope for company.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $companyId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}
