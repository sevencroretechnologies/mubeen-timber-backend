<?php

namespace App\Models\Timber;

use App\Models\User;
use App\Traits\HasOrgAndCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TimberStockAlert extends Model
{
    use HasFactory, HasOrgAndCompany;

    public $timestamps = false;

    protected $table = 'timber_stock_alerts';

    protected $fillable = [
        'wood_type_id',
        'warehouse_id',
        'stock_ledger_id',
        'current_quantity',
        'threshold',
        'alert_type',
        'is_resolved',
        'resolved_at',
        'resolved_by',
        'company_id',
        'org_id',
    ];

    protected $casts = [
        'current_quantity' => 'decimal:3',
        'threshold' => 'decimal:3',
        'is_resolved' => 'boolean',
        'resolved_at' => 'datetime',
    ];

    public function woodType()
    {
        return $this->belongsTo(TimberWoodType::class, 'wood_type_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(TimberWarehouse::class, 'warehouse_id');
    }

    public function stockLedger()
    {
        return $this->belongsTo(TimberStockLedger::class, 'stock_ledger_id');
    }

    public function resolvedBy()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function scopeUnresolved($query)
    {
        return $query->where('is_resolved', false);
    }
}
