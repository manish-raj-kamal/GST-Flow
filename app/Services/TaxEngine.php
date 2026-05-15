<?php

namespace App\Services;

class TaxEngine
{
    public function __construct(
        private readonly GstCalculationService $gstCalculationService,
    ) {
    }

    public function calculate(string $sellerStateCode, string $buyerStateCode, array $items): array
    {
        return $this->gstCalculationService->calculate($items, $sellerStateCode, $buyerStateCode);
    }
}

