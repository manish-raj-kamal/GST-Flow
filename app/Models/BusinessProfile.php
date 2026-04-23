<?php

namespace App\Models;

class BusinessProfile extends DocumentModel
{
    protected $table = 'business_profiles';

    protected $fillable = [
        'user_id',
        'business_name',
        'legal_name',
        'gstin',
        'pan',
        'address',
        'city',
        'state',
        'state_code',
        'pincode',
        'email',
        'phone',
        'business_type',
        'registration_date',
    ];

    protected $casts = [
        'registration_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function customers()
    {
        return $this->hasMany(Customer::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }
}
