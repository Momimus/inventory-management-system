<x-layouts.app title="Low stock">
    <x-page-header title="Low stock" subtitle="Active products at or below their reorder level, most urgent first." />

    <x-filter-bar :action="route('low-stock')" :search="$filters['search'] ?? null" placeholder="Name or SKU">
        <x-filter-select name="level" label="Level" :value="$filters['level'] ?? null"
                         :options="['low' => 'Low stock', 'out' => 'Out of stock']" all="Low and out" />
        <x-filter-select name="category" label="Category" :options="$categories" :value="$filters['category'] ?? null" />
        <x-filter-select name="supplier" label="Supplier" :options="$suppliers" :value="$filters['supplier'] ?? null" />
    </x-filter-bar>

    <div class="card overflow-hidden">
        @if ($products->isEmpty())
            <x-empty-state icon="check2-circle" title="Nothing needs reordering"
                           message="Every active product matching these filters is above its reorder level." />
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col">Product</th>
                            <th scope="col">Category</th>
                            <th scope="col">Supplier</th>
                            <th scope="col" class="text-end">In stock</th>
                            <th scope="col" class="text-end">Reorder level</th>
                            <th scope="col" class="text-end">Shortfall</th>
                            <th scope="col">Status</th>
                            <th scope="col"><span class="visually-hidden">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($products as $product)
                            <tr>
                                <td>
                                    <a href="{{ route('products.show', $product) }}" class="fw-medium">{{ $product->name }}</a>
                                    <div class="small text-body-secondary"><code>{{ $product->sku }}</code></div>
                                </td>
                                <td>{{ $product->category->name }}</td>
                                <td>
                                    @if ($product->supplier)
                                        <a href="{{ route('suppliers.show', $product->supplier) }}">{{ $product->supplier->name }}</a>
                                        @if ($product->supplier->phone)
                                            <div class="small text-body-secondary">{{ $product->supplier->phone }}</div>
                                        @endif
                                    @else
                                        <span class="text-body-secondary">—</span>
                                    @endif
                                </td>
                                <td class="text-end fw-semibold">{{ number_format($product->quantity) }}</td>
                                <td class="text-end">{{ number_format($product->reorder_level) }}</td>
                                <td class="text-end">{{ number_format(max(0, $product->reorder_level - $product->quantity)) }}</td>
                                <td><x-stock-badge :product="$product" /></td>
                                <td class="text-end">
                                    <a href="{{ route('stock.create', ['type' => 'in', 'product' => $product->id]) }}"
                                       class="btn btn-sm btn-outline-success text-nowrap">
                                        <i class="bi bi-box-arrow-in-down me-1"></i>Stock in
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="mt-3">{{ $products->links() }}</div>
</x-layouts.app>
