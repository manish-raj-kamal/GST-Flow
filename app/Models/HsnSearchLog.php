<?php

namespace App\Models;

class HsnSearchLog extends DocumentModel
{
    protected $table = 'hsn_search_logs';

    protected $fillable = [
        'user_id',
        'query',
        'normalized_query',
        'results_count',
        'top_confidence',
        'latency_ms',
        'status',
        'selected_hsn_code',
        'result_snapshot',
        'meta',
    ];

    protected $casts = [
        'results_count' => 'int',
        'top_confidence' => 'int',
        'latency_ms' => 'int',
        'result_snapshot' => 'array',
        'meta' => 'array',
    ];
}

