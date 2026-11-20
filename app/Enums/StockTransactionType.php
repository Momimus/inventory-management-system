<?php

namespace App\Enums;

enum StockTransactionType: string
{
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
}
