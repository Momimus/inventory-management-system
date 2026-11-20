@php($maxKb = config('inventory.product_images.max_upload_kb'))

<div class="row g-4">
    <div class="col-xl-8">
        <div class="card">
            <div class="card-header">Details</div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <x-form.input name="name" label="Product name" :value="$product->name" required maxlength="150" />
                    </div>
                    <div class="col-md-4">
                        <x-form.input name="sku" label="SKU" :value="$product->sku" required maxlength="64" class="text-uppercase"
                                      help="Unique stock code, e.g. PEN-BLUE-01." />
                    </div>
                    <div class="col-md-6">
                        <x-form.select name="category_id" label="Category" :options="$categories" :value="$product->category_id"
                                       placeholder="Choose a category" required />
                    </div>
                    <div class="col-md-6">
                        <x-form.select name="supplier_id" label="Supplier" :options="$suppliers" :value="$product->supplier_id"
                                       placeholder="No supplier" />
                    </div>
                </div>

                <x-form.textarea name="description" label="Description" :value="$product->description" maxlength="2000" />
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header">Pricing and stock</div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <x-form.input name="purchase_price" type="number" label="Purchase price" :value="$product->purchase_price ?? '0.00'"
                                      step="0.01" min="0" required />
                    </div>
                    <div class="col-md-4">
                        <x-form.input name="selling_price" type="number" label="Selling price" :value="$product->selling_price ?? '0.00'"
                                      step="0.01" min="0" required />
                    </div>
                    <div class="col-md-4">
                        <x-form.input name="reorder_level" type="number" label="Reorder level" :value="$product->reorder_level"
                                      step="1" min="0" required help="Alert when stock falls to this number." />
                    </div>
                </div>

                <div class="alert alert-light border small mb-0">
                    <i class="bi bi-info-circle me-1"></i>
                    @if ($product->exists)
                        Current quantity: <strong>{{ number_format($product->quantity) }}</strong>.
                        Quantities change only through stock in, stock out and adjustments, so every change is recorded.
                    @else
                        New products start with zero stock. Record the first delivery with a stock-in transaction.
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card">
            <div class="card-header">Image</div>
            <div class="card-body">
                @if ($product->imageUrl())
                    <img src="{{ $product->imageUrl() }}" alt="Current image of {{ $product->name }}" class="product-image mb-3">
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="remove_image" id="remove_image" value="1" @checked(old('remove_image'))>
                        <label class="form-check-label" for="remove_image">Remove current image</label>
                    </div>
                @endif

                <x-form.input name="image" type="file" :label="$product->imageUrl() ? 'Replace image' : 'Upload image'"
                              accept="image/jpeg,image/png,image/webp"
                              :help="'JPG, PNG or WebP, up to '.($maxKb / 1024).' MB. Images are resized and converted to WebP.'" />
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-body">
                <x-form.switch name="is_active" label="Active product" :checked="$product->is_active"
                               help="Inactive products are kept for history but cannot receive new stock movements." />
            </div>
        </div>
    </div>
</div>
