<?php

namespace App\Exceptions;

use App\Models\Product;
use Exception;

class ProductBusinessProfileMismatchException extends Exception
{
    public function __construct(
        public readonly Product $product,
        public readonly string $targetBusinessProfileId,
        string $message = 'Invoice item product does not belong to the selected business profile.'
    ) {
        parent::__construct($message, 422);
    }
}
