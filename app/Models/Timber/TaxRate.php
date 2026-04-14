<?php

namespace App\Models\Timber;

use App\Enums\TaxType;
use App\Traits\HasOrgAndCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaxRate extends Model
{
    use HasFactory, HasOrgAndCompany, SoftDeletes;

    protected $table = 'tax_rates';

    protected $fillable = [
        'name',
        'rate',
        'tax_type',
        'is_active',
        'org_id',
        'company_id',
    ];

    protected $casts = [
        'tax_type' => TaxType::class,
        'rate' => 'float',
        'is_active' => 'boolean',
    ];

    /**
     * Get tax groups that use this tax rate.
     */
    public function taxGroups()
    {
        return $this->belongsToMany(TaxGroup::class, 'tax_group_details');
    }

    /**
     * Scope: Active tax rates
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
