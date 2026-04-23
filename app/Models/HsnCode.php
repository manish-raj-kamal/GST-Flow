<?php

namespace App\Models;

class HsnCode extends DocumentModel
{
    protected $table = 'hsn_codes';

    protected $fillable = [
        'hsn_code',
        'description',
        'category',
        'gst_rate',
        'effective_date',
        'status',
    ];

    protected $casts = [
        'gst_rate' => 'float',
        'effective_date' => 'date',
    ];
}
