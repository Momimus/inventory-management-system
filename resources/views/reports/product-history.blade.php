<x-layouts.app title="Product history">
    <x-page-header title="Product history"
                   :subtitle="$report ? $report->product()->name.' ('.$report->product()->sku.')' : 'Choose a product to see every movement and its balances.'">
        @if ($report)
            @include('reports._export-button')
        @endif
    </x-page-header>

    @include('reports._filters', [
        'action' => route('reports.product-history'),
        'show' => ['product', 'dates'],
        'productRequired' => true,
    ])

    @if ($report)
        <div class="row g-3 mb-3">
            <div class="col-6 col-lg"><x-stat-card label="Opening balance" icon="hourglass-top" tone="secondary" :value="number_format($summary['opening'])" /></div>
            <div class="col-6 col-lg"><x-stat-card label="Stock in" icon="box-arrow-in-down" tone="success" :value="'+'.number_format($summary['in'])" /></div>
            <div class="col-6 col-lg"><x-stat-card label="Stock out" icon="box-arrow-up" :value="'-'.number_format($summary['out'])" /></div>
            <div class="col-6 col-lg"><x-stat-card label="Adjustments" icon="sliders" tone="warning" :value="sprintf('%+d', $summary['adjustments'])" /></div>
            <div class="col-12 col-lg"><x-stat-card label="Closing balance" icon="hourglass-bottom" tone="info" :value="number_format($summary['closing'])" /></div>
        </div>

        <div class="card">
            @if ($transactions->isEmpty())
                <x-empty-state icon="clock-history" title="No movements in this period" />
            @else
                @include('stock._table', ['transactions' => $transactions, 'showProduct' => false])
            @endif
        </div>

        <div class="mt-3">{{ $transactions->links() }}</div>
    @endif
</x-layouts.app>
