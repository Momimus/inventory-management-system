<x-layouts.app title="Stock transactions">
    <x-page-header title="Stock transactions"
                   :subtitle="$product ? 'History for '.$product->name.' ('.$product->sku.')' : 'Every stock movement, newest first. Records cannot be edited or deleted.'" />

    <x-filter-bar :action="route('stock.index')" :search="$filters['search'] ?? null" placeholder="Product, SKU or reference">
        @if ($product)
            <input type="hidden" name="product" value="{{ $product->id }}">
        @endif
        <x-filter-select name="type" label="Type" :options="\App\Enums\StockTransactionType::options()" :value="$filters['type'] ?? null" />
        <div class="col-sm-6 col-md-auto">
            <label for="from" class="form-label small text-body-secondary mb-1">From</label>
            <input type="date" name="from" id="from" value="{{ $filters['from'] ?? '' }}" @class(['form-control', 'is-invalid' => $errors->has('from')])>
        </div>
        <div class="col-sm-6 col-md-auto">
            <label for="to" class="form-label small text-body-secondary mb-1">To</label>
            <input type="date" name="to" id="to" value="{{ $filters['to'] ?? '' }}" @class(['form-control', 'is-invalid' => $errors->has('to')])>
        </div>
    </x-filter-bar>

    @error('to')
        <div class="alert alert-warning py-2 small">{{ $message }}</div>
    @enderror

    <div class="card">
        @if ($transactions->isEmpty())
            <x-empty-state icon="clock-history" title="No stock movements found"
                           message="Movements appear here when stock is received, issued or adjusted." />
        @else
            @include('stock._table', ['transactions' => $transactions, 'showProduct' => ! $product])
        @endif
    </div>

    <div class="mt-3">{{ $transactions->links() }}</div>
</x-layouts.app>
