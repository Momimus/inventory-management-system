<?php

namespace App\Reports;

use App\Enums\StockLevel;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Active products at or below their reorder level, with the quantity needed
 * to get back to that level. Useful as a purchasing list.
 */
class LowStockReport extends Report
{
    public function title(): string
    {
        return 'Low stock report';
    }

    public function query(): Builder
    {
        return Product::query()
            ->active()
            ->lowStock()
            ->with(['category', 'supplier'])
            ->when($this->filter('category'), fn ($query, $id) => $query->where('category_id', $id))
            ->when($this->filter('supplier'), fn ($query, $id) => $query->where('supplier_id', $id))
            ->when(
                in_array($this->filter('level'), [StockLevel::Low->value, StockLevel::Out->value], true),
                fn ($query) => $query->atStockLevel(StockLevel::from($this->filter('level'))),
            )
            ->orderBy('quantity')
            ->orderByDesc('reorder_level')
            ->orderBy('id');
    }

    public function headings(): array
    {
        return [
            'SKU', 'Product', 'Category', 'Supplier', 'Supplier email', 'Supplier phone',
            'Quantity', 'Reorder level', 'Shortfall', 'Stock level', 'Purchase price', 'Cost to reach reorder level',
        ];
    }

    /**
     * @param  Product  $record
     */
    public function row(Model $record): array
    {
        $shortfall = max(0, $record->reorder_level - $record->quantity);

        return [
            $record->sku,
            $record->name,
            $record->category->name,
            $record->supplier?->name,
            $record->supplier?->email,
            $record->supplier?->phone,
            $record->quantity,
            $record->reorder_level,
            $shortfall,
            $record->stockLevel()->label(),
            (float) $record->purchase_price,
            round($shortfall * (float) $record->purchase_price, 2),
        ];
    }
}
