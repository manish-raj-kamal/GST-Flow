<?php

namespace App\Services;

use App\Models\Invoice;

class InvoiceNumberService
{
    public function generate(string $transactionType = 'sales'): string
    {
        $datePrefix = now()->format('Ym');
        $prefix = strtoupper($transactionType === 'purchase' ? 'PUR' : 'SAL').'-'.$datePrefix;

        $next = $this->nextSequenceForPrefix($prefix, $transactionType);
        $candidate = sprintf('%s-%04d', $prefix, $next);

        // Safety net for concurrent requests.
        while (Invoice::query()->where('invoice_number', $candidate)->exists()) {
            $next++;
            $candidate = sprintf('%s-%04d', $prefix, $next);
        }

        return $candidate;
    }

    private function nextSequenceForPrefix(string $prefix, string $transactionType): int
    {
        $invoiceNumbers = Invoice::query()
            ->where('transaction_type', $transactionType)
            ->get()
            ->pluck('invoice_number');

        $max = $invoiceNumbers
            ->filter(fn (?string $number) => is_string($number) && str_starts_with($number, $prefix.'-'))
            ->map(function (string $number): int {
                $parts = explode('-', $number);
                $suffix = end($parts);

                return ctype_digit((string) $suffix) ? (int) $suffix : 0;
            })
            ->max();

        return ((int) $max) + 1;
    }
}
