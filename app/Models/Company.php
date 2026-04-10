<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'org_id',
        'company_name',
        'address',
        'shipping_address',
        'company_phone',
        'email',
        'website',
        'company_logo',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class, 'org_id');
    }

    // A helper to get the "owner" representation for the Document system
    public function getDocumentOwnerTypeAttribute()
    {
        return \App\Enums\DocumentOwnerType::Company;
    }
}
