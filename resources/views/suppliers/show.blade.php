<x-layouts.app :title="$supplier->name">
    <x-page-header :title="$supplier->name">
        <x-status-badge :active="$supplier->is_active" />
        @can('update', $supplier)
            <a href="{{ route('suppliers.edit', $supplier) }}" class="btn btn-outline-secondary"><i class="bi bi-pencil me-1"></i>Edit</a>
        @endcan
    </x-page-header>

    <div class="row g-4">
        <div class="col-xl-4">
            <div class="card mb-4">
                <div class="card-header">Contact details</div>
                <div class="card-body">
                    <dl class="mb-0">
                        <dt class="small text-body-secondary fw-normal">Contact person</dt>
                        <dd>{{ $supplier->contact_person ?? '—' }}</dd>
                        <dt class="small text-body-secondary fw-normal">Email</dt>
                        <dd>
                            @if ($supplier->email)
                                <a href="mailto:{{ $supplier->email }}">{{ $supplier->email }}</a>
                            @else
                                —
                            @endif
                        </dd>
                        <dt class="small text-body-secondary fw-normal">Phone</dt>
                        <dd>{{ $supplier->phone ?? '—' }}</dd>
                        <dt class="small text-body-secondary fw-normal">Address</dt>
                        <dd class="mb-0">{!! $supplier->address ? nl2br(e($supplier->address)) : '—' !!}</dd>
                    </dl>
                </div>
            </div>

            @can('delete', $supplier)
                <div class="card border-danger-subtle">
                    <div class="card-header text-danger">Delete supplier</div>
                    <div class="card-body">
                        @if ($canBeDeleted)
                            <form method="POST" action="{{ route('suppliers.destroy', $supplier) }}"
                                  data-confirm="Delete the supplier {{ $supplier->name }}? This cannot be undone." data-confirm-button="Delete">
                                @csrf
                                @method('DELETE')
                                <p class="small text-body-secondary">No products or stock records use this supplier.</p>
                                <button type="submit" class="btn btn-outline-danger">Delete supplier</button>
                            </form>
                        @else
                            <p class="small mb-0">This supplier is linked to products or stock records, so it is kept for the history.
                                Mark it inactive instead.</p>
                        @endif
                    </div>
                </div>
            @endcan
        </div>

        <div class="col-xl-8">
            <div class="card">
                <div class="card-header">Products supplied ({{ $products->total() }})</div>
                @if ($products->isEmpty())
                    <x-empty-state icon="box" title="No products linked to this supplier" />
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th scope="col">Product</th>
                                    <th scope="col">SKU</th>
                                    <th scope="col">Category</th>
                                    <th scope="col" class="text-end">Quantity</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($products as $product)
                                    <tr>
                                        <td class="fw-medium">{{ $product->name }}</td>
                                        <td><code>{{ $product->sku }}</code></td>
                                        <td>{{ $product->category->name }}</td>
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
    </div>
</x-layouts.app>
