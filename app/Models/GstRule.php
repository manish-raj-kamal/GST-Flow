<?php

namespace App\Models;

class GstRule extends DocumentModel
{
    protected $table = 'gst_rules';

    protected $fillable = [
        'rule_key',
        'rule_name',
        'config',
        'effective_from',
        'effective_to',
        'status',
    ];

    protected $casts = [
        'config' => 'array',
        'effective_from' => 'date',
        'effective_to' => 'date',
    ];
}

