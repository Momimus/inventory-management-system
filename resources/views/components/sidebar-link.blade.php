@props(['route', 'icon', 'active' => null, 'params' => [], 'current' => null])

@php
    $isActive = $current ?? request()->routeIs($active ?? $route);
@endphp

{{-- The title gives the collapsed icon rail a hover label; the visible text stays in the DOM for screen readers. --}}
<a href="{{ route($route, $params) }}" {{ $attributes->class(['app-sidebar-link', 'active' => $isActive]) }}
   title="{{ trim(strip_tags($slot)) }}" @if ($isActive) aria-current="page" @endif>
    <i class="bi bi-{{ $icon }}" aria-hidden="true"></i>
    <span>{{ $slot }}</span>
    {{ $badge ?? '' }}
</a>
