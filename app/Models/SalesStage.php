<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesStage extends Model
{
    use HasFactory;

    protected $fillable = ['stage_name', 'description'];

    public function opportunities(): HasMany
    {
        return $this->hasMany(Opportunity::class);
    }
}
