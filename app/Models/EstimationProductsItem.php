<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EstimationProductsItem extends Model
{
    use SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'estimation_products_item';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'org_id',
        'company_id',
        'estimation_product_id',
        'estimation_id',
        'product_id',
        'length',
        'breadth',
        'height',
        'thickness',
        'unit_type',
        'quantity',
        'rate',
        'item_cft',
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
        'item_cft'     => 'decimal:2',
        'rate'         => 'decimal:2',
        'total_amount' => 'decimal:2',
        'quantity'     => 'integer',
    ];

    /**
     * Unit type / CFT calculation constants.
     * 1 = (L × B × H) / 144  (inches)
     * 2 = L × B × H           (feet)
     * 3 = (L × B × T) / 12    (thickness in inches)
     * 4 = L × B × T           (thickness in feet)
     * 5 = manual
     */
    const UNIT_TYPE_LBH_INCHES_144 = '1';
    const UNIT_TYPE_LBH_FEET       = '2';
    const UNIT_TYPE_LBT_INCHES_12  = '3';
    const UNIT_TYPE_LBT_FEET       = '4';
    const UNIT_TYPE_MANUAL         = '5';

    // ─── CFT Calculation ─────────────────────────────────────────────

    /**
     * Calculate CFT per unit based on the selected unit type.
     */
    public function calculateCft(): float
    {
        $l = (float) ($this->length ?? 0);
        $b = (float) ($this->breadth ?? 0);
        $h = (float) ($this->height ?? 0);
        $t = (float) ($this->thickness ?? 0);

        $cftPerUnit = match ($this->unit_type) {
            '1' => ($l * $b * $h) / 144,
            '2' => $l * $b * $h,
            '3' => ($l * $b * $t) / 12,
            '4' => $l * $b * $t,
            '5' => (float) ($this->item_cft ?? 0),   // manual
            default => ($l * $b * $h) / 144,
        };

        return round($cftPerUnit, 2);
    }

    /**
     * Calculate total amount: item_cft × rate × quantity
     */
    public function calculateTotalAmount(): float
    {
        $cft  = (float) ($this->item_cft ?? 0);
        $rate = (float) ($this->rate ?? 0);
        $qty  = (int) ($this->quantity ?? 1);

        return round($cft * $rate * $qty, 2);
    }

    /**
     * Auto-calculate CFT and total_amount, then save.
     */
    public function performCalculations(): self
    {
        if ($this->unit_type !== self::UNIT_TYPE_MANUAL) {
            $this->item_cft = $this->calculateCft();
        }
        $this->total_amount = $this->calculateTotalAmount();

        return $this;
    }

    // ─── Relationships ───────────────────────────────────────────────

    /**
     * Get the parent estimation product.
     */
    public function estimationProduct(): BelongsTo
    {
        return $this->belongsTo(EstimationProduct::class, 'estimation_product_id');
    }

    /**
     * Get the estimation (denormalized).
     */
    public function estimation(): BelongsTo
    {
        return $this->belongsTo(Estimation::class, 'estimation_id');
    }

    /**
     * Get the organization.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'org_id');
    }

    /**
     * Get the company.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /**
     * Get the product.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
