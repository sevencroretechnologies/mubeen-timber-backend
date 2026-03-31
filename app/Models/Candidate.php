<?php

namespace App\Models;

use App\Traits\HasOrgAndCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Candidate extends Model
{
    use HasFactory, HasOrgAndCompany;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'date_of_birth',
        'gender',
        'address',
        'resume_path',
        'cover_letter',
        'linkedin_url',
        'candidate_source_id',
        'status',
        'is_archived',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'is_archived' => 'boolean',
    ];

    public function candidateSource()
    {
        return $this->belongsTo(CandidateSource::class);
    }

    public function applications()
    {
        return $this->hasMany(JobApplication::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_archived', false);
    }

    public function scopeArchived($query)
    {
        return $query->where('is_archived', true);
    }
}
