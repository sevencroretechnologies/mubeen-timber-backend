<?php

namespace App\Models\Timber;

use App\Traits\HasOrgAndCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TimberWoodType extends Model
{
    use HasFactory, HasOrgAndCompany;

    protected $table = 'timber_wood_types';

    protected $fillable = [
        'name',
        'code',
        'category',
        'default_rate',
        'unit',
        'description',
        'is_active',
        'company_id',
    ];

    protected $casts = [
        'default_rate' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function stockLedger()
    {
        return $this->hasMany(TimberStockLedger::class, 'wood_type_id');
    }
}
