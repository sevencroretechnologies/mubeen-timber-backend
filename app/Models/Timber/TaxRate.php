<?php

namespace App\Models\Timber;

use App\Enums\TaxType;
use App\Traits\HasOrgAndCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaxRate extends Model
{
    use HasFactory, HasOrgAndCompany, SoftDeletes;

    protected $table = 'tax_rates';

    protected $fillable = [
        'name',
        'rate',
        'tax_type',
        'org_id',
        'company_id',
    ];

    protected $casts = [
        'tax_type' => TaxType::class,
        'rate' => 'float',
    ];
}
