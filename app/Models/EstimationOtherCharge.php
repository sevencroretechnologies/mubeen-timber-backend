<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EstimationOtherCharge extends Model
{
    protected $fillable = [
        'estimation_id',
        'org_id',
        'company_id',
        'labour_charges',
        'transport_and_handling',
        'discount',
        'approximate_tax',
        'overall_total_cft',
        'other_description_amount',
        'other_description',
    ];

    public function estimation()
    {
        return $this->belongsTo(Estimation::class);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class, 'org_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
