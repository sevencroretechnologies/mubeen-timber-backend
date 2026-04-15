<?php

namespace App\Models\Timber;

use App\Models\User;
use App\Traits\HasOrgAndCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TimberSupplier extends Model
{
    use HasFactory, HasOrgAndCompany, SoftDeletes;

    protected $table = 'timber_suppliers';

    protected $fillable = [
        'supplier_code',
        'name',
        'contact_person',
        'phone',
        'email',
        'address',
        'city',
        'state',
        'pincode',
        'gst_number',
        'pan_number',
        'bank_name',
        'bank_account',
        'ifsc_code',
        // 'payment_terms',
        'is_active',
        'company_id',
        'org_id',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function purchaseOrders()
    {
        return $this->hasMany(TimberPurchaseOrder::class, 'supplier_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function generateCode(): string
    {
        $prefix = 'SUP';
        $count = static::withTrashed()->count() + 1;
        return $prefix . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);
    }
}
