<?php

namespace App\Services;

use App\Enums\StockTransactionType;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockTransaction;
use App\Models\Supplier;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Aggregate figures for the dashboard and the sidebar alert badge.
 *
 * They are cached because they scan whole tables. The cache is cleared by
 * the FlushesInventoryStatistics trait whenever products, categories,
 * suppliers or stock transactions change, so figures are never out of date.
 */
class InventoryStatistics
{
    public const CACHE_KEY = 'inventory.statistics';

    public const MOVEMENT_DAYS = 7;

    public function summary(): array
    {
        return Cache::remember(self::CACHE_KEY, (int) config('inventory.dashboard_cache_seconds'), fn () => [
            'products' => Product::count(),
            'active_products' => Product::active()->count(),
            'categories' => Category::count(),
            'active_suppliers' => Supplier::active()->count(),
            'units' => (int) Product::sum('quantity'),
            'stock_value_cost' => (float) Product::sum(DB::raw('quantity * purchase_price')),
            'stock_value_retail' => (float) Product::sum(DB::raw('quantity * selling_price')),
            'low_stock' => Product::active()->lowStock()->where('quantity', '>', 0)->count(),
            'out_of_stock' => Product::active()->outOfStock()->count(),
            'by_category' => $this->unitsByCategory(),
            'movements' => $this->dailyMovements(),
        ]);
    }

    public function alertCount(): int
    {
        $summary = $this->summary();

        return $summary['low_stock'] + $summary['out_of_stock'];
    }

    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @return list<array{name: string, products: int, units: int}>
     */
    private function unitsByCategory(): array
    {
        return Category::query()
            ->withCount('products')
            ->withSum('products', 'quantity')
            ->orderByDesc('products_sum_quantity')
            ->limit(6)
            ->get()
            ->map(fn (Category $category) => [
                'name' => $category->name,
                'products' => $category->products_count,
                'units' => (int) $category->products_sum_quantity,
            ])
            ->all();
    }

    /**
     * Units received and issued per day, oldest first, with empty days included.
     *
     * @return list<array{date: string, in: int, out: int}>
     */
    private function dailyMovements(): array
    {
        $start = Carbon::today()->subDays(self::MOVEMENT_DAYS - 1);

        $rows = StockTransaction::query()
            ->where('created_at', '>=', $start)
            ->whereIn('type', [StockTransactionType::StockIn, StockTransactionType::StockOut])
            ->selectRaw('date(created_at) as day')
            ->selectRaw('sum(case when type = ? then quantity else 0 end) as units_in', [StockTransactionType::StockIn->value])
            ->selectRaw('sum(case when type = ? then -quantity else 0 end) as units_out', [StockTransactionType::StockOut->value])
            ->groupBy('day')
            ->get()
            ->keyBy(fn ($row) => Carbon::parse($row->day)->toDateString());

        return collect(range(0, self::MOVEMENT_DAYS - 1))
            ->map(function (int $offset) use ($start, $rows) {
                $date = $start->copy()->addDays($offset)->toDateString();

                return [
                    'date' => $date,
                    'in' => (int) ($rows[$date]->units_in ?? 0),
                    'out' => (int) ($rows[$date]->units_out ?? 0),
                ];
            })
            ->all();
    }
}
