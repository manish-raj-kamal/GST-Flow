<?php

namespace App\Services;

class GstCalculationService
{
    /**
     * @param array<int, array<string, mixed>> $items
     * @return array<string, mixed>
     */
    public function calculate(array $items, string $sellerStateCode, string $buyerStateCode): array
    {
        $sameState = strtoupper($sellerStateCode) === strtoupper($buyerStateCode);
        $processedItems = [];

        $totals = [
            'taxable_value' => 0.0,
            'cgst' => 0.0,
            'sgst' => 0.0,
            'igst' => 0.0,
            'total_tax' => 0.0,
            'invoice_total' => 0.0,
        ];

        foreach ($items as $item) {
            $quantity = (float) ($item['quantity'] ?? 0);
            $rate = (float) ($item['rate'] ?? 0);
            $taxRate = (float) ($item['tax_rate'] ?? ($item['gst_rate'] ?? 0));

            $taxable = round($quantity * $rate, 2);
            $taxAmount = round(($taxable * $taxRate) / 100, 2);

            $cgst = 0.0;
            $sgst = 0.0;
            $igst = 0.0;

            if ($sameState) {
                $cgst = round($taxAmount / 2, 2);
                $sgst = round($taxAmount / 2, 2);
            } else {
                $igst = $taxAmount;
            }

            $lineTotal = round($taxable + $taxAmount, 2);

            $totals['taxable_value'] += $taxable;
            $totals['cgst'] += $cgst;
            $totals['sgst'] += $sgst;
            $totals['igst'] += $igst;
            $totals['total_tax'] += $taxAmount;
            $totals['invoice_total'] += $lineTotal;

            $processedItems[] = [
                'product_name' => (string) ($item['product_name'] ?? ''),
                'hsn_code' => (string) ($item['hsn_code'] ?? ''),
                'quantity' => $quantity,
                'rate' => $rate,
                'tax_rate' => $taxRate,
                'taxable_value' => $taxable,
                'tax_amount' => $taxAmount,
                'cgst' => $cgst,
                'sgst' => $sgst,
                'igst' => $igst,
                'line_total' => $lineTotal,
            ];
        }

        foreach ($totals as $key => $value) {
            $totals[$key] = round($value, 2);
        }

        return [
            'same_state' => $sameState,
            'supply_type' => $sameState ? 'intrastate' : 'interstate',
            'items' => $processedItems,
            'totals' => $totals,
        ];
    }
}
