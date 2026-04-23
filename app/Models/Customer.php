<?php

namespace App\Models;

class Customer extends DocumentModel
{
    protected $table = 'customers';

    protected $fillable = [
        'business_profile_id',
        'customer_name',
        'gstin',
        'state',
        'state_code',
        'address',
        'phone',
        'email',
        'customer_type',
        'is_interstate',
    ];

    protected $casts = [
        'is_interstate' => 'boolean',
    ];

    public function businessProfile()
    {
        return $this->belongsTo(BusinessProfile::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }
}
