<x-layouts.app :title="$report->title()">
    <x-page-header :title="$report->title()" subtitle="Stock on hand right now.">
        @include('reports._export-button')
    </x-page-header>

    @include('reports._filters', ['action' => route('reports.inventory'), 'show' => ['category', 'supplier', 'level', 'status']])

    <div class="row g-3 mb-3">
        <div class="col-sm-6 col-xl-3"><x-stat-card label="Products" icon="box-seam" :value="number_format($totals['products'])" /></div>
        <div class="col-sm-6 col-xl-3"><x-stat-card label="Units" icon="stack" tone="info" :value="number_format($totals['units'])" /></div>
        <div class="col-sm-6 col-xl-3">
            <x-stat-card label="Value at cost" icon="cash-stack" tone="success"
                         :value="\Illuminate\Support\Number::currency($totals['value_cost'], in: config('inventory.currency'))" />
        </div>
        <div class="col-sm-6 col-xl-3">
            <x-stat-card label="Value at selling price" icon="tag" tone="secondary"
                         :value="\Illuminate\Support\Number::currency($totals['value_retail'], in: config('inventory.currency'))" />
        </div>
    </div>

    <div class="card">
        @if ($products->isEmpty())
            <x-empty-state icon="clipboard-data" title="No products match these filters" />
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col">Product</th>
                            <th scope="col">Category</th>
                            <th scope="col">Supplier</th>
                            <th scope="col" class="text-end">Qty</th>
                            <th scope="col" class="text-end">Reorder</th>
                            <th scope="col" class="text-end">Unit cost</th>
                            <th scope="col" class="text-end">Value at cost</th>
                            <th scope="col">Stock</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($products as $product)
                            <tr @class(['text-body-secondary' => ! $product->is_active])>
                                <td>
                                    <a href="{{ route('products.show', $product) }}">{{ $product->name }}</a>
                                    <div class="small text-body-secondary"><code>{{ $product->sku }}</code></div>
                                </td>
                                <td>{{ $product->category->name }}</td>
                                <td>{{ $product->supplier?->name ?? '—' }}</td>
                                <td class="text-end fw-medium">{{ number_format($product->quantity) }}</td>
                                <td class="text-end">{{ number_format($product->reorder_level) }}</td>
                                <td class="text-end"><x-money :amount="$product->purchase_price" /></td>
                                <td class="text-end"><x-money :amount="$product->quantity * $product->purchase_price" /></td>
                                <td><x-stock-badge :product="$product" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="mt-3">{{ $products->links() }}</div>
</x-layouts.app>
