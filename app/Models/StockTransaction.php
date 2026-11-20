<?php

namespace App\Models;

use App\Enums\StockTransactionType;
use App\Models\Concerns\FlushesInventoryStatistics;
use App\Models\Concerns\IsAppendOnly;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One movement of stock for one product. quantity is the signed change
 * (+10 received, -3 issued) and balance_after is the product's quantity once
 * the movement was applied. Rows are created only by StockService and are
 * never edited or deleted.
 */
class StockTransaction extends Model
{
    use FlushesInventoryStatistics, IsAppendOnly;

    protected $fillable = [
        'product_id',
        'user_id',
        'supplier_id',
        'type',
        'quantity',
        'balance_after',
        'reference',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'type' => StockTransactionType::class,
            'quantity' => 'integer',
            'balance_after' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function scopeOfType(Builder $query, ?StockTransactionType $type): void
    {
        $query->when($type, fn (Builder $query) => $query->where('type', $type));
    }

    /**
     * Inclusive date range; either end may be left open.
     */
    public function scopeBetweenDates(Builder $query, ?CarbonInterface $from, ?CarbonInterface $to): void
    {
        $query
            ->when($from, fn (Builder $query) => $query->where($query->qualifyColumn('created_at'), '>=', $from->copy()->startOfDay()))
            ->when($to, fn (Builder $query) => $query->where($query->qualifyColumn('created_at'), '<=', $to->copy()->endOfDay()));
    }

    public function scopeSearch(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);

        $query->when($term !== '', fn (Builder $query) => $query->where(function (Builder $query) use ($term) {
            $query->whereLike('reference', "%{$term}%")
                ->orWhereHas('product', fn (Builder $query) => $query->search($term));
        }));
    }
}
