<?php

namespace App\Models;

use App\Traits\HasOrgAndCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OnboardingTemplate extends Model
{
    use HasFactory, HasOrgAndCompany, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'days_to_complete',
        'status',
    ];

    public function tasks()
    {
        return $this->hasMany(OnboardingTask::class)->orderBy('order');
    }

    public function employeeOnboardings()
    {
        return $this->hasMany(EmployeeOnboarding::class);
    }
}
