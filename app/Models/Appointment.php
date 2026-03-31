<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'scheduled_time', 'status','appointment_with', 'party',
    ];

    protected $casts = [
        'scheduled_time' => 'datetime',
    ];
}
