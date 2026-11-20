@php($type = $report->type())

<x-layouts.app :title="$report->title()">
    <x-page-header :title="$report->title()" subtitle="Filter by date range, category or product.">
        @include('reports._export-button')
    </x-page-header>

    <ul class="nav nav-pills mb-3">
        @foreach (\App\Enums\StockTransactionType::cases() as $case)
            <li class="nav-item">
                <a href="{{ route('reports.movements', ['type' => $case, ...$filters]) }}"
                   @class(['nav-link', 'active' => $case === $type])>{{ $case->label() }}</a>
            </li>
        @endforeach
    </ul>

    @include('reports._filters', [
        'action' => route('reports.movements', $type),
        'show' => $type === \App\Enums\StockTransactionType::StockIn
            ? ['dates', 'category', 'supplier', 'product']
            : ['dates', 'category', 'product'],
    ])

    <div class="row g-3 mb-3">
        <div class="col-sm-4"><x-stat-card label="Transactions" icon="receipt" :value="number_format($totals['transactions'])" /></div>
        <div class="col-sm-4"><x-stat-card label="Units moved" icon="stack" tone="info" :value="number_format($totals['units'])" /></div>
        <div class="col-sm-4">
            <x-stat-card label="Net change" icon="plus-slash-minus" :tone="$totals['net'] < 0 ? 'danger' : 'success'"
                         :value="sprintf('%+d', $totals['net'])" />
        </div>
    </div>

    <div class="card">
        @if ($transactions->isEmpty())
            <x-empty-state icon="receipt" title="No transactions in this period" />
        @else
            @include('stock._table', ['transactions' => $transactions])
        @endif
    </div>

    <div class="mt-3">{{ $transactions->links() }}</div>
</x-layouts.app>
