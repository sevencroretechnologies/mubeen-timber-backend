<?php

namespace App\Models;

use App\Enums\TaskSource as TaskSourceEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesTask extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'task_source_id',
        'source_id',
        'task_type_id',
        'sales_assign_id',
    ];

    public function taskSource()
    {
        return $this->belongsTo(TaskSource::class, 'task_source_id');
    }

    public function taskType()
    {
        return $this->belongsTo(TaskType::class);
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'sales_assign_id');
    }

    public function details()
    {
        return $this->hasMany(SalesTaskDetail::class);
    }

    /**
     * Get the source entity (Lead, Prospect, or Opportunity) based on task_source_id.
     * task_source_id: 1 = Lead, 2 = Prospect, 3 = Opportunity
     */
    public function sourceEntity()
    {
        return match ($this->task_source_id) {
            TaskSourceEnum::LEAD->value       => $this->belongsTo(Lead::class, 'source_id'),
            TaskSourceEnum::PROSPECT->value   => $this->belongsTo(Prospect::class, 'source_id'),
            TaskSourceEnum::OPPORTUNITY->value => $this->belongsTo(Opportunity::class, 'source_id'),
            default => null,
        };
    }

    /**
     * Get the resolved source entity.
     */
    public function getSourceDetailAttribute()
    {
        $relation = $this->sourceEntity();
        return $relation ? $relation->first() : null;
    }
}
