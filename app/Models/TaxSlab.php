<?php

namespace App\Models;

class TaxSlab extends DocumentModel
{
    protected $table = 'tax_slabs';

    protected $fillable = [
        'name',
        'rate',
        'effective_date',
        'status',
    ];

    protected $casts = [
        'rate' => 'float',
        'effective_date' => 'date',
    ];
}
