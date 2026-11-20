<x-layouts.app title="Suppliers">
    <x-page-header title="Suppliers" subtitle="Companies you buy stock from.">
        @can('create', \App\Models\Supplier::class)
            <a href="{{ route('suppliers.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Add supplier</a>
        @endcan
    </x-page-header>

    <x-filter-bar :action="route('suppliers.index')" :search="$filters['search'] ?? null" placeholder="Name, contact, email or phone">
        <x-filter-select name="status" label="Status" :options="['active' => 'Active', 'inactive' => 'Inactive']" :value="$filters['status'] ?? null" />
    </x-filter-bar>

    <div class="card">
        @if ($suppliers->isEmpty())
            <x-empty-state icon="truck" title="No suppliers found" message="Try a different search or filter." />
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col">Supplier</th>
                            <th scope="col">Contact</th>
                            <th scope="col">Email</th>
                            <th scope="col">Phone</th>
                            <th scope="col" class="text-end">Products</th>
                            <th scope="col">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($suppliers as $supplier)
                            <tr>
                                <td class="fw-medium"><a href="{{ route('suppliers.show', $supplier) }}">{{ $supplier->name }}</a></td>
                                <td>{{ $supplier->contact_person ?? '—' }}</td>
                                <td>
                                    @if ($supplier->email)
                                        <a href="mailto:{{ $supplier->email }}">{{ $supplier->email }}</a>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="text-nowrap">{{ $supplier->phone ?? '—' }}</td>
                                <td class="text-end">{{ number_format($supplier->products_count) }}</td>
                                <td><x-status-badge :active="$supplier->is_active" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="mt-3">{{ $suppliers->links() }}</div>
</x-layouts.app>
