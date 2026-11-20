<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum StockLevel: string
{
    use HasOptions;

    case InStock = 'in';
    case Low = 'low';
    case Out = 'out';

    public function label(): string
    {
        return match ($this) {
            self::InStock => 'In stock',
            self::Low => 'Low stock',
            self::Out => 'Out of stock',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::InStock => 'text-bg-success',
            self::Low => 'text-bg-warning',
            self::Out => 'text-bg-danger',
        };
    }
}
