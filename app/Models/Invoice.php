<?php

namespace App\Models;

class Invoice extends DocumentModel
{
    protected $table = 'invoices';

    protected $fillable = [
        'business_profile_id',
        'customer_id',
        'transaction_type',
        'invoice_number',
        'invoice_date',
        'seller_gstin',
        'buyer_gstin',
        'place_of_supply',
        'seller_state_code',
        'buyer_state_code',
        'items',
        'taxable_value',
        'cgst',
        'sgst',
        'igst',
        'total_tax',
        'total_amount',
        'status',
        'tax_breakdowns',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'items' => 'array',
        'tax_breakdowns' => 'array',
        'taxable_value' => 'float',
        'cgst' => 'float',
        'sgst' => 'float',
        'igst' => 'float',
        'total_tax' => 'float',
        'total_amount' => 'float',
    ];

    public function businessProfile()
    {
        return $this->belongsTo(BusinessProfile::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function versions()
    {
        return $this->hasMany(InvoiceVersion::class);
    }
}
