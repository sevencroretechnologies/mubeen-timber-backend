<?php

namespace App\Models;

use App\Traits\HasOrgAndCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use HasFactory, HasOrgAndCompany, SoftDeletes;

    protected $fillable = ['org_id', 'company_name', 'address'];

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
