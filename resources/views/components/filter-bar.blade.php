@props(['action', 'search' => null, 'placeholder' => 'Search'])

{{-- A GET form, so filters stay in the URL and can be bookmarked or shared. --}}
<form method="GET" action="{{ $action }}" class="card card-body mb-3" role="search">
    <div class="row g-2 align-items-end">
        <div class="col-md">
            <label for="search" class="form-label small text-body-secondary mb-1">Search</label>
            <input type="search" name="search" id="search" value="{{ $search }}" class="form-control"
                   placeholder="{{ $placeholder }}" maxlength="100">
        </div>

        {{ $slot }}

        <div class="col-md-auto d-flex gap-2">
            <button type="submit" class="btn btn-outline-primary"><i class="bi bi-funnel me-1"></i>Filter</button>
            <a href="{{ $action }}" class="btn btn-link text-body-secondary">Reset</a>
        </div>
    </div>
</form>
