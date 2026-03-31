<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use App\Modules\CRM\Models\CrmNote;

class Prospect extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_name',
        'industry',
        'market_segment',
        'customer_group',
        'territory',
        'no_of_employees',
        'annual_revenue',
        'fax',
        'website',
        'prospect_owner_id',
        'company',
        'status',
        'source',
        'address',
        'city',
        'state',
        'country',
        'zip_code',
        'email',
        'phone',
    ];

    protected $casts = [
        'annual_revenue' => 'decimal:2',
    ];

    public function prospectOwner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prospect_owner_id');
    }

    public function leads(): BelongsToMany
    {
        return $this->belongsToMany(Lead::class, 'prospect_leads')
            ->withPivot(['lead_name', 'email', 'mobile_no', 'status'])
            ->withTimestamps();
    }

    public function opportunities(): BelongsToMany
    {
        return $this->belongsToMany(Opportunity::class, 'prospect_opportunities')
            ->withPivot(['amount', 'stage', 'deal_owner', 'probability', 'expected_closing', 'currency', 'contact_person'])
            ->withTimestamps();
    }

    public function notes(): MorphMany
    {
        return $this->morphMany(CrmNote::class, 'notable');
    }
}
