@props(['title', 'subtitle' => null])

<div class="page-header">
    <div class="min-w-0">
        <h1 class="page-title">{{ $title }}</h1>
        @if ($subtitle)
            <p class="page-subtitle">{{ $subtitle }}</p>
        @endif
    </div>

    @if ($slot->isNotEmpty())
        <div class="page-actions">{{ $slot }}</div>
    @endif
</div>
