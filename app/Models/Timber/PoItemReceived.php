<?php

namespace App\Models\Timber;

use App\Traits\HasOrgAndCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PoItemReceived extends Model
{
    use HasFactory, HasOrgAndCompany, SoftDeletes;

    protected $table = 'po_items_received';

    protected $fillable = [
        'purchase_order_id',
        'warehouse_id',
        'wood_type_id',
        'received_quantity',
        'received_date',
        'total_amount',
        'company_id',
        'org_id',
    ];

    protected $casts = [
        'received_quantity' => 'decimal:3',
        'total_amount' => 'decimal:2',
        'received_date' => 'date',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(TimberPurchaseOrder::class, 'purchase_order_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(TimberWarehouse::class, 'warehouse_id');
    }

    public function woodType()
    {
        return $this->belongsTo(TimberWoodType::class, 'wood_type_id');
    }
}
