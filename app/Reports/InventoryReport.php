<?php

namespace App\Reports;

use App\Enums\StockLevel;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Current stock on hand for every product, with its value at cost and at
 * selling price.
 */
class InventoryReport extends Report
{
    public function title(): string
    {
        return 'Current inventory';
    }

    public function query(): Builder
    {
        return Product::query()
            ->with(['category', 'supplier'])
            ->when($this->filter('category'), fn ($query, $id) => $query->where('category_id', $id))
            ->when($this->filter('supplier'), fn ($query, $id) => $query->where('supplier_id', $id))
            ->when($this->filter('status'), fn ($query, $status) => $query->where('is_active', $status === 'active'))
            ->when($this->filter('level'), fn ($query, $level) => $query->atStockLevel(StockLevel::from($level)))
            ->orderBy('name')
            ->orderBy('id');
    }

    /**
     * @return array{products: int, units: int, value_cost: float, value_retail: float}
     */
    public function totals(): array
    {
        $totals = $this->query()->reorder()->setEagerLoads([])->toBase()
            ->selectRaw('count(*) as products, coalesce(sum(quantity), 0) as units')
            ->selectRaw('coalesce(sum(quantity * purchase_price), 0) as value_cost')
            ->selectRaw('coalesce(sum(quantity * selling_price), 0) as value_retail')
            ->first();

        return [
            'products' => (int) $totals->products,
            'units' => (int) $totals->units,
            'value_cost' => (float) $totals->value_cost,
            'value_retail' => (float) $totals->value_retail,
        ];
    }

    public function headings(): array
    {
        return [
            'SKU', 'Product', 'Category', 'Supplier', 'Status', 'Quantity', 'Reorder level', 'Stock level',
            'Purchase price', 'Selling price', 'Value at cost', 'Value at selling price',
        ];
    }

    /**
     * @param  Product  $record
     */
    public function row(Model $record): array
    {
        return [
            $record->sku,
            $record->name,
            $record->category->name,
            $record->supplier?->name,
            $record->is_active ? 'Active' : 'Inactive',
            $record->quantity,
            $record->reorder_level,
            $record->stockLevel()->label(),
            (float) $record->purchase_price,
            (float) $record->selling_price,
            round($record->quantity * (float) $record->purchase_price, 2),
            round($record->quantity * (float) $record->selling_price, 2),
        ];
    }
}
