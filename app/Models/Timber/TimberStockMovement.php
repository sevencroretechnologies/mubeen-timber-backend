<?php

namespace App\Models\Timber;

use App\Enums\StockMovementReferenceType;
use App\Models\User;
use App\Traits\HasOrgAndCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TimberStockMovement extends Model
{
    use HasFactory, HasOrgAndCompany;

    public $timestamps = false;

    protected $table = 'timber_stock_movements';

    protected $fillable = [
        'stock_ledger_id',
        'wood_type_id',
        'warehouse_id',
        'movement_type',
        'quantity',
        'unit',
        'reference_type',
        'reference_id',
        'unit_cost',
        'total_cost',
        'before_quantity',
        'after_quantity',
        'notes',
        'movement_date',
        'company_id',
        'org_id',
        'created_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'before_quantity' => 'decimal:3',
        'after_quantity' => 'decimal:3',
        'movement_date' => 'date',
        'reference_type' => StockMovementReferenceType::class,
    ];

    public function stockLedger()
    {
        return $this->belongsTo(TimberStockLedger::class, 'stock_ledger_id');
    }

    public function woodType()
    {
        return $this->belongsTo(TimberWoodType::class, 'wood_type_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(TimberWarehouse::class, 'warehouse_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
