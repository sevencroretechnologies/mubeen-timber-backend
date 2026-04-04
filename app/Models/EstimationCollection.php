<?php

namespace App\Models;

use App\Enums\StockMovementReferenceType;
use App\Models\Timber\TimberStockLedger;
use App\Models\Timber\TimberStockMovement;
use App\Models\Timber\TimberWarehouse;
use App\Models\Timber\TimberWoodType;
use Illuminate\Database\Eloquent\Model;

class EstimationCollection extends Model
{
    protected $fillable = [
        'estimation_id',
        'wood_type_id',
        'warehouse_id',
        'quantity_cft',
        'notes',
        'collected_at',
        'collected_by',
    ];

    protected $casts = [
        'quantity_cft' => 'decimal:3',
        'collected_at' => 'datetime',
    ];

    public function estimation()
    {
        return $this->belongsTo(Estimation::class);
    }

    public function woodType()
    {
        return $this->belongsTo(TimberWoodType::class, 'wood_type_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(TimberWarehouse::class, 'warehouse_id');
    }

    public function collectedBy()
    {
        return $this->belongsTo(User::class, 'collected_by');
    }

    /**
     * Create stock movement when material is collected
     */
    public function createStockMovement(): void
    {
        // Find the stock ledger for this wood type and warehouse
        $stockLedger = TimberStockLedger::where('wood_type_id', $this->wood_type_id)
            ->where('warehouse_id', $this->warehouse_id)
            ->first();

        if (!$stockLedger) {
            // Create stock ledger entry if it doesn't exist
            $stockLedger = TimberStockLedger::create([
                'wood_type_id' => $this->wood_type_id,
                'warehouse_id' => $this->warehouse_id,
                'current_quantity' => 0,
                'reserved_quantity' => 0,
                'minimum_threshold' => 0,
                'maximum_threshold' => 0,
            ]);
        }

        $beforeQuantity = $stockLedger->current_quantity;
        $afterQuantity = $stockLedger->current_quantity - $this->quantity_cft;

        // Create stock movement record
        TimberStockMovement::create([
            'stock_ledger_id' => $stockLedger->id,
            'wood_type_id' => $this->wood_type_id,
            'warehouse_id' => $this->warehouse_id,
            'movement_type' => 'OUT',
            'quantity' => $this->quantity_cft,
            'unit' => 'CFT',
            'reference_type' => StockMovementReferenceType::EstimationCollection->value,
            'reference_id' => $this->id,
            'before_quantity' => $beforeQuantity,
            'after_quantity' => $afterQuantity,
            'notes' => 'Material collected for estimation #' . $this->estimation_id,
            'movement_date' => now()->toDateString(),
            'created_by' => $this->collected_by,
        ]);

        // Update stock ledger
        $stockLedger->update([
            'current_quantity' => $afterQuantity,
        ]);
    }
}
