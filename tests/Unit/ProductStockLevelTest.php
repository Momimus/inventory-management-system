<?php

namespace Tests\Unit;

use App\Enums\StockLevel;
use App\Models\Product;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ProductStockLevelTest extends TestCase
{
    public static function quantities(): array
    {
        return [
            'well stocked' => [50, 10, StockLevel::InStock, false],
            'one above reorder level' => [11, 10, StockLevel::InStock, false],
            'exactly at reorder level' => [10, 10, StockLevel::Low, true],
            'below reorder level' => [3, 10, StockLevel::Low, true],
            'empty' => [0, 10, StockLevel::Out, true],
            'empty with no reorder level' => [0, 0, StockLevel::Out, true],
            'stocked with no reorder level' => [1, 0, StockLevel::InStock, false],
        ];
    }

    #[DataProvider('quantities')]
    public function test_stock_level_is_derived_from_quantity_and_reorder_level(
        int $quantity,
        int $reorderLevel,
        StockLevel $expected,
        bool $isLow,
    ): void {
        $product = (new Product)->forceFill(['quantity' => $quantity, 'reorder_level' => $reorderLevel]);

        $this->assertSame($expected, $product->stockLevel());
        $this->assertSame($isLow, $product->isLowStock());
    }
}
