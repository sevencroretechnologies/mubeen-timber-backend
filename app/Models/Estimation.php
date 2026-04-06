<?php

namespace App\Models;

use App\Enums\EstimationStatus;
use Illuminate\Database\Eloquent\Model;

class Estimation extends Model
{
    protected $fillable = [
        'customer_id',
        'org_id',
        'company_id',
        'product_id',
        'description',
        'status'
    ];

    protected $casts = [
        'status' => EstimationStatus::class,
    ];

    public function product()
    {
        return $this->belongsTo(\App\Models\Product::class);
    }

    public function customer()
    {
        return $this->belongsTo(\App\Models\Customer::class);
    }

    public function collections()
    {
        return $this->hasMany(\App\Models\EstimationCollection::class);
    }

    /**
     * Get total collected CFT for this estimation
     */
    public function getTotalCollectedCftAttribute(): float
    {
        return (float) $this->collections()->sum('quantity_cft');
    }

    /**
     * Get remaining CFT to be collected
     */
    public function getRemainingCftAttribute(): float
    {
        return max(0, (float) $this->cft - $this->total_collected_cft);
    }

    /**
     * Check if estimation can be approved
     */
    public function canBeApproved(): bool
    {
        return $this->status?->canBeApproved() ?? false;
    }

    /**
     * Check if material can be collected
     */
    public function canCollectMaterial(): bool
    {
        return $this->status?->canCollectMaterial() ?? false;
    }

    /**
     * Approve the estimation
     */
    public function approve(): void
    {
        if ($this->canBeApproved()) {
            $this->update(['status' => EstimationStatus::Approved]);
        }
    }

    /**
     * Cancel the estimation
     */
    public function cancel(): void
    {
        if ($this->status?->canBeCancelled()) {
            $this->update(['status' => EstimationStatus::Cancelled]);
        }
    }

    /**
     * Mark as collected
     */
    public function markAsCollected(): void
    {
        if ($this->status?->canCollectMaterial()) {
            $this->update(['status' => EstimationStatus::Collected]);
        }
    }

    /**
     * Update status based on collection progress
     */
    public function updateStatusBasedOnCollection(): void
    {
        if ($this->status === EstimationStatus::Approved && $this->collections()->exists()) {
            $this->update(['status' => EstimationStatus::PartiallyCollected]);
        }
    }
}
