<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OpportunityItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'opportunity_id',
        'item_code',
        'item_name',
        'qty',
        'rate',
        'amount',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'rate' => 'decimal:2',
        'amount' => 'decimal:2',
        'base_rate' => 'decimal:2',
        'base_amount' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function (OpportunityItem $item) {
            $item->amount = $item->rate * $item->qty;
            $opportunity = $item->opportunity;
            if ($opportunity) {
                $item->base_rate = $opportunity->conversion_rate * $item->rate;
                $item->base_amount = $opportunity->conversion_rate * $item->amount;
            }
        });
    }

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }
}
