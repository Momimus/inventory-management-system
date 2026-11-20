@props(['icon' => 'inbox', 'title', 'message' => null])

<div class="empty-state">
    <i class="bi bi-{{ $icon }}" aria-hidden="true"></i>
    <p class="fw-semibold text-body mt-2 mb-1">{{ $title }}</p>
    @if ($message)
        <p class="small mb-0">{{ $message }}</p>
    @endif
    @if ($slot->isNotEmpty())
        <div class="mt-3">{{ $slot }}</div>
    @endif
</div>
