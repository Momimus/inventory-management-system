@php
    use App\Enums\StockTransactionType;

    $isAdjustment = $type === StockTransactionType::Adjustment;
    $intro = match ($type) {
        StockTransactionType::StockIn => 'Record stock received, for example a supplier delivery or a customer return.',
        StockTransactionType::StockOut => 'Record stock leaving, for example a sale, internal use or damaged goods.',
        StockTransactionType::Adjustment => 'Correct the system quantity to match a physical count. The difference is recorded with your reason.',
    };
    $productOptions = $products->mapWithKeys(fn ($product) => [
        $product->id => "{$product->name} ({$product->sku}) — {$product->quantity} in stock",
    ]);
@endphp

<x-layouts.app :title="$type->label()">
    <x-page-header :title="$type->label()" :subtitle="$intro" />

    <div class="card col-lg-8 col-xl-6">
        <div class="card-body">
            @if ($products->isEmpty())
                <x-empty-state icon="box-seam" title="No active products" message="Add a product before recording stock movements.">
                    <a href="{{ route('products.create') }}" class="btn btn-sm btn-primary">Add product</a>
                </x-empty-state>
            @else
                <form method="POST" action="{{ route('stock.store', $type) }}" novalidate>
                    @csrf

                    <x-form.select name="product_id" label="Product" :options="$productOptions" :value="$selectedProductId"
                                   placeholder="Choose a product" required />

                    @if ($isAdjustment)
                        <x-form.input name="counted_quantity" type="number" label="Counted quantity" min="0" step="1" required
                                      help="The number of units physically on hand. It replaces the current quantity." />
                    @else
                        <x-form.input name="quantity" type="number" label="Quantity" min="1" step="1" required
                                      :help="$type === StockTransactionType::StockOut ? 'Cannot be more than the quantity in stock.' : null" />
                    @endif

                    @if ($type === StockTransactionType::StockIn)
                        <x-form.select name="supplier_id" label="Supplier" :options="$suppliers" placeholder="Not from a supplier" />
                    @endif

                    <x-form.input name="reference" label="Reference" maxlength="100"
                                  help="Delivery note, invoice or order number (optional)." />

                    <x-form.textarea name="note" :label="$isAdjustment ? 'Reason for adjustment' : 'Note'" rows="2" maxlength="500"
                                     :required="$isAdjustment" />

                    <div class="d-flex gap-2">
                        <x-submit-button>Record {{ strtolower($type->label()) }}</x-submit-button>
                        <a href="{{ route('stock.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            @endif
        </div>
    </div>
</x-layouts.app>
