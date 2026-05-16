<?php

namespace App\Models;

class HsnProduct extends DocumentModel
{
    protected $table = 'hsn_products';

    protected $fillable = [
        'hsn_code',
        'primary_name',
        'aliases',
        'category',
        'subcategory',
        'official_description',
        'keywords',
        'gst_rate',
        'search_weight',
        'status',
        'source',
    ];

    protected $casts = [
        'aliases' => 'array',
        'keywords' => 'array',
        'gst_rate' => 'float',
        'search_weight' => 'int',
    ];
}

