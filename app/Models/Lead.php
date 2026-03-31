<?php

namespace App\Models;

use App\Enums\Gender;
use App\Enums\QualificationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Modules\CRM\Models\CrmNote;

class Lead extends Model
{
    use HasFactory, SoftDeletes;

    protected static function booted()
    {
        static::creating(function ($lead) {
            $year = now()->year;
            $prefix = "CRM-LEAD-{$year}-";

            // Find the last lead created this year to determine the next sequence
            $lastLead = self::where('series', 'like', "{$prefix}%")
                ->orderBy('id', 'desc')
                ->first();

            if ($lastLead) {
                // Extract the sequence number
                $lastSequence = intval(substr($lastLead->series, -5));
                $nextSequence = $lastSequence + 1;
            } else {
                $nextSequence = 1;
            }

            // Generate the new series
            $lead->series = $prefix . str_pad($nextSequence, 5, '0', STR_PAD_LEFT);
        });
    }

    protected $fillable = [
        'series',
        'salutation',
        'first_name',
        'middle_name',
        'last_name',
        'job_title',
        'gender',
        'status_id',
        'source_id',
        'request_type_id',
        'email',
        'phone',
        'mobile_no',
        'website',
        'whatsapp_no',
        'city',
        'state',
        'country',
        'company_name',
        'annual_revenue',
        'no_of_employees',
        'industry_id',
        'qualification_status',
        'qualified_by',
        'qualified_on',
    ];

    protected $casts = [
        'annual_revenue' => 'decimal:2',
        'qualified_on' => 'datetime',
        'qualification_status' => QualificationStatus::class,
        'gender' => Gender::class,
    ];

    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }

    public function requestType(): BelongsTo
    {
        return $this->belongsTo(RequestType::class);
    }

    public function industry(): BelongsTo
    {
        return $this->belongsTo(IndustryType::class, 'industry_id');
    }

    public function qualifiedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'qualified_by');
    }

    public function notes(): MorphMany
    {
        return $this->morphMany(CrmNote::class, 'notable');
    }

    public function prospects(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Prospect::class, 'prospect_leads')
            ->withPivot(['lead_name', 'email', 'mobile_no', 'status'])
            ->withTimestamps();
    }

    /**
     * Get full name attribute
     */
    public function getFullNameAttribute(): string
    {
        return trim(implode(' ', array_filter([
            $this->salutation,
            $this->first_name,
            $this->middle_name,
            $this->last_name
        ])));
    }
}
