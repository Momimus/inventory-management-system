{{--
    Shared report filter form. Pass the filters to show:
    @include('reports._filters', ['action' => ..., 'show' => ['dates', 'category', 'supplier', 'product', 'status', 'level']])
--}}
@php($show = collect($show))

<form method="GET" action="{{ $action }}" class="card card-body mb-3">
    <div class="row g-2 align-items-end">
        @if ($show->contains('product'))
            <div class="col-md-4">
                <label for="product" class="form-label small text-body-secondary mb-1">Product</label>
                <select name="product" id="product" class="form-select" @required($productRequired ?? false)>
                    <option value="">{{ ($productRequired ?? false) ? 'Choose a product' : 'All products' }}</option>
                    @foreach ($products as $id => $name)
                        <option value="{{ $id }}" @selected((string) ($filters['product'] ?? '') === (string) $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        @if ($show->contains('dates'))
            <div class="col-sm-6 col-md-auto">
                <label for="from" class="form-label small text-body-secondary mb-1">From</label>
                <input type="date" name="from" id="from" value="{{ $filters['from'] ?? '' }}" class="form-control">
            </div>
            <div class="col-sm-6 col-md-auto">
                <label for="to" class="form-label small text-body-secondary mb-1">To</label>
                <input type="date" name="to" id="to" value="{{ $filters['to'] ?? '' }}" class="form-control">
            </div>
        @endif

        @if ($show->contains('category'))
            <x-filter-select name="category" label="Category" :options="$categories" :value="$filters['category'] ?? null" />
        @endif
        @if ($show->contains('supplier'))
            <x-filter-select name="supplier" label="Supplier" :options="$suppliers" :value="$filters['supplier'] ?? null" />
        @endif
        @if ($show->contains('level'))
            <x-filter-select name="level" label="Stock level" :options="$levelOptions ?? \App\Enums\StockLevel::options()" :value="$filters['level'] ?? null" />
        @endif
        @if ($show->contains('status'))
            <x-filter-select name="status" label="Status" :options="['active' => 'Active', 'inactive' => 'Inactive']" :value="$filters['status'] ?? null" />
        @endif

        <div class="col-md-auto d-flex gap-2">
            <button type="submit" class="btn btn-outline-primary"><i class="bi bi-funnel me-1"></i>Apply</button>
            <a href="{{ $action }}" class="btn btn-link text-body-secondary">Reset</a>
        </div>
    </div>

    @if ($errors->any())
        <div class="text-danger small mt-2">{{ $errors->first() }}</div>
    @endif
</form>
