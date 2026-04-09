<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        'total_cft',
        'total_amount',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'total_cft' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    // ─── Aggregation from Items ──────────────────────────────────────

    /**
     * Recalculate total_amount by summing all item totals.
     */
    public function recalculateFromItems(): self
    {
        $this->total_cft = round(
            (float) ($this->items()->selectRaw('SUM(item_cft * quantity) as total_cft')->value('total_cft') ?? 0),
            2
        );

        $this->total_amount = round(
            $this->items()->sum('total_amount'),
            2
        );

        $this->save();

        return $this;
    }

    /**
     * Get total quantity across all items.
     */
    public function getTotalQuantityAttribute(): int
    {
        return (int) $this->items()->sum('quantity');
    }

    // ─── Relationships ───────────────────────────────────────────────

    /**
     * Get all items for this product.
     */
    public function items(): HasMany
    {
        return $this->hasMany(EstimationProductsItem::class, 'estimation_product_id');
    }

    /**
     * Get the estimation that owns this product.
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
     * Get the product reference.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Get the customer.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * Get the project.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }
}
