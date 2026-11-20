<x-layouts.app title="Dashboard">
    <x-page-header title="Dashboard" />

    <div class="row g-3">
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-body-secondary small">Products</div>
                    <div class="fs-3 fw-semibold">{{ $productCount }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-body-secondary small">Categories</div>
                    <div class="fs-3 fw-semibold">{{ $categoryCount }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-body-secondary small">Low stock</div>
                    <div class="fs-3 fw-semibold">{{ $lowStockCount }}</div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
