<?php

namespace App\Reports;

use App\Enums\StockTransactionType;
use App\Models\StockTransaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Stock-in, stock-out or adjustment transactions for a period.
 */
class MovementReport extends Report
{
    public function __construct(array $filters, private StockTransactionType $type)
    {
        parent::__construct($filters);
    }

    public function type(): StockTransactionType
    {
        return $this->type;
    }

    public function title(): string
    {
        return $this->type->label().' report';
    }

    public function query(): Builder
    {
        return StockTransaction::query()
            ->with(['product.category', 'user', 'supplier'])
            ->ofType($this->type)
            ->betweenDates($this->date('from'), $this->date('to'))
            ->when($this->filter('product'), fn ($query, $id) => $query->where('product_id', $id))
            ->when($this->filter('supplier'), fn ($query, $id) => $query->where('supplier_id', $id))
            ->when($this->filter('category'), fn ($query, $id) => $query->whereHas(
                'product',
                fn ($query) => $query->where('category_id', $id),
            ))
            ->orderBy('created_at')
            ->orderBy('id');
    }

    /**
     * @return array{transactions: int, units: int, net: int}
     */
    public function totals(): array
    {
        $totals = $this->query()->reorder()->setEagerLoads([])->toBase()
            ->selectRaw('count(*) as transactions')
            ->selectRaw('coalesce(sum(case when quantity < 0 then -quantity else quantity end), 0) as units')
            ->selectRaw('coalesce(sum(quantity), 0) as net')
            ->first();

        return [
            'transactions' => (int) $totals->transactions,
            'units' => (int) $totals->units,
            'net' => (int) $totals->net,
        ];
    }

    public function headings(): array
    {
        return [
            'Date', 'Transaction ID', 'SKU', 'Product', 'Category', 'Change', 'Balance after',
            'Supplier', 'Reference', 'Note', 'Recorded by',
        ];
    }

    /**
     * @param  StockTransaction  $record
     */
    public function row(Model $record): array
    {
        return [
            $record->created_at->format('Y-m-d H:i:s'),
            $record->id,
            $record->product->sku,
            $record->product->name,
            $record->product->category->name,
            $record->quantity,
            $record->balance_after,
            $record->supplier?->name,
            $record->reference,
            $record->note,
            $record->user?->name,
        ];
    }
}
