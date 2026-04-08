<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EstimationAttachment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'org_id',
        'company_id',
        'estimation_id',
        'image',
        'description',
    ];

    protected $appends = ['image_url'];

    /**
     * Get the full URL for the image.
     */
    public function getImageUrlAttribute()
    {
        return $this->image ? asset($this->image) : null;
    }

    /**
     * Get the estimation that owns the attachment.
     */
    public function estimation()
    {
        return $this->belongsTo(Estimation::class);
    }

    /**
     * Get the organization that owns the attachment.
     */
    public function organization()
    {
        return $this->belongsTo(Organization::class, 'org_id');
    }

    /**
     * Get the company that owns the attachment.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
