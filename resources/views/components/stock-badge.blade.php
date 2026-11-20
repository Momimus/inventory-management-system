@props(['product'])

@php
    $level = $product->stockLevel();
    $tone = match ($level) {
        \App\Enums\StockLevel::InStock => 'good',
        \App\Enums\StockLevel::Low => 'warning',
        \App\Enums\StockLevel::Out => 'critical',
    };
@endphp

<span class="status-badge status-badge--{{ $tone }}"><span class="status-dot" aria-hidden="true"></span>{{ $level->label() }}</span>
