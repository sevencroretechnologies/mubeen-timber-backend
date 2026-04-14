<?php

namespace App\Models\Timber;

use App\Traits\HasOrgAndCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaxGroup extends Model
{
    use HasFactory, HasOrgAndCompany, SoftDeletes;

    protected $table = 'tax_groups';

    protected $fillable = [
        'name',
        'code',
        'total_rate',
        'is_active',
        'org_id',
        'company_id',
    ];

    protected $casts = [
        'total_rate' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    protected $appends = ['tax_rate_ids'];

    /**
     * Relationship: Tax group details (one-to-many)
     */
    public function details()
    {
        return $this->hasMany(TaxGroupDetail::class);
    }

    /**
     * Relationship: Tax rates through tax_group_details (many-to-many)
     */
    public function taxRates()
    {
        return $this->belongsToMany(TaxRate::class, 'tax_group_details', 'tax_group_id', 'tax_rate_id');
    }

    /**
     * Accessor: Get tax rate IDs array for API responses
     */
    public function getTaxRateIdsAttribute(): array
    {
        if ($this->relationLoaded('taxRates')) {
            return $this->taxRates->pluck('id')->toArray();
        }

        return $this->details()->pluck('tax_rate_id')->toArray();
    }

    /**
     * Scope: Active tax groups
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
