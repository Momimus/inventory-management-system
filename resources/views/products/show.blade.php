<x-layouts.app :title="$product->name">
    <x-page-header :title="$product->name" :subtitle="'SKU '.$product->sku">
        @can('update', $product)
            <a href="{{ route('products.edit', $product) }}" class="btn btn-outline-secondary"><i class="bi bi-pencil me-1"></i>Edit</a>
        @endcan
    </x-page-header>

    <div class="row g-4">
        <div class="col-xl-4">
            <div class="card mb-4">
                <div class="card-body text-center">
                    @if ($product->imageUrl())
                        <img src="{{ $product->imageUrl() }}" alt="{{ $product->name }}" class="product-image">
                    @else
                        <div class="product-image d-flex align-items-center justify-content-center mx-auto">
                            <i class="bi bi-image fs-1 text-body-tertiary" aria-hidden="true"></i>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">Stock</div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="fs-3 fw-semibold">{{ number_format($product->quantity) }}</span>
                        <x-stock-badge :product="$product" />
                    </div>
                    <div class="small text-body-secondary mb-3">Reorder level: {{ number_format($product->reorder_level) }}</div>

                    @if ($product->is_active)
                        <div class="d-flex flex-wrap gap-2">
                            <a href="{{ route('stock.create', ['type' => 'in', 'product' => $product->id]) }}" class="btn btn-sm btn-success">
                                <i class="bi bi-box-arrow-in-down me-1"></i>Stock in
                            </a>
                            <a href="{{ route('stock.create', ['type' => 'out', 'product' => $product->id]) }}" class="btn btn-sm btn-primary">
                                <i class="bi bi-box-arrow-up me-1"></i>Stock out
                            </a>
                            @can('create', [\App\Models\StockTransaction::class, \App\Enums\StockTransactionType::Adjustment])
                                <a href="{{ route('stock.create', ['type' => 'adjustment', 'product' => $product->id]) }}" class="btn btn-sm btn-outline-warning">
                                    <i class="bi bi-sliders me-1"></i>Adjust
                                </a>
                            @endcan
                        </div>
                    @else
                        <p class="small text-body-secondary mb-0">Inactive products cannot receive stock movements.</p>
                    @endif
                </div>
            </div>

            @can('delete', $product)
                <div class="card border-danger-subtle">
                    <div class="card-header text-danger">
                        {{ $recentTransactions->isEmpty() ? 'Delete product' : 'Product deletion unavailable' }}
                    </div>
                    <div class="card-body">
                        @if ($recentTransactions->isEmpty())
                            <form method="POST" action="{{ route('products.destroy', $product) }}"
                                  data-confirm="Delete {{ $product->name }}? This cannot be undone." data-confirm-button="Delete">
                                @csrf
                                @method('DELETE')
                                <p class="small text-body-secondary">Only products without stock history can be deleted.
                                    Otherwise mark the product inactive.</p>
                                <button type="submit" class="btn btn-outline-danger">Delete product</button>
                            </form>
                        @else
                            <p class="small text-body-secondary mb-0">
                                This product has stock history and cannot be deleted. Mark it inactive instead.
                            </p>
                        @endif
                    </div>
                </div>
            @endcan
        </div>

        <div class="col-xl-8">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between">
                    <span>Details</span>
                    <x-status-badge :active="$product->is_active" />
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4 fw-normal text-body-secondary">Category</dt>
                        <dd class="col-sm-8"><a href="{{ route('categories.show', $product->category) }}">{{ $product->category->name }}</a></dd>

                        <dt class="col-sm-4 fw-normal text-body-secondary">Supplier</dt>
                        <dd class="col-sm-8">
                            @if ($product->supplier)
                                <a href="{{ route('suppliers.show', $product->supplier) }}">{{ $product->supplier->name }}</a>
                            @else
                                —
                            @endif
                        </dd>

                        <dt class="col-sm-4 fw-normal text-body-secondary">Purchase price</dt>
                        <dd class="col-sm-8"><x-money :amount="$product->purchase_price" /></dd>

                        <dt class="col-sm-4 fw-normal text-body-secondary">Selling price</dt>
                        <dd class="col-sm-8"><x-money :amount="$product->selling_price" /></dd>

                        <dt class="col-sm-4 fw-normal text-body-secondary">Stock value (at cost)</dt>
                        <dd class="col-sm-8"><x-money :amount="$product->quantity * $product->purchase_price" /></dd>

                        <dt class="col-sm-4 fw-normal text-body-secondary">Description</dt>
                        <dd class="col-sm-8">{!! $product->description ? nl2br(e($product->description)) : '—' !!}</dd>

                        <dt class="col-sm-4 fw-normal text-body-secondary">Created</dt>
                        <dd class="col-sm-8">{{ $product->created_at->format('d M Y, H:i') }}</dd>

                        <dt class="col-sm-4 fw-normal text-body-secondary">Last updated</dt>
                        <dd class="col-sm-8 mb-0">{{ $product->updated_at->format('d M Y, H:i') }}</dd>
                    </dl>
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Recent stock movements</span>
                    @if ($recentTransactions->isNotEmpty())
                        <a href="{{ route('stock.index', ['product' => $product->id]) }}" class="small fw-normal">Full history</a>
                    @endif
                </div>
                @if ($recentTransactions->isEmpty())
                    <x-empty-state icon="clock-history" title="No stock movements yet" />
                @else
                    @include('stock._table', ['transactions' => $recentTransactions, 'showProduct' => false])
                @endif
            </div>
        </div>
    </div>
</x-layouts.app>
