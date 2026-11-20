{{--
    How active products split between healthy, low and out of stock: one whole, three parts.
    Each part's share is printed inside its block when there is room; the legend always lists every count.
--}}
@props(['healthy', 'low', 'out'])

@php
    $total = $healthy + $low + $out;
    $parts = [
        ['key' => 'good', 'label' => 'Healthy', 'hint' => 'Above reorder level', 'icon' => 'check-circle-fill', 'count' => $healthy,
         'color' => 'var(--ims-good)', 'href' => route('products.index', ['stock' => 'in', 'status' => 'active'])],
        ['key' => 'warning', 'label' => 'Low stock', 'hint' => 'At or below reorder level', 'icon' => 'exclamation-triangle-fill', 'count' => $low,
         'color' => 'var(--ims-warning)', 'href' => route('low-stock', ['level' => 'low'])],
        ['key' => 'critical', 'label' => 'Out of stock', 'hint' => 'No units on hand', 'icon' => 'x-octagon-fill', 'count' => $out,
         'color' => 'var(--ims-critical)', 'href' => route('low-stock', ['level' => 'out'])],
    ];
    $share = fn (int $count) => $total > 0 ? $count / $total * 100 : 0;
    $percent = fn (int $count) => \Illuminate\Support\Number::percentage($share($count), maxPrecision: 1);
    $summary = collect($parts)->map(fn ($part) => number_format($part['count']).' '.strtolower($part['label']))->implode(', ');
@endphp

@if ($total === 0)
    <x-empty-state icon="box-seam" title="No active products yet" message="Stock health appears once products are added." class="py-4" />
@else
    <div class="health-bar" role="img" aria-label="Stock health of {{ number_format($total) }} active products: {{ $summary }}">
        @foreach ($parts as $part)
            @continue($part['count'] === 0)
            <span @class(['health-part', 'health-part--warning' => $part['key'] === 'warning'])
                  style="--part: {{ $part['color'] }}; flex: {{ $part['count'] }} 1 0"
                  data-chart-tip="{{ $part['label'] }}&#10;{{ number_format($part['count']) }} {{ Str::plural('product', $part['count']) }} ({{ $percent($part['count']) }})">
                @if ($share($part['count']) >= 12)
                    {{ $percent($part['count']) }}
                @endif
            </span>
        @endforeach
    </div>

    <ul class="health-legend">
        @foreach ($parts as $part)
            <li>
                <i class="bi bi-{{ $part['icon'] }}" style="color: {{ $part['color'] }}" aria-hidden="true"></i>
                <span class="min-w-0 text-truncate">
                    <a href="{{ $part['href'] }}" class="fw-medium text-body">{{ $part['label'] }}</a>
                    <span class="text-muted-ims d-none d-sm-inline ms-1">{{ $part['hint'] }}</span>
                </span>
                <span class="health-legend-count">{{ number_format($part['count']) }}</span>
                <span class="health-legend-share">{{ $percent($part['count']) }}</span>
            </li>
        @endforeach
    </ul>
@endif
