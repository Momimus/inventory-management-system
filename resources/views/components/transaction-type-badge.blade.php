@props(['type'])

<span class="badge {{ $type->badgeClass() }}"><i class="bi bi-{{ $type->icon() }} me-1" aria-hidden="true"></i>{{ $type->label() }}</span>
