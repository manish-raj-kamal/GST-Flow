<?php

namespace App\Services;

class TaxEngine
{
    public function __construct(
        private readonly GstCalculationService $gstCalculationService,
    ) {
    }

    public function calculate(string $sellerState, string $buyerState, float $gstRate, float $amount = 0): array
    {
        $isInterState = $sellerState !== $buyerState;
        $cacheKey = "tax_calc_{$sellerState}_{$buyerState}_{$gstRate}_{$amount}";

        return \Illuminate\Support\Facades\Cache::remember($cacheKey, now()->addHours(1), function () use ($isInterState, $gstRate, $amount) {
            if ($isInterState) {
                return [
                    'igst_rate' => $gstRate,
                    'igst_amount' => ($amount * $gstRate) / 100,
                    'cgst_rate' => 0,
                    'cgst_amount' => 0,
                    'sgst_rate' => 0,
                    'sgst_amount' => 0,
                    'total_gst' => ($amount * $gstRate) / 100,
                ];
            }

            $halfRate = $gstRate / 2;
            $halfAmount = ($amount * $halfRate) / 100;

            return [
                'igst_rate' => 0,
                'igst_amount' => 0,
                'cgst_rate' => $halfRate,
                'cgst_amount' => $halfAmount,
                'sgst_rate' => $halfRate,
                'sgst_amount' => $halfAmount,
                'total_gst' => $halfAmount * 2,
            ];
        });
    }
}

