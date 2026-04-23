<?php

namespace App\Models;

class Product extends DocumentModel
{
    protected $table = 'products';

    protected $fillable = [
        'business_profile_id',
        'product_name',
        'description',
        'category',
        'hsn_code',
        'unit',
        'price',
        'gst_rate',
        'status',
    ];

    protected $casts = [
        'price' => 'float',
        'gst_rate' => 'float',
    ];

    public function businessProfile()
    {
        return $this->belongsTo(BusinessProfile::class);
    }
}
