<x-layouts.app :title="$report->title()">
    <x-page-header :title="$report->title()" subtitle="What to reorder, how many units, and from which supplier.">
        @include('reports._export-button')
    </x-page-header>

    @include('reports._filters', [
        'action' => route('reports.low-stock'),
        'show' => ['category', 'supplier', 'level'],
        'levelOptions' => ['low' => 'Low stock', 'out' => 'Out of stock'],
    ])

    <div class="card">
        @if ($products->isEmpty())
            <x-empty-state icon="check2-circle" title="Nothing needs reordering" />
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col">Product</th>
                            <th scope="col">Supplier</th>
                            <th scope="col" class="text-end">Qty</th>
                            <th scope="col" class="text-end">Reorder level</th>
                            <th scope="col" class="text-end">Shortfall</th>
                            <th scope="col" class="text-end">Est. cost</th>
                            <th scope="col">Stock</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($products as $product)
                            @php($shortfall = max(0, $product->reorder_level - $product->quantity))
                            <tr>
                                <td>
                                    <a href="{{ route('products.show', $product) }}">{{ $product->name }}</a>
                                    <div class="small text-body-secondary"><code>{{ $product->sku }}</code> · {{ $product->category->name }}</div>
                                </td>
                                <td>
                                    {{ $product->supplier?->name ?? '—' }}
                                    @if ($product->supplier?->email)
                                        <div class="small"><a href="mailto:{{ $product->supplier->email }}">{{ $product->supplier->email }}</a></div>
                                    @endif
                                </td>
                                <td class="text-end fw-semibold">{{ number_format($product->quantity) }}</td>
                                <td class="text-end">{{ number_format($product->reorder_level) }}</td>
                                <td class="text-end">{{ number_format($shortfall) }}</td>
                                <td class="text-end"><x-money :amount="$shortfall * $product->purchase_price" /></td>
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
