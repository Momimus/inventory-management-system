<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum StockTransactionType: string
{
    use HasOptions;

    case StockIn = 'in';
    case StockOut = 'out';
    case Adjustment = 'adjustment';

    public function label(): string
    {
        return match ($this) {
            self::StockIn => 'Stock In',
            self::StockOut => 'Stock Out',
            self::Adjustment => 'Adjustment',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::StockIn => 'box-arrow-in-down',
            self::StockOut => 'box-arrow-up',
            self::Adjustment => 'sliders',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::StockIn => 'text-bg-success',
            self::StockOut => 'text-bg-primary',
            self::Adjustment => 'text-bg-warning',
        };
    }
}
