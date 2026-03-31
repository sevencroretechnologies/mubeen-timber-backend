<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;

class SalesTaskDetail extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'sales_task_id',
        'date',
        'time',
        'description',
        'status',
    ];

    public function salesTask()
    {
        return $this->belongsTo(SalesTask::class);
    }
}
