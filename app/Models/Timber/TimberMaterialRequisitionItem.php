<?php

namespace App\Models\Timber;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TimberMaterialRequisitionItem extends Model
{
    use HasFactory;

    protected $table = 'timber_material_requisition_items';

    protected $fillable = [
        'requisition_id',
        'wood_type_id',
        'requested_quantity',
        'approved_quantity',
        'issued_quantity',
        'returned_quantity',
        'unit',
        'notes',
    ];

    protected $casts = [
        'requested_quantity' => 'decimal:3',
        'approved_quantity' => 'decimal:3',
        'issued_quantity' => 'decimal:3',
        'returned_quantity' => 'decimal:3',
    ];

    public function requisition()
    {
        return $this->belongsTo(TimberMaterialRequisition::class, 'requisition_id');
    }

    public function woodType()
    {
        return $this->belongsTo(TimberWoodType::class, 'wood_type_id');
    }
}
