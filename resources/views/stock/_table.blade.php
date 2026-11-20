{{-- Expects $transactions with product, user and supplier eager loaded. $showProduct hides the product column on a product's own page. --}}
@php($showProduct ??= true)

<div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead>
            <tr>
                <th scope="col">Date</th>
                @if ($showProduct)
                    <th scope="col">Product</th>
                @endif
                <th scope="col">Type</th>
                <th scope="col" class="text-end">Change</th>
                <th scope="col" class="text-end">Balance</th>
                <th scope="col">Reference</th>
                <th scope="col">By</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($transactions as $transaction)
                <tr>
                    <td class="text-nowrap small">
                        <a href="{{ route('stock.show', $transaction) }}" class="text-reset">{{ $transaction->created_at->format('d M Y H:i') }}</a>
                    </td>
                    @if ($showProduct)
                        <td>
                            <a href="{{ route('products.show', $transaction->product) }}" class="fw-medium">{{ $transaction->product->name }}</a>
                            <div class="small text-body-secondary"><code>{{ $transaction->product->sku }}</code></div>
                        </td>
                    @endif
                    <td><x-transaction-type-badge :type="$transaction->type" /></td>
                    <td class="text-end"><x-quantity-change :value="$transaction->quantity" /></td>
                    <td class="text-end">{{ number_format($transaction->balance_after) }}</td>
                    <td class="small">
                        {{ $transaction->reference ?? '—' }}
                        @if ($transaction->supplier)
                            <div class="text-body-secondary">{{ $transaction->supplier->name }}</div>
                        @endif
                    </td>
                    <td class="small">{{ $transaction->user?->name ?? 'Deleted user' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
