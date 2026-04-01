<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'project_id',
        'name',
        'code',
        'description',
        'long_description',
        'slug',
        'stock',
        'rate',
        'amount',
    ];

    /**
     * Auto-generate a unique product code after creating.
     */
    protected static function booted(): void
    {
        static::created(function (Product $product) {
            // Format: PRD-00001 (zero-padded to 5 digits)
            $product->code = 'PRD-' . str_pad($product->id, 5, '0', STR_PAD_LEFT);
            $product->saveQuietly();
        });
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }
}
