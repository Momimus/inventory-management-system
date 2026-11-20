<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * A stock movement that was refused for a business reason. The message is
 * written for the person using the form.
 */
class StockOperationException extends RuntimeException
{
    public static function insufficientStock(int $available, int $requested): self
    {
        return new self("Only {$available} in stock; cannot remove {$requested}.");
    }

    public static function inactiveProduct(): self
    {
        return new self('This product is inactive. Reactivate it before recording stock movements.');
    }

    public static function noChange(): self
    {
        return new self('The counted quantity is the same as the current stock, so there is nothing to adjust.');
    }
}
