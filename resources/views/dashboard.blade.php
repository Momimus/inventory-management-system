@use('App\Enums\StockTransactionType')
@use('App\Models\Product')
@use('App\Models\StockTransaction')
@use('App\Services\InventoryStatistics')
@use('Illuminate\Support\Number')

@php
    $currency = config('inventory.currency');
    $money = fn ($amount, $precision = 2) => Number::currency((float) $amount, in: $currency, precision: $precision);
    $movements = collect($summary['movements']);
    $unitsIn = $movements->sum('in');
    $unitsOut = $movements->sum('out');
    $margin = $summary['stock_value_retail'] - $summary['stock_value_cost'];
    $marginShare = $summary['stock_value_retail'] > 0 ? $margin / $summary['stock_value_retail'] * 100 : null;
@endphp

<x-layouts.app title="Dashboard">
    <x-page-header title="Dashboard" subtitle="Stock levels, movements and alerts across your catalogue.">
        @can('create', [StockTransaction::class, StockTransactionType::StockIn])
            <a href="{{ route('stock.create', 'in') }}" class="btn btn-primary"><i class="bi bi-box-arrow-in-down" aria-hidden="true"></i>Stock in</a>
        @endcan
        @can('create', [StockTransaction::class, StockTransactionType::StockOut])
            <a href="{{ route('stock.create', 'out') }}" class="btn btn-outline-secondary"><i class="bi bi-box-arrow-up" aria-hidden="true"></i>Stock out</a>
        @endcan
        @can('create', Product::class)
            <a href="{{ route('products.create') }}" class="btn btn-outline-secondary"><i class="bi bi-plus-lg" aria-hidden="true"></i>Add product</a>
        @endcan
        @can('view-reports')
            <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary"><i class="bi bi-bar-chart-line" aria-hidden="true"></i>Reports</a>
        @endcan
    </x-page-header>

    {{-- Key figures --}}
    <section class="row g-3 mb-4" aria-label="Key figures">
        <div class="col-6 col-md-4 col-xxl-2">
            <x-stat-card label="Products" icon="box-seam" :value="number_format($summary['products'])"
                         :note="number_format($summary['active_products']).' active'" :href="route('products.index')" />
        </div>
        <div class="col-6 col-md-4 col-xxl-2">
            <x-stat-card label="Categories" icon="tags" :value="number_format($summary['categories'])"
                         :note="number_format($summary['active_suppliers']).' active '.Str::plural('supplier', $summary['active_suppliers'])"
                         :href="route('categories.index')" />
        </div>
        <div class="col-6 col-md-4 col-xxl-2">
            <x-stat-card label="Units in stock" icon="stack" :value="number_format($summary['units'])" note="Across all products" />
        </div>
        <div class="col-6 col-md-4 col-xxl-2">
            <x-stat-card label="Low stock" icon="exclamation-triangle" :tone="$summary['low_stock'] > 0 ? 'warning' : null"
                         :value="number_format($summary['low_stock'])" note="At or below reorder level"
                         :href="route('low-stock', ['level' => 'low'])" />
        </div>
        <div class="col-6 col-md-4 col-xxl-2">
            <x-stat-card label="Out of stock" icon="x-octagon" :tone="$summary['out_of_stock'] > 0 ? 'critical' : null"
                         :value="number_format($summary['out_of_stock'])" note="Active, none on hand"
                         :href="route('low-stock', ['level' => 'out'])" />
        </div>
        <div class="col-6 col-md-4 col-xxl-2">
            <x-stat-card label="Inventory value" icon="wallet2" :value="$money($summary['stock_value_cost'], 0)"
                         :title="$money($summary['stock_value_cost'])" note="At purchase cost" />
        </div>
    </section>

    {{-- Health and movement --}}
    <div class="row g-4 mb-4">
        <div class="col-xl-4">
            <section class="card h-100" aria-labelledby="health-title">
                <div class="card-header">
                    <div>
                        <h2 class="panel-title" id="health-title">Stock health</h2>
                        <p class="panel-subtitle">{{ number_format($summary['active_products']) }} active {{ Str::plural('product', $summary['active_products']) }}, inactive ones excluded</p>
                    </div>
                    <a href="{{ route('low-stock') }}" class="panel-link">Review alerts</a>
                </div>
                <div class="card-body">
                    <x-stock-health :healthy="$summary['in_stock']" :low="$summary['low_stock']" :out="$summary['out_of_stock']" />
                </div>
            </section>
        </div>

        <div class="col-xl-8">
            <section class="card h-100" aria-labelledby="movement-title">
                <div class="card-header">
                    <div>
                        <h2 class="panel-title" id="movement-title">Stock movement</h2>
                        <p class="panel-subtitle">Units received and issued, last {{ InventoryStatistics::MOVEMENT_DAYS }} days. Adjustments are not included.</p>
                    </div>
                    <a href="{{ route('stock.index') }}" class="panel-link">All transactions</a>
                </div>
                <div class="card-body">
                    <ul class="chart-legend mb-3">
                        <li><span class="chart-swatch" style="--swatch: var(--ims-series-in)" aria-hidden="true"></span>Received <strong>{{ number_format($unitsIn) }}</strong></li>
                        <li><span class="chart-swatch" style="--swatch: var(--ims-series-out)" aria-hidden="true"></span>Issued <strong>{{ number_format($unitsOut) }}</strong></li>
                    </ul>
                    <x-chart.movement :days="$summary['movements']" />
                </div>
            </section>
        </div>
    </div>

    {{-- Alerts and value --}}
    <div class="row g-4 mb-4">
        <div class="col-xl-8">
            <section class="card h-100 overflow-hidden" aria-labelledby="alerts-title">
                <div class="card-header">
                    <div>
                        <h2 class="panel-title" id="alerts-title">Low-stock alerts</h2>
                        <p class="panel-subtitle">Lowest quantities first</p>
                    </div>
                    <a href="{{ route('low-stock') }}" class="panel-link">View all</a>
                </div>
                @if ($alerts->isEmpty())
                    <x-empty-state icon="check2-circle" title="All products are above their reorder level"
                                   message="Products that fall to their reorder level will be listed here." />
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th scope="col">Product</th>
                                    <th scope="col" class="text-end">Available</th>
                                    <th scope="col" class="text-end">Reorder at</th>
                                    <th scope="col">Status</th>
                                    <th scope="col"><span class="visually-hidden">Actions</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($alerts as $product)
                                    @php
                                        $isOut = $product->quantity <= 0;
                                        $fill = $product->reorder_level > 0 ? min(100, $product->quantity / $product->reorder_level * 100) : 0;
                                    @endphp
                                    <tr>
                                        <td style="min-width: 13rem">
                                            <a href="{{ route('products.show', $product) }}" class="fw-medium">{{ $product->name }}</a>
                                            <div class="small text-muted-ims"><code>{{ $product->sku }}</code><span class="ms-2">{{ $product->category->name }}</span></div>
                                        </td>
                                        <td class="text-end">
                                            <div class="d-flex align-items-center justify-content-end gap-2">
                                                <span class="level-meter d-none d-md-block" aria-hidden="true"
                                                      style="--track: {{ $isOut ? 'var(--ims-critical-subtle)' : 'var(--ims-warning-subtle)' }}; --fill: {{ $isOut ? 'var(--ims-critical)' : 'var(--ims-warning)' }}">
                                                    <span style="width: {{ round($fill) }}%"></span>
                                                </span>
                                                <span class="fw-semibold">{{ number_format($product->quantity) }}</span>
                                            </div>
                                        </td>
                                        <td class="text-end text-body-secondary">{{ number_format($product->reorder_level) }}</td>
                                        <td><x-stock-badge :product="$product" /></td>
                                        <td class="text-end">
                                            @can('create', [StockTransaction::class, StockTransactionType::StockIn])
                                                <a href="{{ route('stock.create', ['type' => 'in', 'product' => $product->id]) }}"
                                                   class="btn btn-sm btn-outline-secondary" aria-label="Restock {{ $product->name }}">
                                                    <i class="bi bi-box-arrow-in-down" aria-hidden="true"></i>Restock
                                                </a>
                                            @endcan
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        </div>

        <div class="col-xl-4">
            <section class="card h-100" aria-labelledby="value-title">
                <div class="card-header">
                    <div>
                        <h2 class="panel-title" id="value-title">Inventory value</h2>
                        <p class="panel-subtitle">Units on hand multiplied by each product's prices</p>
                    </div>
                </div>
                <div class="card-body">
                    <div class="figure-row mb-4">
                        <div class="min-w-0">
                            <div class="figure-label">At cost</div>
                            <div class="figure-value tabular" title="{{ $money($summary['stock_value_cost']) }}">{{ $money($summary['stock_value_cost'], 0) }}</div>
                        </div>
                        <div class="min-w-0">
                            <div class="figure-label">At selling price</div>
                            <div class="figure-value tabular" title="{{ $money($summary['stock_value_retail']) }}">{{ $money($summary['stock_value_retail'], 0) }}</div>
                        </div>
                        <div class="min-w-0">
                            <div class="figure-label">Potential margin</div>
                            <div class="figure-value tabular" title="{{ $money($margin) }}">
                                {{ $marginShare === null ? '—' : Number::percentage($marginShare, maxPrecision: 1) }}
                            </div>
                        </div>
                    </div>

                    <h3 class="section-label">Value at cost by category</h3>
                    @if (collect($summary['value_by_category'])->sum('value') <= 0)
                        <p class="small text-body-secondary mb-0">No stock on hand to value yet.</p>
                    @else
                        <x-chart.bar-list caption="Value at cost by category"
                            :items="collect($summary['value_by_category'])->map(fn ($row) => [
                                'label' => $row['name'],
                                'value' => $row['value'],
                                'display' => $money($row['value'], 0),
                            ])" />
                    @endif
                </div>
            </section>
        </div>
    </div>

    {{-- Recent movements, category distribution and audit trail --}}
    <div class="row g-4">
        <div class="col-xl-8">
            <section class="card h-100" aria-labelledby="recent-movements-title">
                <div class="card-header">
                    <div>
                        <h2 class="panel-title" id="recent-movements-title">Latest stock transactions</h2>
                        <p class="panel-subtitle">Newest first, across all products</p>
                    </div>
                    <a href="{{ route('stock.index') }}" class="panel-link">View all</a>
                </div>
                @if ($recentTransactions->isEmpty())
                    <x-empty-state icon="clock-history" title="No stock movements yet"
                                   message="Received, issued and adjusted stock will be listed here as it is recorded." />
                @else
                    <ul class="feed">
                        @foreach ($recentTransactions as $transaction)
                            <li class="feed-item">
                                <span class="feed-icon feed-icon--{{ $transaction->type->value }}" title="{{ $transaction->type->label() }}">
                                    <i class="bi bi-{{ $transaction->type->icon() }}" aria-hidden="true"></i>
                                    <span class="visually-hidden">{{ $transaction->type->label() }}</span>
                                </span>
                                <div class="min-w-0">
                                    <div class="feed-title">
                                        <a href="{{ route('products.show', $transaction->product) }}" class="text-body">{{ $transaction->product->name }}</a>
                                    </div>
                                    <div class="feed-meta">
                                        <span>{{ $transaction->type->label() }}</span>
                                        @if ($transaction->reference)
                                            <span class="text-truncate">{{ $transaction->reference }}</span>
                                        @endif
                                        @if ($transaction->supplier)
                                            <span class="d-none d-sm-inline">{{ $transaction->supplier->name }}</span>
                                        @endif
                                        <span>{{ $transaction->user?->name ?? 'Deleted user' }}</span>
                                    </div>
                                </div>
                                <a href="{{ route('stock.show', $transaction) }}" class="feed-side text-decoration-none">
                                    <div class="feed-qty"><x-quantity-change :value="$transaction->quantity" /></div>
                                    <time class="feed-time d-block" datetime="{{ $transaction->created_at->toIso8601String() }}"
                                          title="{{ $transaction->created_at->format('d M Y H:i') }}">{{ $transaction->created_at->diffForHumans(short: true) }}</time>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>

        <div class="col-xl-4">
            <section class="card h-100" aria-labelledby="category-title">
                <div class="card-header">
                    <div>
                        <h2 class="panel-title" id="category-title">Units by category</h2>
                        <p class="panel-subtitle">Largest categories by units on hand</p>
                    </div>
                    <a href="{{ route('categories.index') }}" class="panel-link">Categories</a>
                </div>
                <div class="card-body">
                    @if (empty($summary['by_category']))
                        <p class="small text-body-secondary mb-0">No categories yet.</p>
                    @else
                        <x-chart.bar-list caption="Units by category"
                            :items="collect($summary['by_category'])->map(fn ($category) => [
                                'label' => $category['name'],
                                'value' => $category['units'],
                                'display' => number_format($category['units']),
                                'tip' => number_format($category['units']).' units in '.number_format($category['products']).' '.Str::plural('product', $category['products']),
                            ])" />
                    @endif
                </div>
            </section>
        </div>

        @can('view-activity-log')
            <div class="col-12">
                <section class="card" aria-labelledby="activity-title">
                    <div class="card-header">
                        <h2 class="panel-title" id="activity-title">Recent activity</h2>
                        <a href="{{ route('activity.index') }}" class="panel-link">Activity log</a>
                    </div>
                    @if ($recentActivity->isEmpty())
                        <x-empty-state icon="activity" title="No activity recorded yet" />
                    @else
                        <ul class="feed feed--columns">
                            @foreach ($recentActivity as $entry)
                                <li class="feed-item feed-item--text">
                                    <div class="min-w-0">
                                        <div class="small">{{ $entry->description }}</div>
                                        <div class="feed-meta">
                                            <span>{{ $entry->user?->name ?? 'System' }}</span>
                                            <time datetime="{{ $entry->created_at->toIso8601String() }}" title="{{ $entry->created_at->format('d M Y H:i') }}">{{ $entry->created_at->diffForHumans() }}</time>
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>
            </div>
        @endcan
    </div>
</x-layouts.app>
