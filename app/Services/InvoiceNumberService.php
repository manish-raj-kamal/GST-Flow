<?php

namespace App\Services;

use App\Models\Invoice;

class InvoiceNumberService
{
    public function generate(string $transactionType = 'sales'): string
    {
        $datePrefix = now()->format('Ym');
        $prefix = strtoupper($transactionType === 'purchase' ? 'PUR' : 'SAL').'-'.$datePrefix;
        $count = Invoice::query()
            ->where('transaction_type', $transactionType)
            ->where('invoice_date', '>=', now()->startOfMonth()->toDateString())
            ->where('invoice_date', '<=', now()->endOfMonth()->toDateString())
            ->count() + 1;

        return sprintf('%s-%04d', $prefix, $count);
    }
}
