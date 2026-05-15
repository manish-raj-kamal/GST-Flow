<?php

namespace App\Models;

class GstChangeNotification extends DocumentModel
{
    protected $table = 'gst_change_notifications';

    protected $fillable = [
        'type',
        'reference_key',
        'message',
        'old_value',
        'new_value',
        'effective_from',
        'meta',
        'is_read',
    ];

    protected $casts = [
        'old_value' => 'array',
        'new_value' => 'array',
        'meta' => 'array',
        'effective_from' => 'date',
        'is_read' => 'boolean',
    ];
}

