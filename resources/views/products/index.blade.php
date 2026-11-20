<x-layouts.app title="Products">
    <x-page-header title="Products" subtitle="Everything you stock, with current quantities.">
        @can('create', \App\Models\Product::class)
            <a href="{{ route('products.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Add product</a>
        @endcan
    </x-page-header>

    <x-filter-bar :action="route('products.index')" :search="$filters['search'] ?? null" placeholder="Name or SKU">
        <x-filter-select name="category" label="Category" :options="$categories" :value="$filters['category'] ?? null" />
        <x-filter-select name="supplier" label="Supplier" :options="$suppliers" :value="$filters['supplier'] ?? null" />
        <x-filter-select name="stock" label="Stock" :options="\App\Enums\StockLevel::options()" :value="$filters['stock'] ?? null" />
        <x-filter-select name="status" label="Status" :options="['active' => 'Active', 'inactive' => 'Inactive']" :value="$filters['status'] ?? null" />
    </x-filter-bar>

    <div class="card">
        @if ($products->isEmpty())
            <x-empty-state icon="box-seam" title="No products found" message="Try a different search or filter.">
                @can('create', \App\Models\Product::class)
                    <a href="{{ route('products.create') }}" class="btn btn-sm btn-primary">Add a product</a>
                @endcan
            </x-empty-state>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col"><x-sort-link column="name" label="Product" default /></th>
                            <th scope="col"><x-sort-link column="sku" label="SKU" /></th>
                            <th scope="col">Category</th>
                            <th scope="col" class="text-end"><x-sort-link column="selling_price" label="Price" /></th>
                            <th scope="col" class="text-end"><x-sort-link column="quantity" label="Qty" /></th>
                            <th scope="col">Stock</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($products as $product)
                            <tr @class(['text-body-secondary' => ! $product->is_active])>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        @if ($product->imageUrl())
                                            <img src="{{ $product->imageUrl() }}" alt="" class="product-thumb" loading="lazy">
                                        @else
                                            <span class="product-thumb d-inline-flex align-items-center justify-content-center"><i class="bi bi-image text-body-tertiary"></i></span>
                                        @endif
                                        <div>
                                            <a href="{{ route('products.show', $product) }}" class="fw-medium">{{ $product->name }}</a>
                                            @unless ($product->is_active)
                                                <span class="badge text-bg-secondary ms-1">Inactive</span>
                                            @endunless
                                            @if ($product->supplier)
                                                <div class="small text-body-secondary">{{ $product->supplier->name }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td><code>{{ $product->sku }}</code></td>
                                <td>{{ $product->category->name }}</td>
                                <td class="text-end"><x-money :amount="$product->selling_price" /></td>
                                <td class="text-end fw-medium">{{ number_format($product->quantity) }}</td>
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
