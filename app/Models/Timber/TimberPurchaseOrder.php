<?php

namespace App\Models\Timber;

use App\Models\User;
use App\Models\Timber\PoItemReceived;
use App\Traits\HasOrgAndCompany;
use App\Enums\PurchaseOrderStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TimberPurchaseOrder extends Model
{
    use HasFactory, HasOrgAndCompany, SoftDeletes;

    protected $table = 'timber_purchase_orders';

    protected $fillable = [
        'po_code',
        'supplier_id',
        'warehouse_id',
        'order_date',
        'expected_delivery_date',
        'subtotal',
        'tax_percentage',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'status',
        'notes',
        'terms',
        'company_id',
        'org_id',
        'created_by',
    ];

    protected $casts = [
        'order_date' => 'date',
        'expected_delivery_date' => 'date',
        'subtotal' => 'decimal:2',
        'status' => PurchaseOrderStatus::class,
        'tax_percentage' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function supplier()
    {
        return $this->belongsTo(TimberSupplier::class, 'supplier_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(TimberWarehouse::class, 'warehouse_id');
    }

    public function items()
    {
        return $this->hasMany(TimberPurchaseOrderItem::class, 'purchase_order_id');
    }

    public function receivedItems()
    {
        return $this->hasMany(PoItemReceived::class, 'purchase_order_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function generateCode(): string
    {
        $prefix = 'PO';
        $year = now()->format('Y');
        $count = static::withTrashed()->whereYear('created_at', $year)->count() + 1;
        return $prefix . '-' . $year . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);
    }

    public function calculateTotals(): void
    {
        $subtotal = $this->items()->sum('total_price');
        $taxAmount = $subtotal * ((float) $this->tax_percentage / 100);
        $totalAmount = $subtotal + $taxAmount - (float) $this->discount_amount;

        $this->update([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
        ]);
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, [
            PurchaseOrderStatus::DRAFT,
            PurchaseOrderStatus::ORDERED,
            PurchaseOrderStatus::PARTIAL_RECEIVED,
        ]);
    }
}
