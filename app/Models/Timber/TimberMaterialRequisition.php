<?php

namespace App\Models\Timber;

use App\Models\User;
use App\Traits\HasOrgAndCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TimberMaterialRequisition extends Model
{
    use HasFactory, HasOrgAndCompany, SoftDeletes;

    protected $table = 'timber_material_requisitions';

    protected $fillable = [
        'requisition_code',
        'job_card_id',
        'project_id',
        'requested_by',
        'approved_by',
        'requisition_date',
        'status',
        'priority',
        'notes',
        'rejection_reason',
        'approved_at',
        'issued_at',
        'company_id',
        'org_id',
    ];

    protected $casts = [
        'requisition_date' => 'date',
        'approved_at' => 'datetime',
        'issued_at' => 'datetime',
    ];

    public function items()
    {
        return $this->hasMany(TimberMaterialRequisitionItem::class, 'requisition_id');
    }

    public function requestedByUser()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedByUser()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public static function generateCode(): string
    {
        $prefix = 'MR';
        $year = now()->format('Y');
        $count = static::withTrashed()->whereYear('created_at', $year)->count() + 1;
        return $prefix . '-' . $year . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);
    }
}
