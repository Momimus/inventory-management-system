@props(['type'])

<span class="status-badge status-badge--{{ $type->value }}"><i class="bi bi-{{ $type->icon() }}" aria-hidden="true"></i>{{ $type->label() }}</span>
