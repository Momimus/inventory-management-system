@php($stockType = \App\Enums\StockTransactionType::class)

{{-- Links are hidden from users who lack permission, but every route also checks permission on the server. --}}
<nav class="app-sidebar-nav" aria-label="Main navigation">
    <x-sidebar-link route="dashboard" icon="grid-1x2">Dashboard</x-sidebar-link>

    <div class="app-sidebar-heading">Inventory</div>
    <x-sidebar-link route="stock.create" :params="['type' => 'in']" icon="box-arrow-in-down"
                    :current="request()->is('stock/in/*')">Stock in</x-sidebar-link>
    <x-sidebar-link route="stock.create" :params="['type' => 'out']" icon="box-arrow-up"
                    :current="request()->is('stock/out/*')">Stock out</x-sidebar-link>
    @can('create', [\App\Models\StockTransaction::class, $stockType::Adjustment])
        <x-sidebar-link route="stock.create" :params="['type' => 'adjustment']" icon="sliders"
                        :current="request()->is('stock/adjustment/*')">Adjustment</x-sidebar-link>
    @endcan
    <x-sidebar-link route="stock.index" active="stock.index|stock.show" icon="clock-history">Transactions</x-sidebar-link>
    <x-sidebar-link route="low-stock" icon="exclamation-triangle">
        Low stock
        @if ($stockAlertCount > 0)
            <x-slot:badge>
                <span class="app-sidebar-count" aria-label="{{ $stockAlertCount }} products need attention">{{ $stockAlertCount }}</span>
            </x-slot:badge>
        @endif
    </x-sidebar-link>

    <div class="app-sidebar-heading">Catalogue</div>
    <x-sidebar-link route="products.index" active="products.*" icon="box-seam">Products</x-sidebar-link>
    <x-sidebar-link route="categories.index" active="categories.*" icon="tags">Categories</x-sidebar-link>
    <x-sidebar-link route="suppliers.index" active="suppliers.*" icon="truck">Suppliers</x-sidebar-link>

    @can('view-reports')
        <div class="app-sidebar-heading">Insights</div>
        <x-sidebar-link route="reports.index" active="reports.*" icon="bar-chart-line">Reports</x-sidebar-link>
    @endcan

    @can('viewAny', \App\Models\User::class)
        <div class="app-sidebar-heading">Administration</div>
        <x-sidebar-link route="users.index" active="users.*" icon="people">Users</x-sidebar-link>
        @can('view-activity-log')
            <x-sidebar-link route="activity.index" icon="activity">Activity log</x-sidebar-link>
        @endcan
    @endcan
</nav>
