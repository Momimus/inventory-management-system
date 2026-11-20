@php
    $maxCategoryUnits = max(1, collect($summary['by_category'])->max('units'));
    $maxDailyUnits = max(1, collect($summary['movements'])->flatMap(fn ($day) => [$day['in'], $day['out']])->max());
@endphp

<x-layouts.app title="Dashboard">
    <x-page-header title="Dashboard" :subtitle="'Inventory overview for '.now()->format('l, j F Y')" />

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl">
            <x-stat-card label="Products" icon="box-seam" :value="number_format($summary['products'])"
                         :note="number_format($summary['active_products']).' active'" :href="route('products.index')" />
        </div>
        <div class="col-sm-6 col-xl">
            <x-stat-card label="Categories" icon="tags" tone="secondary" :value="number_format($summary['categories'])"
                         :href="route('categories.index')" />
        </div>
        <div class="col-sm-6 col-xl">
            <x-stat-card label="Units in stock" icon="stack" tone="info" :value="number_format($summary['units'])"
                         :note="'Value at cost: '.\Illuminate\Support\Number::currency($summary['stock_value_cost'], in: config('inventory.currency'))" />
        </div>
        <div class="col-sm-6 col-xl">
            <x-stat-card label="Low stock" icon="exclamation-triangle" tone="warning" :value="number_format($summary['low_stock'])"
                         :href="route('low-stock', ['level' => 'low'])" />
        </div>
        <div class="col-sm-6 col-xl">
            <x-stat-card label="Out of stock" icon="x-octagon" tone="danger" :value="number_format($summary['out_of_stock'])"
                         :href="route('low-stock', ['level' => 'out'])" />
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-xl-7">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Low-stock alerts</span>
                    <a href="{{ route('low-stock') }}" class="small fw-normal">View all</a>
                </div>
                @if ($alerts->isEmpty())
                    <x-empty-state icon="check2-circle" title="All products are above their reorder level" />
                @else
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th scope="col">Product</th>
                                    <th scope="col">Category</th>
                                    <th scope="col" class="text-end">Qty</th>
                                    <th scope="col" class="text-end">Reorder at</th>
                                    <th scope="col">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($alerts as $product)
                                    <tr>
                                        <td><a href="{{ route('products.show', $product) }}">{{ $product->name }}</a></td>
                                        <td class="text-body-secondary">{{ $product->category->name }}</td>
                                        <td class="text-end fw-semibold">{{ number_format($product->quantity) }}</td>
                                        <td class="text-end">{{ number_format($product->reorder_level) }}</td>
                                        <td><x-stock-badge :product="$product" /></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <div class="col-xl-5">
            <div class="card h-100">
                <div class="card-header">Inventory summary</div>
                <div class="card-body">
                    <dl class="row small mb-3">
                        <dt class="col-7 fw-normal text-body-secondary">Stock value at cost</dt>
                        <dd class="col-5 text-end fw-semibold mb-1"><x-money :amount="$summary['stock_value_cost']" /></dd>
                        <dt class="col-7 fw-normal text-body-secondary">Stock value at selling price</dt>
                        <dd class="col-5 text-end fw-semibold mb-1"><x-money :amount="$summary['stock_value_retail']" /></dd>
                        <dt class="col-7 fw-normal text-body-secondary">Active suppliers</dt>
                        <dd class="col-5 text-end fw-semibold mb-0">{{ number_format($summary['active_suppliers']) }}</dd>
                    </dl>

                    <h2 class="h6 text-body-secondary small text-uppercase mb-2">Units by category</h2>
                    @forelse ($summary['by_category'] as $category)
                        <div class="mb-2">
                            <div class="d-flex justify-content-between small">
                                <span>{{ $category['name'] }}</span>
                                <span class="text-body-secondary">{{ number_format($category['units']) }}</span>
                            </div>
                            <div class="progress" role="progressbar" aria-label="{{ $category['name'] }} units"
                                 aria-valuenow="{{ $category['units'] }}" aria-valuemin="0" aria-valuemax="{{ $maxCategoryUnits }}" style="height: 6px">
                                <div class="progress-bar" style="width: {{ round($category['units'] / $maxCategoryUnits * 100) }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="small text-body-secondary mb-0">No categories yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-xl-7">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Recent stock transactions</span>
                    <a href="{{ route('stock.index') }}" class="small fw-normal">View all</a>
                </div>
                @if ($recentTransactions->isEmpty())
                    <x-empty-state icon="clock-history" title="No stock movements yet" />
                @else
                    @include('stock._table', ['transactions' => $recentTransactions])
                @endif
            </div>
        </div>

        <div class="col-xl-5">
            <div class="card mb-4">
                <div class="card-header">Units moved, last {{ \App\Services\InventoryStatistics::MOVEMENT_DAYS }} days</div>
                <div class="card-body">
                    <div class="d-flex gap-3 small mb-2">
                        <span><span class="badge text-bg-success">&nbsp;</span> In</span>
                        <span><span class="badge text-bg-primary">&nbsp;</span> Out</span>
                    </div>
                    @foreach ($summary['movements'] as $day)
                        <div class="row g-2 align-items-center small mb-1">
                            <div class="col-3 text-body-secondary">{{ \Illuminate\Support\Carbon::parse($day['date'])->format('D j M') }}</div>
                            <div class="col-9">
                                <div class="progress mb-1" role="progressbar" aria-label="Units in on {{ $day['date'] }}"
                                     aria-valuenow="{{ $day['in'] }}" aria-valuemin="0" aria-valuemax="{{ $maxDailyUnits }}" style="height: 6px">
                                    <div class="progress-bar bg-success" style="width: {{ round($day['in'] / $maxDailyUnits * 100) }}%"></div>
                                </div>
                                <div class="progress" role="progressbar" aria-label="Units out on {{ $day['date'] }}"
                                     aria-valuenow="{{ $day['out'] }}" aria-valuemin="0" aria-valuemax="{{ $maxDailyUnits }}" style="height: 6px">
                                    <div class="progress-bar" style="width: {{ round($day['out'] / $maxDailyUnits * 100) }}%"></div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            @can('view-activity-log')
                <div class="card">
                    <div class="card-header">Recent activity</div>
                    @if ($recentActivity->isEmpty())
                        <x-empty-state icon="activity" title="No activity recorded yet" />
                    @else
                        <ul class="list-group list-group-flush small">
                            @foreach ($recentActivity as $entry)
                                <li class="list-group-item">
                                    <div>{{ $entry->description }}</div>
                                    <div class="text-body-secondary">
                                        {{ $entry->user?->name ?? 'System' }} · {{ $entry->created_at->diffForHumans() }}
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endcan
        </div>
    </div>
</x-layouts.app>
