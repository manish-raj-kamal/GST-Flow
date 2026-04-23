<?php

namespace App\Models;

class Report extends DocumentModel
{
    protected $table = 'reports';

    protected $fillable = [
        'user_id',
        'report_type',
        'from_date',
        'to_date',
        'filters',
        'summary',
    ];

    protected $casts = [
        'from_date' => 'date',
        'to_date' => 'date',
        'filters' => 'array',
        'summary' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
