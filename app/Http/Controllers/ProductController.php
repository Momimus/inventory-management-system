<?php

namespace App\Http\Controllers;

use App\Enums\StockLevel;
use App\Http\Requests\ProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Services\ActivityLogger;
use App\Services\ProductImageStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductController extends Controller
{
    /**
     * Columns the list may be sorted by. Anything else in the URL is rejected,
     * so user input never chooses a raw column name.
     */
    private const SORTABLE = ['name', 'sku', 'quantity', 'selling_price', 'updated_at'];

    public function __construct(
        private ActivityLogger $activity,
        private ProductImageStorage $images,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Product::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'integer'],
            'supplier' => ['nullable', 'integer'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'stock' => ['nullable', Rule::enum(StockLevel::class)],
            'sort' => ['nullable', Rule::in(self::SORTABLE)],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
        ]);

        $products = Product::query()
            ->with(['category', 'supplier'])
            ->search($filters['search'] ?? null)
            ->when($filters['category'] ?? null, fn ($query, $id) => $query->where('category_id', $id))
            ->when($filters['supplier'] ?? null, fn ($query, $id) => $query->where('supplier_id', $id))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('is_active', $status === 'active'))
            ->when($filters['stock'] ?? null, fn ($query, $level) => $query->atStockLevel(StockLevel::from($level)))
            ->orderBy($filters['sort'] ?? 'name', $filters['direction'] ?? 'asc')
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        return view('products.index', [
            'products' => $products,
            'filters' => $filters,
            'categories' => Category::orderBy('name')->pluck('name', 'id'),
            'suppliers' => Supplier::orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Product::class);

        return view('products.create', [...$this->formOptions(), 'product' => new Product]);
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $product = new Product($request->productAttributes());

        if ($request->hasFile('image')) {
            $product->image_path = $this->images->store($request->file('image'));
        }

        $product->save();

        $this->activity->log('product.created', "Created product {$product->sku}", $product, [
            'name' => $product->name,
            'sku' => $product->sku,
        ]);

        return redirect()->route('products.show', $product)->with('success', "Product {$product->name} created.");
    }

    public function show(Product $product): View
    {
        $this->authorize('view', $product);

        $product->load(['category', 'supplier']);

        $recentTransactions = $product->stockTransactions()
            ->with(['product', 'user', 'supplier'])
            ->latest()
            ->latest('id')
            ->limit(10)
            ->get();

        return view('products.show', ['product' => $product, 'recentTransactions' => $recentTransactions]);
    }

    public function edit(Product $product): View
    {
        $this->authorize('update', $product);

        return view('products.edit', [...$this->formOptions($product), 'product' => $product]);
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $product->fill($request->productAttributes());
        $oldImage = $product->image_path;

        if ($request->hasFile('image')) {
            $product->image_path = $this->images->store($request->file('image'));
        } elseif ($request->validated('remove_image')) {
            $product->image_path = null;
        }

        $product->save();

        if ($oldImage !== $product->image_path) {
            $this->images->delete($oldImage);
        }

        if ($product->wasChanged()) {
            $this->activity->log('product.updated', "Updated product {$product->sku}", $product, $this->activity->changesOf($product));
        }

        return redirect()->route('products.show', $product)->with('success', 'Product updated.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->authorize('delete', $product);

        // stock_transactions.product_id is also RESTRICT in the database, as a last line of defence.
        if ($product->stockTransactions()->exists()) {
            return back()->with('error', 'This product has stock history and cannot be deleted. Mark it inactive instead.');
        }

        $product->delete();

        $this->images->delete($product->image_path);

        $this->activity->log('product.deleted', "Deleted product {$product->sku}", $product, [
            'name' => $product->name,
            'sku' => $product->sku,
        ]);

        return redirect()->route('products.index')->with('success', "Product {$product->name} deleted.");
    }

    private function formOptions(?Product $product = null): array
    {
        return [
            'categories' => Category::orderBy('name')->pluck('name', 'id'),
            'suppliers' => Supplier::query()
                ->where(fn ($query) => $query->where('is_active', true)->when(
                    $product?->supplier_id,
                    fn ($query, $id) => $query->orWhere('id', $id),
                ))
                ->orderBy('name')
                ->pluck('name', 'id'),
        ];
    }
}
