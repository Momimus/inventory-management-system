<?php

namespace App\Http\Controllers;

use App\Enums\StockLevel;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Active products at or below their reorder level. Alerts are worked out from
 * current quantities on every request rather than stored, so there are no
 * duplicate or stale alert records to manage.
 */
class LowStockController extends Controller
{
    public function __invoke(Request $request): View
    {
        $this->authorize('viewAny', Product::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'integer'],
            'supplier' => ['nullable', 'integer'],
            'level' => ['nullable', Rule::in([StockLevel::Low->value, StockLevel::Out->value])],
        ]);

        $products = Product::query()
            ->active()
            ->lowStock()
            ->with(['category', 'supplier'])
            ->search($filters['search'] ?? null)
            ->when($filters['category'] ?? null, fn ($query, $id) => $query->where('category_id', $id))
            ->when($filters['supplier'] ?? null, fn ($query, $id) => $query->where('supplier_id', $id))
            ->when($filters['level'] ?? null, fn ($query, $level) => $query->atStockLevel(StockLevel::from($level)))
            ->orderBy('quantity')
            ->orderByDesc('reorder_level')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('low-stock', [
            'products' => $products,
            'filters' => $filters,
            'categories' => Category::orderBy('name')->pluck('name', 'id'),
            'suppliers' => Supplier::orderBy('name')->pluck('name', 'id'),
        ]);
    }
}
