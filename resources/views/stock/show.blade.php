<x-layouts.app title="Stock transaction">
    <x-page-header :title="'Transaction #'.$transaction->id" :subtitle="$transaction->created_at->format('d M Y, H:i:s')">
        <x-transaction-type-badge :type="$transaction->type" />
    </x-page-header>

    <div class="card col-lg-8 col-xl-6">
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-4 fw-normal text-body-secondary">Product</dt>
                <dd class="col-sm-8">
                    <a href="{{ route('products.show', $transaction->product) }}">{{ $transaction->product->name }}</a>
                    <code class="ms-1">{{ $transaction->product->sku }}</code>
                </dd>

                <dt class="col-sm-4 fw-normal text-body-secondary">Change</dt>
                <dd class="col-sm-8"><x-quantity-change :value="$transaction->quantity" /></dd>

                <dt class="col-sm-4 fw-normal text-body-secondary">Quantity before</dt>
                <dd class="col-sm-8">{{ number_format($transaction->balance_after - $transaction->quantity) }}</dd>

                <dt class="col-sm-4 fw-normal text-body-secondary">Quantity after</dt>
                <dd class="col-sm-8">{{ number_format($transaction->balance_after) }}</dd>

                <dt class="col-sm-4 fw-normal text-body-secondary">Supplier</dt>
                <dd class="col-sm-8">{{ $transaction->supplier?->name ?? '—' }}</dd>

                <dt class="col-sm-4 fw-normal text-body-secondary">Reference</dt>
                <dd class="col-sm-8">{{ $transaction->reference ?? '—' }}</dd>

                <dt class="col-sm-4 fw-normal text-body-secondary">Note</dt>
                <dd class="col-sm-8">{{ $transaction->note ?? '—' }}</dd>

                <dt class="col-sm-4 fw-normal text-body-secondary">Recorded by</dt>
                <dd class="col-sm-8 mb-0">{{ $transaction->user?->name ?? 'Deleted user' }}</dd>
            </dl>
        </div>
        <div class="card-footer small text-body-secondary">
            <i class="bi bi-lock me-1" aria-hidden="true"></i>
            Transactions are permanent. To correct a mistake, record an adjustment.
        </div>
    </div>
</x-layouts.app>
