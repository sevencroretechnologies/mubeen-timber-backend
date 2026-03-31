<?php

namespace App\Models\Timber;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TimberPurchaseOrderItem extends Model
{
    use HasFactory;

    protected $table = 'timber_purchase_order_items';

    protected $fillable = [
        'purchase_order_id',
        'wood_type_id',
        'quantity',
        'received_quantity',
        'unit',
        'unit_price',
        'total_price',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'received_quantity' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(TimberPurchaseOrder::class, 'purchase_order_id');
    }

    public function woodType()
    {
        return $this->belongsTo(TimberWoodType::class, 'wood_type_id');
    }

    public function isFullyReceived(): bool
    {
        return (float) $this->received_quantity >= (float) $this->quantity;
    }

    public function pendingQuantity(): float
    {
        return max(0, (float) $this->quantity - (float) $this->received_quantity);
    }
}
