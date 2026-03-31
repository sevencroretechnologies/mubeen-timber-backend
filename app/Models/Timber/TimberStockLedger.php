<?php

namespace App\Models\Timber;

use App\Traits\HasOrgAndCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TimberStockLedger extends Model
{
    use HasFactory, HasOrgAndCompany;

    protected $table = 'timber_stock_ledger';

    protected $fillable = [
        'wood_type_id',
        'warehouse_id',
        'current_quantity',
        'reserved_quantity',
        'minimum_threshold',
        'maximum_threshold',
        'last_restocked_at',
        'company_id',
        'org_id',
    ];

    protected $casts = [
        'current_quantity' => 'decimal:3',
        'reserved_quantity' => 'decimal:3',
        'minimum_threshold' => 'decimal:3',
        'maximum_threshold' => 'decimal:3',
        'last_restocked_at' => 'datetime',
    ];

    protected $appends = ['available_quantity', 'stock_status'];

    public function woodType()
    {
        return $this->belongsTo(TimberWoodType::class, 'wood_type_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(TimberWarehouse::class, 'warehouse_id');
    }

    public function movements()
    {
        return $this->hasMany(TimberStockMovement::class, 'stock_ledger_id');
    }

    public function alerts()
    {
        return $this->hasMany(TimberStockAlert::class, 'stock_ledger_id');
    }

    public function getAvailableQuantityAttribute(): float
    {
        return (float) $this->current_quantity - (float) $this->reserved_quantity;
    }

    public function getStockStatusAttribute(): string
    {
        if ((float) $this->current_quantity <= 0) {
            return 'out';
        }
        if ((float) $this->minimum_threshold > 0 && (float) $this->current_quantity <= (float) $this->minimum_threshold) {
            return 'low';
        }
        return 'ok';
    }

    public function isLowStock(): bool
    {
        return (float) $this->minimum_threshold > 0
            && (float) $this->current_quantity <= (float) $this->minimum_threshold;
    }
}
