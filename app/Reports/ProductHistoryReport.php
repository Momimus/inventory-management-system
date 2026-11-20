<?php

namespace App\Reports;

use App\Enums\StockTransactionType;
use App\Models\Product;
use App\Models\StockTransaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Every movement of one product in a period, with the balance at the start
 * and end of the period.
 */
class ProductHistoryReport extends Report
{
    public function __construct(array $filters, private Product $product)
    {
        parent::__construct($filters);
    }

    public function product(): Product
    {
        return $this->product;
    }

    public function title(): string
    {
        return "History {$this->product->sku}";
    }

    public function query(): Builder
    {
        return $this->product->stockTransactions()
            ->getQuery()
            ->with(['product', 'user', 'supplier'])
            ->betweenDates($this->date('from'), $this->date('to'))
            ->orderBy('created_at')
            ->orderBy('id');
    }

    /**
     * Opening balance is the balance after the last movement before the
     * period started. Closing balance is opening plus every change in the period.
     *
     * @return array{opening: int, in: int, out: int, adjustments: int, closing: int}
     */
    public function summary(): array
    {
        $from = $this->date('from');

        $opening = $from
            ? (int) $this->product->stockTransactions()
                ->where('created_at', '<', $from->copy()->startOfDay())
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->value('balance_after')
            : 0;

        $sums = $this->query()->reorder()->setEagerLoads([])->toBase()
            ->selectRaw('coalesce(sum(case when type = ? then quantity else 0 end), 0) as units_in', [StockTransactionType::StockIn->value])
            ->selectRaw('coalesce(sum(case when type = ? then -quantity else 0 end), 0) as units_out', [StockTransactionType::StockOut->value])
            ->selectRaw('coalesce(sum(case when type = ? then quantity else 0 end), 0) as adjustments', [StockTransactionType::Adjustment->value])
            ->first();

        $in = (int) $sums->units_in;
        $out = (int) $sums->units_out;
        $adjustments = (int) $sums->adjustments;

        return [
            'opening' => $opening,
            'in' => $in,
            'out' => $out,
            'adjustments' => $adjustments,
            'closing' => $opening + $in - $out + $adjustments,
        ];
    }

    public function headings(): array
    {
        return ['Date', 'Transaction ID', 'Type', 'Change', 'Balance after', 'Supplier', 'Reference', 'Note', 'Recorded by'];
    }

    /**
     * @param  StockTransaction  $record
     */
    public function row(Model $record): array
    {
        return [
            $record->created_at->format('Y-m-d H:i:s'),
            $record->id,
            $record->type->label(),
            $record->quantity,
            $record->balance_after,
            $record->supplier?->name,
            $record->reference,
            $record->note,
            $record->user?->name,
        ];
    }
}
