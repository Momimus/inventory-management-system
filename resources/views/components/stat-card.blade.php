{{-- tone="warning" or "critical" tints the icon to flag a figure that needs attention; pass it only when the value warrants it. --}}
@props(['label', 'value', 'icon', 'tone' => null, 'href' => null, 'note' => null, 'title' => null])

<div @class(['stat-card', 'is-link' => $href, "stat-card--{$tone}" => $tone])>
    <div class="stat-head">
        <span class="stat-label">{{ $label }}</span>
        <span class="stat-icon" aria-hidden="true"><i class="bi bi-{{ $icon }}"></i></span>
    </div>
    <div class="stat-value" @if ($title) title="{{ $title }}" @endif>
        @if ($href)
            <a href="{{ $href }}" class="stretched-link">{{ $value }}</a>
        @else
            {{ $value }}
        @endif
    </div>
    @if ($note)
        <div class="stat-note">{{ $note }}</div>
    @endif
</div>
