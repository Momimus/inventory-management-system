@php
    $reports = [
        ['route' => route('reports.inventory'), 'icon' => 'clipboard-data', 'title' => 'Current inventory',
            'text' => 'Quantity and value of every product, by category, supplier and stock level.'],
        ['route' => route('reports.movements', 'in'), 'icon' => 'box-arrow-in-down', 'title' => 'Stock in',
            'text' => 'Deliveries and returns received in a period.'],
        ['route' => route('reports.movements', 'out'), 'icon' => 'box-arrow-up', 'title' => 'Stock out',
            'text' => 'Stock issued, sold or written off in a period.'],
        ['route' => route('reports.movements', 'adjustment'), 'icon' => 'sliders', 'title' => 'Stock adjustments',
            'text' => 'Corrections made after physical counts, with reasons.'],
        ['route' => route('reports.low-stock'), 'icon' => 'exclamation-triangle', 'title' => 'Low stock',
            'text' => 'A purchasing list: what to reorder, how many, and from whom.'],
        ['route' => route('reports.product-history'), 'icon' => 'clock-history', 'title' => 'Product history',
            'text' => 'Every movement of one product, with opening and closing balances.'],
    ];
@endphp

<x-layouts.app title="Reports">
    <x-page-header title="Reports" subtitle="Every report can be filtered and exported as CSV." />

    <div class="row g-3">
        @foreach ($reports as $item)
            <div class="col-md-6 col-xl-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h2 class="h6">
                            <i class="bi bi-{{ $item['icon'] }} me-2 text-primary" aria-hidden="true"></i>
                            <a href="{{ $item['route'] }}" class="stretched-link text-reset text-decoration-none">{{ $item['title'] }}</a>
                        </h2>
                        <p class="small text-body-secondary mb-0">{{ $item['text'] }}</p>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</x-layouts.app>
