@php($stockType = \App\Enums\StockTransactionType::class)

{{-- Links are hidden from users who lack permission, but every route also checks permission on the server. --}}
<nav class="app-sidebar-nav" aria-label="Main navigation">
    <x-sidebar-link route="dashboard" icon="speedometer2">Dashboard</x-sidebar-link>

    <div class="app-sidebar-heading">Inventory</div>
    <a href="{{ route('stock.create', 'in') }}" @class(['app-sidebar-link', 'active' => request()->is('stock/in/*')])>
        <i class="bi bi-box-arrow-in-down" aria-hidden="true"></i><span>Stock in</span>
    </a>
    <a href="{{ route('stock.create', 'out') }}" @class(['app-sidebar-link', 'active' => request()->is('stock/out/*')])>
        <i class="bi bi-box-arrow-up" aria-hidden="true"></i><span>Stock out</span>
    </a>
    @can('create', [\App\Models\StockTransaction::class, $stockType::Adjustment])
        <a href="{{ route('stock.create', 'adjustment') }}" @class(['app-sidebar-link', 'active' => request()->is('stock/adjustment/*')])>
            <i class="bi bi-sliders" aria-hidden="true"></i><span>Adjustment</span>
        </a>
    @endcan
    <x-sidebar-link route="stock.index" active="stock.index|stock.show" icon="clock-history">Transactions</x-sidebar-link>
    <a href="{{ route('low-stock') }}" @class(['app-sidebar-link', 'active' => request()->routeIs('low-stock')])>
        <i class="bi bi-exclamation-triangle" aria-hidden="true"></i>
        <span>Low stock</span>
        @if ($stockAlertCount > 0)
            <span class="badge rounded-pill text-bg-warning ms-auto" aria-label="{{ $stockAlertCount }} products need attention">{{ $stockAlertCount }}</span>
        @endif
    </a>

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
