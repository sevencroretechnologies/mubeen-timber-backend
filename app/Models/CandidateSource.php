<?php

namespace App\Models;

use App\Traits\HasOrgAndCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CandidateSource extends Model
{
    use HasFactory, HasOrgAndCompany, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'status',
    ];

    public function candidates()
    {
        return $this->hasMany(Candidate::class);
    }
}
