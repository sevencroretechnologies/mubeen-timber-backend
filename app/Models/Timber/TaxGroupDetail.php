<?php

namespace App\Models\Timber;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaxGroupDetail extends Model
{
    use HasFactory;

    protected $table = 'tax_group_details';

    protected $fillable = [
        'tax_group_id',
        'tax_rate_id',
        'org_id',
        'company_id',
    ];

    /**
     * Relationship: Tax group
     */
    public function taxGroup()
    {
        return $this->belongsTo(TaxGroup::class);
    }

    /**
     * Relationship: Tax rate
     */
    public function taxRate()
    {
        return $this->belongsTo(TaxRate::class);
    }
}
