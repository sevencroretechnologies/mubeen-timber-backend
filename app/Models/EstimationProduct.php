<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EstimationProduct extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'estimation_id',
        'org_id',
        'company_id',
        'product_id',
        'customer_id',
        'project_id',
        'length',
        'breadth',
        'height',
        'thickness',
        'cft_calculation_type',
        'quantity',
        'cft',
        'cost_per_cft',
        'total_amount',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'length'       => 'decimal:2',
        'breadth'      => 'decimal:2',
        'height'       => 'decimal:2',
        'thickness'    => 'decimal:2',
        'cft'          => 'decimal:2',
        'cost_per_cft' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'quantity'     => 'integer',
    ];

    /**
     * CFT Calculation Type constants for reference.
     * 1 = (length * breadth * height) / 144 (dimensions in inches)
     * 2 = length * breadth * height (dimensions in feet)
     * 3 = (length * breadth * thickness) / 12 (thickness in inches)
     * 4 = length * breadth * thickness (thickness in feet)
     * 5 = manual (user provides CFT manually)
     */
    const CFT_TYPE_LBH_INCHES_144 = '1';
    const CFT_TYPE_LBH_FEET       = '2';
    const CFT_TYPE_LBT_INCHES_12  = '3';
    const CFT_TYPE_LBT_FEET       = '4';
    const CFT_TYPE_MANUAL         = '5';

    /**
     * Calculate CFT based on the selected calculation type.
     */
    public function calculateCft(): float
    {
        $l = (float) ($this->length ?? 0);
        $b = (float) ($this->breadth ?? 0);
        $h = (float) ($this->height ?? 0);
        $t = (float) ($this->thickness ?? 0);

        $cftPerUnit = 0;

        if ($this->cft_calculation_type === '1') {
            // Type 1: in inches -> (l*b*h)/144
            $cftPerUnit = ($l * $b * $h) / 144;
        } elseif ($this->cft_calculation_type === '2') {
            // Type 2: in feet -> l*b*h
            $cftPerUnit = $l * $b * $h;
        } elseif ($this->cft_calculation_type === '3') {
            // Type 3: thickness in inches -> (l*b*t)/12
            $cftPerUnit = ($l * $b * $t) / 12;
        } elseif ($this->cft_calculation_type === '4') {
            // Type 4: thickness in feet -> l*b*t
            $cftPerUnit = $l * $b * $t;
        } elseif ($this->cft_calculation_type === '5') {
            // Type 5: Manual - use the cft value directly
            $cftPerUnit = (float) ($this->cft ?? 0);
        } else {
            // Fallback
            $cftPerUnit = ($l * $b * $h) / 144;
        }

        return round($cftPerUnit, 2);
    }

    /**
     * Calculate total amount: cft * cost_per_cft * quantity
     */
    public function calculateTotalAmount(): float
    {
        $cft        = (float) ($this->cft ?? 0);
        $costPerCft = (float) ($this->cost_per_cft ?? 0);
        $qty        = (int) ($this->quantity ?? 1);

        return $cft * $costPerCft * $qty;
    }

    // ─── Relationships ───────────────────────────────────────────────

    /**
     * Get the estimation that owns this product.
     */
    public function estimation(): BelongsTo
    {
        return $this->belongsTo(Estimation::class, 'estimation_id');
    }

    /**
     * Get the organization that owns this estimation product.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'org_id');
    }

    /**
     * Get the company that owns this estimation product.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /**
     * Get the product associated with this estimation.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Get the customer associated with this estimation.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * Get the project associated with this estimation.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }
}
