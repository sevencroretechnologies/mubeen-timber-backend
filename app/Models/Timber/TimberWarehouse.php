<?php

namespace App\Models\Timber;

use App\Traits\HasOrgAndCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TimberWarehouse extends Model
{
    use HasFactory, HasOrgAndCompany, SoftDeletes;

    protected $table = 'timber_warehouses';

    protected $fillable = [
        'name',
        'code',
        'address',
        'city',
        'is_default',
        'is_active',
        'company_id',
        'org_id',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function stockLedger()
    {
        return $this->hasMany(TimberStockLedger::class, 'warehouse_id');
    }
}
