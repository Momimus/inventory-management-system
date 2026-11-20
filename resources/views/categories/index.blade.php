<x-layouts.app title="Categories">
    <x-page-header title="Categories" subtitle="Groups used to organise products.">
        @can('create', \App\Models\Category::class)
            <a href="{{ route('categories.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Add category</a>
        @endcan
    </x-page-header>

    <x-filter-bar :action="route('categories.index')" :search="$search" placeholder="Name or description" />

    <div class="card">
        @if ($categories->isEmpty())
            <x-empty-state icon="tags" title="No categories found"
                           :message="$search ? 'Try a different search.' : 'Create a category before adding products.'" />
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col">Name</th>
                            <th scope="col">Description</th>
                            <th scope="col" class="text-end">Products</th>
                            <th scope="col" class="text-end">Units in stock</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($categories as $category)
                            <tr>
                                <td class="fw-medium"><a href="{{ route('categories.show', $category) }}">{{ $category->name }}</a></td>
                                <td class="text-body-secondary">{{ Str::limit($category->description, 80) }}</td>
                                <td class="text-end">{{ number_format($category->products_count) }}</td>
                                <td class="text-end">{{ number_format((int) $category->products_sum_quantity) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="mt-3">{{ $categories->links() }}</div>
</x-layouts.app>
