<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Estimation extends Model
{
    protected $fillable = [
        'customer_id',
        'project_id',
        'product_id',
        'estimation_type',
        'length',
        'breadth',
        'height',
        'thickness',
        'quantity',
        'cft',
        'cost_per_cft',
        'labor_charges',
        'total_amount'
    ];

    public function product()
    {
        return $this->belongsTo(\App\Models\Product::class);
    }

    public function customer()
    {
        return $this->belongsTo(\App\Models\Customer::class);
    }

    public function project()
    {
        return $this->belongsTo(\App\Models\Project::class);
    }
}
