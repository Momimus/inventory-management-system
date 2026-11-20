@props(['product'])

@php($level = $product->stockLevel())

<span class="badge {{ $level->badgeClass() }}">{{ $level->label() }}</span>
