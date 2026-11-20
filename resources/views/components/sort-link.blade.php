@props(['column', 'label', 'default' => false])

{{-- Table header link that toggles sorting while keeping the current filters in the URL. --}}
@php
    $current = request('sort', $default ? $column : null);
    $direction = request('direction', 'asc');
    $isSorted = $current === $column;
    $nextDirection = $isSorted && $direction === 'asc' ? 'desc' : 'asc';
@endphp

<a href="{{ request()->fullUrlWithQuery(['sort' => $column, 'direction' => $nextDirection, 'page' => null]) }}"
   class="text-reset text-decoration-none">
    {{ $label }}
    @if ($isSorted)
        <i class="bi bi-caret-{{ $direction === 'asc' ? 'up' : 'down' }}-fill" aria-label="{{ $direction === 'asc' ? 'ascending' : 'descending' }}"></i>
    @endif
</a>
