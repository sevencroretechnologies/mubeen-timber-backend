<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contact extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'customer_contacts';

    protected $fillable = [
        'salutation',
        'first_name',
        'middle_name',
        'last_name',
        'designation',
        'gender',
        'company_name',
        'address',
        'status',
    ];

    protected $appends = ['full_name'];

    protected $with = ['phones', 'emails'];

    public function getFullNameAttribute(): string
    {
        return trim(
            ($this->salutation ? $this->salutation . ' ' : '') .
            $this->first_name .
            ($this->middle_name ? ' ' . $this->middle_name : '') .
            ($this->last_name ? ' ' . $this->last_name : '')
        );
    }

    public function phones(): HasMany
    {
        return $this->hasMany(CustomerContactPhone::class, 'contact_id');
    }

    public function emails(): HasMany
    {
        return $this->hasMany(CustomerContactEmail::class, 'contact_id');
    }
}
