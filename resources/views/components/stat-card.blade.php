@props(['label', 'value', 'icon', 'tone' => 'primary', 'href' => null, 'note' => null])

<div class="card stat-card h-100">
    <div class="card-body d-flex align-items-start gap-3">
        <span class="stat-icon bg-{{ $tone }}-subtle text-{{ $tone }}-emphasis" aria-hidden="true"><i class="bi bi-{{ $icon }}"></i></span>
        <div class="min-w-0">
            <div class="text-body-secondary small">{{ $label }}</div>
            <div class="stat-value">
                @if ($href)
                    <a href="{{ $href }}" class="text-reset text-decoration-none stretched-link">{{ $value }}</a>
                @else
                    {{ $value }}
                @endif
            </div>
            @if ($note)
                <div class="small text-body-secondary">{{ $note }}</div>
            @endif
        </div>
    </div>
</div>
