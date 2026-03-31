<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Modules\CRM\Models\CrmNote;

class Opportunity extends Model
{
    use HasFactory, SoftDeletes;

    protected static function booted()
    {
        static::creating(function ($opportunity) {
            $year = now()->year;
            $prefix = "CRM-OPP-{$year}-";

            // Find the last opportunity created this year to determine the next sequence
            $lastOpportunity = self::where('naming_series', 'like', "{$prefix}%")
                ->orderBy('id', 'desc')
                ->first();

            if ($lastOpportunity) {
                // Extract the sequence number
                $lastSequence = intval(substr($lastOpportunity->naming_series, -5));
                $nextSequence = $lastSequence + 1;
            } else {
                $nextSequence = 1;
            }

            // Generate the new series
            $opportunity->naming_series = $prefix . str_pad($nextSequence, 5, '0', STR_PAD_LEFT);
        });
    }

    protected $fillable = [
        'naming_series',
        'opportunity_type_id',
        'opportunity_stage_id',
        'opportunity_from',
        'lead_id',
        'source_id',
        'expected_closing',
        'party_name',
        'opportunity_owner',
        'probability',
        'status_id',
        'company_name',
        'industry_id',
        'no_of_employees',
        'city',
        'state',
        'country',
        'annual_revenue',
        'market_segment',
        'currency',
        'opportunity_amount',
        'with_items',
        'name',
        'territory_id',
        'contact_person',
        'contact_email',
        'contact_mobile',
        'to_discuss',
        'next_contact_by',
        'next_contact_date',
        'customer_id',
        'customer_contact_id',
        'prospect_id',
    ];

    protected $casts = [
        'annual_revenue' => 'decimal:2',
        'opportunity_amount' => 'decimal:2',
        'probability' => 'decimal:2',
        'expected_closing' => 'date:Y-m-d',
        'next_contact_date' => 'date:Y-m-d',
        'with_items' => 'boolean',
    ];

    public function opportunityType(): BelongsTo
    {
        return $this->belongsTo(OpportunityType::class);
    }

    public function opportunityStage(): BelongsTo
    {
        return $this->belongsTo(OpportunityStage::class);
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'customer_contact_id');
    }

    public function prospect(): BelongsTo
    {
        return $this->belongsTo(Prospect::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }

    public function industry(): BelongsTo
    {
        return $this->belongsTo(IndustryType::class, 'industry_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opportunity_owner');
    }

    public function lostReasons(): HasMany
    {
        return $this->hasMany(OpportunityLostReason::class);
    }

    public function competitors(): BelongsToMany
    {
        return $this->belongsToMany(Competitor::class, 'competitor_details');
    }

    public function notes(): MorphMany
    {
        return $this->morphMany(CrmNote::class, 'notable');
    }

    public function items(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(OpportunityProduct::class);
    }
}
