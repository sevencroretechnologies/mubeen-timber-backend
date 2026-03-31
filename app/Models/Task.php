<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'task_source_id',
        'task_type_id',
        'related_id',
        'due_date',
        'status',
        'user_id',
    ];

    public function taskSource()
    {
        return $this->belongsTo(TaskSource::class);
    }

    public function taskType()
    {
        return $this->belongsTo(TaskType::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
