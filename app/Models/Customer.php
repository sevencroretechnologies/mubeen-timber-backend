<?php

namespace App\Models;

use App\Enums\CustomerType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'customer_type',
        'customer_group_id',
        'territory_id',
        'lead_id',
        'opportunity_id',
        'industry_id',
        'default_price_list_id',
        'payment_term_id',
        'customer_contact_id',
        'email',
        'phone',
        'website',
        'tax_id',
        'billing_currency',
        'bank_account_details',
        'print_language',
        'customer_details',
    ];

    protected $casts = [
        'customer_type' => CustomerType::class,
    ];

    public function customerGroup(): BelongsTo
    {
        return $this->belongsTo(CustomerGroup::class);
    }

    public function territory(): BelongsTo
    {
        return $this->belongsTo(Territory::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }

    public function industry(): BelongsTo
    {
        return $this->belongsTo(IndustryType::class, 'industry_id');
    }

    public function priceList(): BelongsTo
    {
        return $this->belongsTo(PriceList::class, 'default_price_list_id');
    }

    public function paymentTerm(): BelongsTo
    {
        return $this->belongsTo(PaymentTerm::class, 'payment_term_id');
    }

    public function primaryContact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'customer_contact_id');
    }
}
