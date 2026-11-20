@props(['title', 'subtitle' => null])

<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
    <div>
        <h1 class="h4 mb-0">{{ $title }}</h1>
        @if ($subtitle)
            <p class="text-body-secondary mb-0 small">{{ $subtitle }}</p>
        @endif
    </div>

    @if ($slot->isNotEmpty())
        <div class="d-flex flex-wrap gap-2">{{ $slot }}</div>
    @endif
</div>
