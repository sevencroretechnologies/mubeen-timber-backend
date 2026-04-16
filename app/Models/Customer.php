<?php

namespace App\Models;

use App\Enums\CustomerType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'org_id',
        'company_id',
        'name',
        'customer_type',
        'customer_group_id',
        'lead_id',
        'email',
        'phone',
        'website',
        'whatsapp_no',
        'bank_name',
        'ifc_code',
    ];

    protected $casts = [
        'customer_type' => CustomerType::class,
    ];

    public function customerGroup(): BelongsTo
    {
        return $this->belongsTo(CustomerGroup::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }


    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'org_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
}
