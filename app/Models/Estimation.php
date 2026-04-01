<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Estimation extends Model
{
    protected $fillable = [
        'product_id',
        'estimation_type',
        'cft',
        'cost_per_cft',
        'labor_charges',
        'total_amount'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
