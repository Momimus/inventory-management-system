@props(['icon' => 'inbox', 'title', 'message' => null])

<div {{ $attributes->class('empty-state') }}>
    <span class="empty-state-icon" aria-hidden="true"><i class="bi bi-{{ $icon }}"></i></span>
    <p class="empty-state-title">{{ $title }}</p>
    @if ($message)
        <p class="empty-state-message">{{ $message }}</p>
    @endif
    @if ($slot->isNotEmpty())
        <div class="empty-state-actions">{{ $slot }}</div>
    @endif
</div>
