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
        'effective_from',
        'effective_to',
        'status',
    ];

    protected $casts = [
        'gst_rate' => 'float',
        'effective_date' => 'date',
        'effective_from' => 'date',
        'effective_to' => 'date',
    ];
}
