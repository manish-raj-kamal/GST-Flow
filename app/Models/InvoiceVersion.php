<?php

namespace App\Models;

class InvoiceVersion extends DocumentModel
{
    protected $table = 'invoice_versions';

    protected $fillable = [
        'invoice_id',
        'user_id',
        'original_values',
        'updated_values',
        'change_summary',
        'edited_at',
    ];

    protected $casts = [
        'original_values' => 'array',
        'updated_values' => 'array',
        'edited_at' => 'datetime',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
