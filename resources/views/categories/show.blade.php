<x-layouts.app :title="$category->name">
    <x-page-header :title="$category->name" :subtitle="$category->description">
        @can('update', $category)
            <a href="{{ route('categories.edit', $category) }}" class="btn btn-outline-secondary"><i class="bi bi-pencil me-1"></i>Edit</a>
        @endcan
    </x-page-header>

    <div class="row g-4">
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header">Products in this category ({{ $products->total() }})</div>
                @if ($products->isEmpty())
                    <x-empty-state icon="box" title="No products yet" />
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th scope="col">Product</th>
                                    <th scope="col">SKU</th>
                                    <th scope="col" class="text-end">Quantity</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($products as $product)
                                    <tr>
                                        <td class="fw-medium"><a href="{{ route('products.show', $product) }}">{{ $product->name }}</a></td>
                                        <td><code>{{ $product->sku }}</code></td>
                                        <td class="text-end">{{ number_format($product->quantity) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
            <div class="mt-3">{{ $products->links() }}</div>
        </div>

        @can('delete', $category)
            <div class="col-xl-4">
                <div class="card border-danger-subtle">
                    <div class="card-header text-danger">Delete category</div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('categories.destroy', $category) }}"
                              data-confirm="Delete the category {{ $category->name }}? This cannot be undone." data-confirm-button="Delete">
                            @csrf
                            @method('DELETE')

                            @if ($products->total() > 0)
                                @if ($otherCategories->isEmpty())
                                    <p class="small mb-0">This category has products and there is no other category to move them to.
                                        Create another category first.</p>
                                @else
                                    <x-form.select name="reassign_to" :label="'Move '.$products->total().' product(s) to'"
                                                   :options="$otherCategories" placeholder="Choose a category" required />
                                    <button type="submit" class="btn btn-outline-danger">Move products and delete</button>
                                @endif
                            @else
                                <p class="small text-body-secondary">This category has no products and can be deleted safely.</p>
                                <button type="submit" class="btn btn-outline-danger">Delete category</button>
                            @endif
                        </form>
                    </div>
                </div>
            </div>
        @endcan
    </div>
</x-layouts.app>
