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
        'estimation_id',
        'product_id',
        'customer_id',
        'project_id',
        'length',
        'breadth',
        'height',
        'thickness',
        'quantity',
        'item_cft',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'length'    => 'decimal:2',
        'breadth'   => 'decimal:2',
        'height'    => 'decimal:2',
        'thickness' => 'decimal:2',
        'item_cft'  => 'decimal:2',
        'quantity'  => 'integer',
    ];

    // ─── Relationships ────────────────────────────────────────────────

    /**
     * Get the estimation that owns this item.
     */
    public function estimation(): BelongsTo
    {
        return $this->belongsTo(Estimation::class, 'estimation_id');
    }

    /**
     * Get the organization that owns this item.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'org_id');
    }

    /**
     * Get the company that owns this item.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /**
     * Get the product associated with this item.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Get the customer associated with this item.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * Get the project associated with this item.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }
}
