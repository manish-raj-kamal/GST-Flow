<?php

namespace App\Models;

class ActivityLog extends DocumentModel
{
    protected $table = 'activity_logs';

    protected $fillable = [
        'user_id',
        'action_type',
        'affected_record',
        'ip_address',
        'user_agent',
        'meta',
    ];

    protected $casts = [
        'affected_record' => 'array',
        'meta' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
