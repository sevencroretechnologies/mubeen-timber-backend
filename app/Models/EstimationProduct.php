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
     * 1 = length * breadth * height / 1728
     * 2 = length * breadth * thickness / 144
     * 3 = length * breadth * height (direct cubic)
     * 4 = custom (user provides CFT manually but auto-calculates total)
     * 5 = manual (user provides everything manually)
     */
    const CFT_TYPE_LBH_1728   = '1';
    const CFT_TYPE_LBT_144    = '2';
    const CFT_TYPE_LBH_DIRECT = '3';
    const CFT_TYPE_CUSTOM     = '4';
    const CFT_TYPE_MANUAL     = '5';

    /**
     * Calculate CFT based on the selected calculation type.
     */
    public function calculateCft(): float
    {
        $l = (float) ($this->length ?? 0);
        $b = (float) ($this->breadth ?? 0);
        $h = (float) ($this->height ?? 0);
        $t = (float) ($this->thickness ?? 0);

        return match ($this->cft_calculation_type) {
            self::CFT_TYPE_LBH_1728   => ($l * $b * $h) / 1728,
            self::CFT_TYPE_LBT_144    => ($l * $b * $t) / 144,
            self::CFT_TYPE_LBH_DIRECT => $l * $b * $h,
            self::CFT_TYPE_CUSTOM     => (float) ($this->cft ?? 0), // User-provided CFT
            self::CFT_TYPE_MANUAL     => (float) ($this->cft ?? 0), // User-provided CFT
            default                   => ($l * $b * $h) / 1728,
        };
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
