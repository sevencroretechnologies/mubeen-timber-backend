<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contract extends Model
{
    use HasFactory;

    protected $fillable = [
        'party_type', 'party_name', 'party_user_id', 'status', 'fulfilment_status',
        'is_signed', 'start_date', 'end_date', 'signee', 'signed_on', 'ip_address',
        'contract_template', 'contract_terms', 'requires_fulfilment',
        'fulfilment_deadline', 'signee_company', 'signed_by_company',
        'document_type', 'document_name', 'party_full_name',
    ];

    protected $casts = [
        'is_signed' => 'boolean',
        'requires_fulfilment' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
        'fulfilment_deadline' => 'date',
        'signed_on' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (Contract $contract) {
            $contract->updateStatus();
            $contract->updateFulfilmentStatus();
        });
    }

    public function updateStatus(): void
    {
        if ($this->is_signed) {
            $now = now();
            if ($this->end_date && $now->greaterThan($this->end_date)) {
                $this->status = 'Inactive';
            } else {
                $this->status = 'Active';
            }
        } else {
            if ($this->status !== 'Cancelled') {
                $this->status = 'Unsigned';
            }
        }
    }

    public function updateFulfilmentStatus(): void
    {
        if (!$this->requires_fulfilment) {
            $this->fulfilment_status = 'N/A';
            return;
        }

        if ($this->exists) {
            $total = $this->fulfilmentChecklists()->count();
            $fulfilled = $this->fulfilmentChecklists()->where('fulfilled', true)->count();

            if ($total === 0) {
                $this->fulfilment_status = 'Unfulfilled';
            } elseif ($fulfilled === $total) {
                $this->fulfilment_status = 'Fulfilled';
            } elseif ($fulfilled > 0) {
                $this->fulfilment_status = 'Partially Fulfilled';
            } else {
                $this->fulfilment_status = 'Unfulfilled';
            }
        }
    }

    public function partyUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'party_user_id');
    }

    public function fulfilmentChecklists(): HasMany
    {
        return $this->hasMany(ContractFulfilmentChecklist::class);
    }
}
