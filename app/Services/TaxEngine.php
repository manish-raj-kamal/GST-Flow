<?php

namespace App\Services;

class TaxEngine
{
    public function __construct(
        private readonly GstCalculationService $gstCalculationService,
    ) {
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @return array<string, mixed>
     */
    public function calculate(string $sellerState, string $buyerState, array $items): array
    {
        return $this->gstCalculationService->calculate($items, $sellerState, $buyerState);
    }
}
